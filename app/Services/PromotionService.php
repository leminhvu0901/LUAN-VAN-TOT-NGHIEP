<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Promotion;
use App\Models\PromotionCombo;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PromotionService
{
    // Chọn mã giảm giá tốt nhất cho giỏ hàng (thủ công nếu có nhập mã, hoặc tự động nếu không nhập)
    public function resolveBestDiscount(Collection $items, float $subtotal, ?User $user, string $channel, int $totalQuantity, ?string $manualCode = null, bool $lock = false): array
    {
        if (filled($manualCode)) {
            // Xử lý nhập mã thủ công nếu khách hàng có nhập mã
            return $this->resolveManualCode($manualCode, $items, $subtotal, $user, $channel, $totalQuantity, $lock);
        }

        // Tự động chọn mã tối ưu nếu khách không nhập mã
        return $this->resolveAutoPromotion($items, $subtotal, $channel, $totalQuantity); // Gọi hàm nội bộ tìm ưu đãi tự động tốt nhất
    }

    // Kiểm tra và xử lý áp dụng mã giảm giá nhập thủ công
    private function resolveManualCode(string $code, Collection $items, float $subtotal, ?User $user, string $channel, int $totalQuantity, bool $lock): array
    {
        $query = Promotion::query()->where('code', strtoupper(trim($code)));
        if ($lock) {
            // Khóa dòng trong database để tránh xung đột khi nhiều người cùng áp dụng mã
            $query->lockForUpdate();
        }
        $promotion = $query->first();

        if (!$promotion) {
            throw ValidationException::withMessages(['coupon_code' => 'Mã giảm giá không tồn tại.']);
        }

        if (!in_array($promotion->scope, ['order', 'product', 'category'], true)) {
            throw ValidationException::withMessages(['coupon_code' => 'Mã giảm giá không hợp lệ.']);
        }

        // Kiểm tra điều kiện thời gian, số lượt dùng, đơn hàng tối thiểu...
        $validity = $promotion->checkValidity($user, $subtotal, $channel, $totalQuantity);
        if (!$validity['valid']) {
            throw ValidationException::withMessages(['coupon_code' => $validity['message']]);
        }

        // Nạp sẵn dữ liệu quan hệ products và categories nếu chưa được load
        $promotion->loadMissing(['products', 'categories']);

        // Tính tổng tiền các món hợp lệ trong giỏ hàng để kiểm tra
        if ($this->eligibleSubtotal($promotion, $items) <= 0) {
            throw ValidationException::withMessages([
                'coupon_code' => match ($promotion->scope) {
                    // pluck('name'): Rút danh sách tên sản phẩm, implode: nối thành chuỗi
                    'product' => 'Giỏ hàng chưa có sản phẩm áp dụng mã này. Mã chỉ giảm cho: '
                        . $promotion->products->pluck('name')->implode(', ') . '.',
                    'category' => 'Giỏ hàng chưa có sản phẩm thuộc danh mục áp dụng mã này. Mã chỉ giảm cho danh mục: '
                        . $promotion->categories->pluck('name')->implode(', ') . '.',
                    default => 'Mã giảm giá không áp dụng được cho giỏ hàng hiện tại.',
                },
            ]);
        }

        // Trả về thông tin mã khuyến mãi và số tiền giảm đã qua tính toán
        return ['promotion' => $promotion, 'discount' => $this->calculateDiscount($promotion, $items)]; // Gọi hàm nội bộ để tính số tiền chiết khấu thực tế
    }

    // Tự động tìm mã giảm giá hợp lệ và tối ưu nhất cho giỏ hàng
    private function resolveAutoPromotion(Collection $items, float $subtotal, string $channel, int $totalQuantity): array
    {
        $now = now();
        $promotion = Promotion::query()
            ->whereIn('scope', ['order', 'product', 'category'])
            ->where('is_active', true)
            ->where('apply_for', 'all')
            ->whereIn('applies_to', ['all', $channel])
            // Bỏ qua mã yêu cầu nhân viên xác nhận (chỉ dành cho nhập tay)
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
            // Lọc các mã đang trong khung giờ hiệu lực
            ->filter(fn(Promotion $p) => $this->isWithinTimeWindow($p, $now))
            // Lọc các mã mà giỏ hàng có chứa sản phẩm áp dụng
            ->filter(fn(Promotion $p) => $this->eligibleSubtotal($p, $items) > 0)
            // Sắp xếp mã giảm nhiều tiền nhất lên đầu tiên
            ->sortByDesc(fn(Promotion $p) => $this->calculateDiscount($p, $items))
            ->first();

        // Tính số tiền giảm của mã tìm được (nếu không có thì trả về 0)
        if ($promotion) {
            $discount = $this->calculateDiscount($promotion, $items); // Tính số tiền giảm giá của mã tự động được chọn
        } else {
            $discount = 0.0;
        }

        return ['promotion' => $promotion, 'discount' => $discount];
    }

    // Tính số tiền giảm giá thực tế của một khuyến mãi trên giỏ hàng
    public function calculateDiscount(Promotion $promotion, Collection $items): float
    {
        // Lấy tổng số tiền của các sản phẩm đủ điều kiện giảm
        $eligibleSubtotal = $this->eligibleSubtotal($promotion, $items);
        if ($eligibleSubtotal <= 0) {
            return 0.0;
        }

        // Tính tiền giảm theo phần trăm hoặc theo giá trị cố định
        if ($promotion->type === 'percent') {
            $discount = round($eligibleSubtotal * ((float) $promotion->value / 100));
        } else {
            $discount = (float) $promotion->value;
        }

        // Giới hạn ở mức giảm tối đa nếu có thiết lập
        if ($promotion->max_discount_amount) {
            $discount = min($discount, (float) $promotion->max_discount_amount);
        }

        // Đảm bảo số tiền giảm không vượt quá tổng giá trị hàng hợp lệ
        return min($discount, $eligibleSubtotal);
    }

    // Tính tổng thành tiền của các sản phẩm nằm trong phạm vi áp dụng khuyến mãi
    public function eligibleSubtotal(Promotion $promotion, Collection $items): float
    {
        return match ($promotion->scope) {
            // Gọi hàm tính tổng tiền riêng theo sản phẩm
            'product' => $this->eligibleSubtotalForProducts($promotion, $items), // Gọi hàm tính tổng tiền theo danh sách sản phẩm
            // Gọi hàm tính tổng tiền riêng theo danh mục
            'category' => $this->eligibleSubtotalForCategories($promotion, $items), // Gọi hàm tính tổng tiền theo danh mục
            // Mặc định (toàn đơn hàng) -> cộng tổng tiền tất cả món trong giỏ
            default => (float) $items->sum(fn($item) => (float) $item->calculated_unit_price * (int) $item->quantity),
        };
    }

    // Tính tổng thành tiền riêng cho danh sách các sản phẩm áp dụng trực tiếp
    private function eligibleSubtotalForProducts(Promotion $promotion, Collection $items): float
    {
        // Rút ra mảng danh sách ID sản phẩm được áp dụng
        $productIds = $promotion->products->pluck('id')->all();

        return (float) $items
            // Lọc ra các món trong giỏ hàng thuộc danh sách sản phẩm khuyến mãi
            ->filter(fn($item) => in_array($item->product_id, $productIds, true))
            ->sum(fn($item) => (float) $item->calculated_unit_price * (int) $item->quantity);
    }

    // Tính tổng thành tiền riêng cho các sản phẩm thuộc danh mục áp dụng
    private function eligibleSubtotalForCategories(Promotion $promotion, Collection $items): float
    {
        // Rút ra mảng danh sách ID danh mục được áp dụng
        $categoryIds = $promotion->categories->pluck('id')->all();

        return (float) $items
            // Lọc ra các món trong giỏ hàng có danh mục thuộc khuyến mãi
            ->filter(fn($item) => $item->product && in_array($item->product->category_id, $categoryIds, true))
            ->sum(fn($item) => (float) $item->calculated_unit_price * (int) $item->quantity);
    }

    // Xử lý và tính toán tất cả các phần thưởng Combo (giảm giá & tặng quà) cho giỏ hàng
    public function resolveComboRewards(Collection $items, string $channel, ?array $autoDiscountResult = null): array
    {
        $now = now();

        $combos = Promotion::query()
            ->where('scope', 'combo')
            ->where('is_active', true)
            ->whereIn('applies_to', ['all', $channel])
            ->with(['combo.giftProduct', 'comboItems.product'])
            ->get()
            // Lọc các combo hợp lệ và đang trong khung giờ
            ->filter(fn(Promotion $p) => $p->combo && $p->comboItems->isNotEmpty() && $this->isWithinTimeWindow($p, $now));

        // Khởi tạo bể chứa số lượng còn lại của từng món trong giỏ để kiểm tra điều kiện combo
        $remaining = [];
        $priceByProduct = [];
        foreach ($items as $item) {
            $remaining[$item->product_id] = ($remaining[$item->product_id] ?? 0) + (int) $item->quantity;
            if (!isset($priceByProduct[$item->product_id])) {
                $priceByProduct[$item->product_id] = (float) ($item->calculated_unit_price ?? $item->unit_price);
            }
        }

        // Lấy thông tin mã giảm giá tự động đang chạy song song để so sánh trùng lặp
        $autoPromotion = $autoDiscountResult['promotion'] ?? null;
        $autoDiscount = (float) ($autoDiscountResult['discount'] ?? 0.0);

        if ($autoPromotion) {
            $autoEligibleSubtotal = $this->eligibleSubtotal($autoPromotion, $items); // Tính tổng tiền sản phẩm áp dụng mã tự động
        } else {
            $autoEligibleSubtotal = 0.0;
        }

        if ($autoEligibleSubtotal > 0) {
            $autoEffectiveRate = $autoDiscount / $autoEligibleSubtotal;
        } else {
            $autoEffectiveRate = 0.0;
        }

        if ($autoPromotion) {
            // Gọi hàm lấy mảng ID sản phẩm thuộc mã giảm giá kia
            $autoProductIds = $this->eligibleProductIdsForPromotion($autoPromotion); // Lấy danh sách ID sản phẩm được áp mã tự động
        } else {
            $autoProductIds = [];
        }

        $autoDiscountRemoved = 0.0;

        // Ước tính giá trị và sắp xếp ưu tiên combo có giá trị cao hơn xử lý trước
        $sorted = $combos->sortByDesc(function (Promotion $p) use ($remaining, $priceByProduct) {
            return $this->comboProspectiveValue($p, $remaining, $priceByProduct); // Tính toán giá trị ước tính để sắp xếp thứ tự ưu tiên
        })->values();

        $results = [];
        foreach ($sorted as $promotion) {
            $combo = $promotion->combo;
            // Tính số lần combo được áp dụng dựa trên số lượng hàng còn lại
            $applications = $this->comboApplications($promotion, $remaining); // Tính số lần combo có thể áp dụng dựa vào lượng tồn món
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
                // Tính tiền giảm giá thô của combo
                $discount = $this->comboDiscountAmount($combo, $eligibleSubtotal, $applications);
                if ($discount > 0 && $autoPromotion) {
                    // Xử lý so sánh tranh chấp giá trị với mã giảm giá tự động
                    $discount = $this->comboDiscountAfterOverlap( // Tính toán giảm giá sau khi trừ lượng trùng lặp với mã tự động
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
                // Trừ bớt số lượng món đã dùng cho combo này khỏi bể chứa hàng
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

    // Tính số lần tối đa mà giỏ hàng đủ điều kiện áp dụng Combo
    private function comboApplications(Promotion $promotion, array $remaining): int
    {
        $applications = null;
        foreach ($promotion->comboItems as $comboItem) {
            // intdiv: Chia lấy phần nguyên để biết ghép được mấy combo đầy đủ món
            $possible = intdiv($remaining[$comboItem->product_id] ?? 0, max(1, $comboItem->quantity));

            if (is_null($applications)) {
                $applications = $possible;
            } else {
                $applications = min($applications, $possible);
            }
        }
        return (int) ($applications ?? 0);
    }

    // Ước tính tổng giá trị thưởng của Combo để sắp xếp thứ tự ưu tiên xử lý
    private function comboProspectiveValue(Promotion $promotion, array $remaining, array $priceByProduct): float
    {
        $combo = $promotion->combo;
        // Gọi hàm tính số lần combo có thể nhận
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
            // Gọi hàm tính số tiền giảm của phần giảm giá
            $value += $this->comboDiscountAmount($combo, $eligibleSubtotal, $applications);
        }
        if ($combo->hasGift() && $combo->auto_add_gift && $combo->giftProduct) {
            // Cộng thêm giá trị sản phẩm quà tặng vào tổng giá trị combo
            $value += (float) $combo->giftProduct->base_price * $combo->gift_quantity * $applications;
        }

        return $value;
    }

    // Tính toán số tiền giảm giá chính xác của một Khuyến mãi Combo
    private function comboDiscountAmount(PromotionCombo $combo, float $eligibleSubtotal, int $applications): float
    {
        if ($eligibleSubtotal <= 0) {
            return 0.0;
        }

        if ($combo->discount_type === 'percent') {
            $discount = round($eligibleSubtotal * ((float) $combo->discount_value / 100));
        } else {
            $discount = (float) $combo->discount_value * $applications;
        }

        if ($combo->max_discount_amount) {
            $discount = min($discount, (float) $combo->max_discount_amount);
        }

        return min($discount, $eligibleSubtotal);
    }

    // Xử lý tranh chấp giảm giá khi 1 sản phẩm trong giỏ hàng vừa thuộc Combo vừa được áp dụng mã giảm giá tự động
    private function comboDiscountAfterOverlap(Promotion $promotion, float $discount, int $applications, array $priceByProduct, ?array $autoProductIds, float $autoEffectiveRate, float &$autoDiscountRemoved): float
    {
        $comboProductIds = $promotion->comboItems->pluck('product_id')->all();
        // Lấy danh sách sản phẩm bị trùng ưu đãi
        if (is_null($autoProductIds)) {
            // Mã giảm giá kia áp dụng toàn đơn hàng -> Tất cả sản phẩm trong combo đều tính là trùng
            $overlapProductIds = $comboProductIds;
        } else {
            // Lấy các sản phẩm chung xuất hiện ở cả Combo và Mã giảm giá kia
            $overlapProductIds = array_values(array_intersect($comboProductIds, $autoProductIds));
        }

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
            return 0.0;
        }

        // Khấu trừ phần tiền giảm trùng khỏi mã giảm giá kia
        $autoDiscountRemoved += $otherValueOnOverlap;
        return $discount;
    }

    // Lấy ra danh sách tất cả ID của các sản phẩm được áp dụng mã khuyến mãi này
    private function eligibleProductIdsForPromotion(Promotion $promotion): ?array
    {
        return match ($promotion->scope) {
            'product' => $promotion->products->pluck('id')->all(),
            // Query lấy danh sách ID sản phẩm thuộc danh mục khuyến mãi
            'category' => Product::query()
                ->whereIn('category_id', $promotion->categories->pluck('id')->all())
                ->pluck('id')->all(),
            default => null,
        };
    }

    // Kiểm tra xem khuyến mãi có đang nằm trong khung giờ hiệu lực hay không
    private function isWithinTimeWindow(Promotion $promotion, $now): bool
    {
        if ($promotion->is_recurring) {
            $nowStr = $now->format('H:i:s');
            // dayOfWeekIso: Lấy thứ trong tuần (1 là Thứ 2, 7 là Chủ nhật)
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

        // gt: lớn hơn (sau thời gian kết thúc), lt: nhỏ hơn (trước thời gian bắt đầu)
        if ($promotion->end_at && $now->gt($promotion->end_at)) {
            return false;
        }
        if ($promotion->start_at && $now->lt($promotion->start_at)) {
            return false;
        }

        return true;
    }
}
