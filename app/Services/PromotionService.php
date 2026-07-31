<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Promotion;
use App\Models\PromotionCombo;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

// Nơi DUY NHẤT tính giảm giá + quà tặng — trước đây công thức percent/fixed bị lặp lại độc lập ở
// OrderService và CartController::validateCoupon(), dễ lệch nhau khi sửa 1 chỗ quên chỗ kia.
//
// Ba loại giảm giá theo tiền (order/product/category) LOẠI TRỪ LẪN NHAU — chỉ 1 áp dụng, chọn theo
// mức giảm có lợi nhất cho khách (giữ đúng quy tắc cũ). Combo (trước đây gọi "Mua X tặng Y") là
// nghiệp vụ riêng, có 2 thành phần thưởng ĐỘC LẬP nhau (giảm giá + tặng quà, xem resolveComboRewards()):
// phần TẶNG QUÀ luôn CỘNG THÊM vô điều kiện (tặng vật lý, không có rủi ro giảm giá 2 lần); phần GIẢM
// GIÁ của combo CHỈ cộng thêm nếu KHÔNG trùng sản phẩm với mã giảm giá order/product/category đang áp
// dụng — nếu trùng, chỉ bên có lợi hơn cho khách được áp dụng trên đúng phần sản phẩm trùng nhau.
class PromotionService
{
    // min_order_amount/min_quantity của Promotion::checkValidity() LUÔN kiểm tra theo TOÀN ĐƠN (giữ
    // nguyên hành vi cũ, không đổi ý nghĩa tham số) — chỉ số tiền giảm mới được tính riêng theo phần
    // sản phẩm/danh mục khớp (eligible subtotal), không phải toàn đơn.
    public function resolveBestDiscount(
        Collection $items,
        float $subtotal,
        ?User $user,
        string $channel,
        int $totalQuantity,
        ?string $manualCode = null,
        bool $lock = false
    ): array {
        if (filled($manualCode)) {
            return $this->resolveManualCode($manualCode, $items, $subtotal, $user, $channel, $totalQuantity, $lock);
        }

        return $this->resolveAutoPromotion($items, $subtotal, $channel, $totalQuantity);
    }

    private function resolveManualCode(
        string $code,
        Collection $items,
        float $subtotal,
        ?User $user,
        string $channel,
        int $totalQuantity,
        bool $lock
    ): array {
        $query = Promotion::query()->where('code', strtoupper(trim($code)));
        if ($lock) {
            $query->lockForUpdate();
        }
        $promotion = $query->first();

        if (!$promotion) {
            throw ValidationException::withMessages(['coupon_code' => 'Mã giảm giá không tồn tại.']);
        }

        if (!in_array($promotion->scope, ['order', 'product', 'category'], true)) {
            // Mã Mua X tặng Y không phải mã giảm tiền — không nhập tay được ở đây.
            throw ValidationException::withMessages(['coupon_code' => 'Mã giảm giá không hợp lệ.']);
        }

        $validity = $promotion->checkValidity($user, $subtotal, $channel, $totalQuantity);
        if (!$validity['valid']) {
            throw ValidationException::withMessages(['coupon_code' => $validity['message']]);
        }

        $promotion->loadMissing(['products', 'categories']);

        // Mã theo sản phẩm/danh mục mà giỏ KHÔNG có món nào thuộc phạm vi -> phải từ chối thẳng, kèm
        // nói rõ mã áp dụng cho gì. Nếu để lọt, khách nhận thông báo "Áp dụng thành công" nhưng số
        // tiền giảm bằng 0 — vừa vô lý vừa khiến khách tưởng hệ thống tính sai.
        if ($this->eligibleSubtotal($promotion, $items) <= 0) {
            throw ValidationException::withMessages([
                'coupon_code' => match ($promotion->scope) {
                    'product' => 'Giỏ hàng chưa có sản phẩm áp dụng mã này. Mã chỉ giảm cho: '
                    . $promotion->products->pluck('name')->implode(', ') . '.',
                    'category' => 'Giỏ hàng chưa có sản phẩm thuộc danh mục áp dụng mã này. Mã chỉ giảm cho danh mục: '
                    . $promotion->categories->pluck('name')->implode(', ') . '.',
                    default => 'Mã giảm giá không áp dụng được cho giỏ hàng hiện tại.',
                },
            ]);
        }

        return ['promotion' => $promotion, 'discount' => $this->calculateDiscount($promotion, $items)];
    }

    private function resolveAutoPromotion(Collection $items, float $subtotal, string $channel, int $totalQuantity): array
    {
        $now = now();

        $promotion = Promotion::query()
            ->whereIn('scope', ['order', 'product', 'category'])
            ->where('is_active', true)
            ->where('apply_for', 'all')
            ->whereIn('applies_to', ['all', $channel])
            // Mã cần nhân viên xác nhận (sinh viên, sinh nhật...) KHÔNG được tự động áp —
            // chỉ lễ tân/khách nhập tay sau khi đã xác nhận điều kiện thực tế.
            ->where('requires_staff_verification', false)
            ->where(function ($q) use ($subtotal) {
                $q->whereNull('min_order_amount')->orWhere('min_order_amount', '<=', $subtotal);
            })
            ->where(function ($q) use ($totalQuantity) {
                $q->whereNull('min_quantity')->orWhere('min_quantity', '<=', $totalQuantity);
            })
            ->where(function ($q) {
                $q->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit');
            })
            ->with(['products', 'categories'])
            ->get()
            ->filter(fn(Promotion $p) => $this->isWithinTimeWindow($p, $now))
            // Mã theo sản phẩm/danh mục mà giỏ không có món nào khớp thì giảm = 0đ — không được tự
            // chọn mã đó (dù kỹ thuật nó "hợp SQL"), tránh gắn promotion_id vào đơn mà không mang
            // lại lợi ích thật nào cho khách.
            ->filter(fn(Promotion $p) => $this->eligibleSubtotal($p, $items) > 0)
            ->sortByDesc(fn(Promotion $p) => $this->calculateDiscount($p, $items))
            ->first();

        $discount = $promotion ? $this->calculateDiscount($promotion, $items) : 0.0;

        return ['promotion' => $promotion, 'discount' => $discount];
    }

    // Số tiền giảm thật, tính trên đúng phần "hợp lệ" theo scope (toàn đơn/sản phẩm/danh mục), luôn
    // không âm và không vượt quá chính phần hợp lệ đó.
    public function calculateDiscount(Promotion $promotion, Collection $items): float
    {
        $eligibleSubtotal = $this->eligibleSubtotal($promotion, $items);
        if ($eligibleSubtotal <= 0) {
            return 0.0;
        }

        $discount = $promotion->type === 'percent'
            ? round($eligibleSubtotal * ((float) $promotion->value / 100))
            : (float) $promotion->value;

        if ($promotion->max_discount_amount) {
            $discount = min($discount, (float) $promotion->max_discount_amount);
        }

        return min($discount, $eligibleSubtotal);
    }

    // Tổng thành tiền của CHỈ các dòng sản phẩm khớp phạm vi khuyến mãi. scope='order' (hoặc giá trị
    // lạ/cũ) -> toàn bộ subtotal, giữ đúng hành vi trước khi có scope (tương thích ngược 100%).
    public function eligibleSubtotal(Promotion $promotion, Collection $items): float
    {
        return match ($promotion->scope) {
            'product' => $this->eligibleSubtotalForProducts($promotion, $items),
            'category' => $this->eligibleSubtotalForCategories($promotion, $items),
            default => (float) $items->sum(fn($item) => (float) $item->calculated_unit_price * (int) $item->quantity),
        };
    }

    private function eligibleSubtotalForProducts(Promotion $promotion, Collection $items): float
    {
        $productIds = $promotion->products->pluck('id')->all();

        return (float) $items
            ->filter(fn($item) => in_array($item->product_id, $productIds, true))
            ->sum(fn($item) => (float) $item->calculated_unit_price * (int) $item->quantity);
    }

    private function eligibleSubtotalForCategories(Promotion $promotion, Collection $items): float
    {
        $categoryIds = $promotion->categories->pluck('id')->all();

        return (float) $items
            ->filter(fn($item) => $item->product && in_array($item->product->category_id, $categoryIds, true))
            ->sum(fn($item) => (float) $item->calculated_unit_price * (int) $item->quantity);
    }

    // Danh sách thưởng Combo đang đủ điều kiện cho giỏ hàng hiện tại. Mỗi combo bắt buộc khách phải
    // mua ĐỦ TẤT CẢ sản phẩm trong danh sách (theo đúng số lượng yêu cầu từng món) mới được tính là
    // "đã mua đủ combo". Thưởng gồm 2 thành phần ĐỘC LẬP (1 combo có thể sinh 0, 1, hoặc 2 phần tử
    // kết quả): giảm giá (percent/fixed, chỉ tính trên giá trị các sản phẩm trong combo) và tặng quà
    // — bật/tắt riêng từng cái ở PromotionCombo::discount_type/gift_product_id.
    //
    // $autoDiscountResult (nếu có) = kết quả resolveBestDiscount() đã tính SẴN từ nơi gọi (mã giảm giá
    // order/product/category tự chọn hoặc nhập tay) — dùng để giải quyết trường hợp phần giảm giá của
    // combo trùng sản phẩm với mã đó: chỉ 1 bên có lợi hơn được áp dụng trên phần trùng nhau (KHÔNG
    // ảnh hưởng tới phần tặng quà của combo — quà luôn cộng dồn độc lập, không có rủi ro giảm giá 2 lần
    // vì bản chất là tặng thêm sản phẩm chứ không trừ tiền).
    //
    // Hàm THUẦN (không side-effect, không lưu DB) — nơi gọi (OrderService khi tạo đơn thật,
    // CartController/Reception\OrderController khi hiển thị preview) tự quyết dùng kết quả thế nào.
    //
    // Trả về ['entries' => [...], 'auto_discount' => float] — entries là mảng phần tử kiểu
    // ['type'=>'discount'|'gift', 'promotion', 'applications', 'combo_items', ...field riêng theo type],
    // auto_discount là số tiền giảm CUỐI CÙNG của mã trong $autoDiscountResult sau khi đã trừ phần
    // "nhường" cho các combo thắng cuộc (bằng nguyên giá trị gốc nếu không combo nào trùng sản phẩm).
    public function resolveComboRewards(Collection $items, string $channel, ?array $autoDiscountResult = null): array
    {
        $now = now();

        $combos = Promotion::query()
            ->where('scope', 'combo')
            ->where('is_active', true)
            ->whereIn('applies_to', ['all', $channel])
            ->with(['combo.giftProduct', 'comboItems.product'])
            ->get()
            ->filter(fn(Promotion $p) => $p->combo && $p->comboItems->isNotEmpty() && $this->isWithinTimeWindow($p, $now));

        // Pool số lượng còn lại của từng sản phẩm trong giỏ — bị trừ dần mỗi khi 1 combo thực sự được
        // cấp thưởng (dù chỉ 1 trong 2 thành phần), để 2 combo khác nhau cùng cần chung 1 sản phẩm
        // không cùng "thấy" đủ hàng và cùng được cấp thưởng trên cùng 1 đơn vị hàng thật.
        $remaining = [];
        $priceByProduct = [];
        foreach ($items as $item) {
            $remaining[$item->product_id] = ($remaining[$item->product_id] ?? 0) + (int) $item->quantity;
            if (!isset($priceByProduct[$item->product_id])) {
                $priceByProduct[$item->product_id] = (float) ($item->calculated_unit_price ?? $item->unit_price);
            }
        }

        // Mã giảm giá khác (order/product/category) đang được áp dụng — dùng để so sánh khi phần giảm
        // giá của combo trùng sản phẩm (xem comboDiscountAfterOverlap()).
        $autoPromotion = $autoDiscountResult['promotion'] ?? null;
        $autoDiscount = (float) ($autoDiscountResult['discount'] ?? 0.0);
        $autoEligibleSubtotal = $autoPromotion ? $this->eligibleSubtotal($autoPromotion, $items) : 0.0;
        $autoEffectiveRate = $autoEligibleSubtotal > 0 ? ($autoDiscount / $autoEligibleSubtotal) : 0.0;
        $autoProductIds = $autoPromotion ? $this->eligibleProductIdsForPromotion($autoPromotion) : [];
        $autoDiscountRemoved = 0.0;

        // Xử lý combo giá trị cao hơn trước — combo nào "thấy" đủ hàng trước sẽ được ưu tiên cấp
        // thưởng khi 2 combo tranh nhau cùng 1 sản phẩm.
        $sorted = $combos->sortByDesc(function (Promotion $p) use ($remaining, $priceByProduct) {
            return $this->comboProspectiveValue($p, $remaining, $priceByProduct);
        })->values();

        $results = [];
        foreach ($sorted as $promotion) {
            $combo = $promotion->combo;
            $applications = $this->comboApplications($promotion, $remaining);
            if ($combo->max_applications_per_order) {
                $applications = min($applications, $combo->max_applications_per_order);
            }
            if ($applications <= 0) {
                continue;
            }

            $eligibleSubtotal = 0.0;
            foreach ($promotion->comboItems as $comboItem) {
                $eligibleSubtotal += ($priceByProduct[$comboItem->product_id] ?? 0) * $comboItem->quantity * $applications;
            }

            $granted = false;

            if ($combo->hasDiscount()) {
                $discount = $this->comboDiscountAmount($combo, $eligibleSubtotal, $applications);
                if ($discount > 0 && $autoPromotion) {
                    $discount = $this->comboDiscountAfterOverlap(
                        $promotion,
                        $discount,
                        $applications,
                        $priceByProduct,
                        $autoProductIds,
                        $autoEffectiveRate,
                        $autoDiscountRemoved
                    );
                }
                if ($discount > 0) {
                    $results[] = [
                        'type' => 'discount',
                        'promotion' => $promotion,
                        'applications' => $applications,
                        'combo_items' => $promotion->comboItems,
                        'discount_amount' => $discount,
                    ];
                    $granted = true;
                }
            }

            if ($combo->hasGift() && $combo->auto_add_gift) {
                $giftQty = $applications * $combo->gift_quantity;
                if ($giftQty > 0) {
                    $results[] = [
                        'type' => 'gift',
                        'promotion' => $promotion,
                        'applications' => $applications,
                        'combo_items' => $promotion->comboItems,
                        'gift_product' => $combo->giftProduct,
                        'granted_quantity' => $giftQty,
                    ];
                    $granted = true;
                }
            }

            if ($granted) {
                foreach ($promotion->comboItems as $comboItem) {
                    $remaining[$comboItem->product_id] = ($remaining[$comboItem->product_id] ?? 0) - $comboItem->quantity * $applications;
                }
            }
        }

        return [
            'entries' => $results,
            'auto_discount' => max(0.0, $autoDiscount - $autoDiscountRemoved),
        ];
    }

    // Số lần combo có thể áp dụng dựa trên pool số lượng còn lại — "phải đủ TẤT CẢ sản phẩm" nghĩa là
    // lấy min() qua từng dòng combo item, không phải cộng dồn.
    private function comboApplications(Promotion $promotion, array $remaining): int
    {
        $applications = null;
        foreach ($promotion->comboItems as $comboItem) {
            $possible = intdiv($remaining[$comboItem->product_id] ?? 0, max(1, $comboItem->quantity));
            $applications = is_null($applications) ? $possible : min($applications, $possible);
        }
        return (int) ($applications ?? 0);
    }

    // Giá trị "nếu được cấp thưởng ngay bây giờ" — chỉ dùng để QUYẾT ĐỊNH THỨ TỰ xử lý (combo giá trị
    // cao hơn được ưu tiên trước khi tranh chấp sản phẩm chung), không làm thay đổi $remaining thật.
    private function comboProspectiveValue(Promotion $promotion, array $remaining, array $priceByProduct): float
    {
        $combo = $promotion->combo;
        $applications = $this->comboApplications($promotion, $remaining);
        if ($combo->max_applications_per_order) {
            $applications = min($applications, $combo->max_applications_per_order);
        }
        if ($applications <= 0) {
            return 0.0;
        }

        $value = 0.0;
        if ($combo->hasDiscount()) {
            $eligibleSubtotal = 0.0;
            foreach ($promotion->comboItems as $comboItem) {
                $eligibleSubtotal += ($priceByProduct[$comboItem->product_id] ?? 0) * $comboItem->quantity * $applications;
            }
            $value += $this->comboDiscountAmount($combo, $eligibleSubtotal, $applications);
        }
        if ($combo->hasGift() && $combo->auto_add_gift && $combo->giftProduct) {
            $value += (float) $combo->giftProduct->base_price * $combo->gift_quantity * $applications;
        }

        return $value;
    }

    // Số tiền giảm của phần "Giảm giá" trong combo — percent tính trên eligibleSubtotal (đã nhân sẵn
    // theo applications), fixed nhân theo applications (thưởng tăng theo số lần đủ combo, giống cách
    // số lượng quà tặng cũng nhân theo applications) — không bao giờ vượt quá chính eligibleSubtotal đó.
    private function comboDiscountAmount(PromotionCombo $combo, float $eligibleSubtotal, int $applications): float
    {
        if ($eligibleSubtotal <= 0) {
            return 0.0;
        }

        $discount = $combo->discount_type === 'percent'
            ? round($eligibleSubtotal * ((float) $combo->discount_value / 100))
            : ((float) $combo->discount_value * $applications);

        if ($combo->max_discount_amount) {
            $discount = min($discount, (float) $combo->max_discount_amount);
        }

        return min($discount, $eligibleSubtotal);
    }

    // Nếu phần giảm giá của combo trùng sản phẩm với mã giảm giá khác đang áp dụng ($autoProductIds),
    // so giá trị 2 bên trên đúng phần giao nhau — bên có lợi hơn thắng. Combo thua thì mất phần giảm
    // giá (trả về 0, KHÔNG ảnh hưởng phần tặng quà); combo thắng thì trừ dần $autoDiscountRemoved để
    // sau vòng lặp tính lại đúng số tiền giảm cuối cùng còn lại của mã kia.
    private function comboDiscountAfterOverlap(
        Promotion $promotion,
        float $discount,
        int $applications,
        array $priceByProduct,
        ?array $autoProductIds,
        float $autoEffectiveRate,
        float &$autoDiscountRemoved
    ): float {
        $comboProductIds = $promotion->comboItems->pluck('product_id')->all();
        $overlapProductIds = is_null($autoProductIds)
            ? $comboProductIds // scope='order' -> áp dụng mọi sản phẩm -> luôn giao toàn bộ combo
            : array_values(array_intersect($comboProductIds, $autoProductIds));

        if (empty($overlapProductIds)) {
            return $discount;
        }

        $overlapSubtotal = 0.0;
        foreach ($promotion->comboItems as $comboItem) {
            if (in_array($comboItem->product_id, $overlapProductIds, true)) {
                $overlapSubtotal += ($priceByProduct[$comboItem->product_id] ?? 0) * $comboItem->quantity * $applications;
            }
        }

        $otherValueOnOverlap = $overlapSubtotal * $autoEffectiveRate;
        if ($discount <= $otherValueOnOverlap) {
            // Mã giảm giá khác có lợi hơn -> combo mất phần giảm giá này (phần tặng quà không bị đụng tới).
            return 0.0;
        }

        $autoDiscountRemoved += $otherValueOnOverlap;
        return $discount;
    }

    // Tập product_id mà 1 promotion (order/product/category) áp dụng — null nghĩa là áp dụng MỌI sản
    // phẩm (scope='order'), dùng để xét trùng sản phẩm với combo ở comboDiscountAfterOverlap().
    private function eligibleProductIdsForPromotion(Promotion $promotion): ?array
    {
        return match ($promotion->scope) {
            'product' => $promotion->products->pluck('id')->all(),
            'category' => Product::query()
                ->whereIn('category_id', $promotion->categories->pluck('id')->all())
                ->pluck('id')->all(),
            default => null,
        };
    }

    // Sao y nguyên logic lọc cửa sổ thời gian của resolveAutoPromotion() cũ (recurring theo
    // ngày/giờ, hoặc cố định theo start_at/end_at) — dùng chung cho cả giảm giá tự động lẫn Mua X
    // tặng Y, vì 2 nghiệp vụ đều cần đúng 1 kiểu kiểm tra "còn hiệu lực ngay lúc này" như nhau.
    private function isWithinTimeWindow(Promotion $promotion, $now): bool
    {
        if ($promotion->is_recurring) {
            $nowStr = $now->format('H:i:s');
            $currentDay = $now->dayOfWeekIso;

            if (
                is_array($promotion->recurring_days) && count($promotion->recurring_days) > 0
                && !in_array($currentDay, $promotion->recurring_days, true)
            ) {
                return false;
            }
            if ($promotion->recurring_start_time && $nowStr < $promotion->recurring_start_time) {
                return false;
            }
            if ($promotion->recurring_end_time && $nowStr > $promotion->recurring_end_time) {
                return false;
            }

            return true;
        }

        if ($promotion->end_at && $now->gt($promotion->end_at)) {
            return false;
        }
        if ($promotion->start_at && $now->lt($promotion->start_at)) {
            return false;
        }

        return true;
    }
}
