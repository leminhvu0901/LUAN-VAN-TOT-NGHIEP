<?php

namespace App\Services;

use App\Models\Promotion;
use App\Models\PromotionBuyXGetY;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

// Nơi DUY NHẤT tính giảm giá + quà tặng — trước đây công thức percent/fixed bị lặp lại độc lập ở
// OrderService và CartController::validateCoupon(), dễ lệch nhau khi sửa 1 chỗ quên chỗ kia.
//
// Ba loại giảm giá theo tiền (order/product/category) LOẠI TRỪ LẪN NHAU — chỉ 1 áp dụng, chọn theo
// mức giảm có lợi nhất cho khách (giữ đúng quy tắc cũ). Mua X tặng Y là nghiệp vụ ĐỘC LẬP, được CỘNG
// THÊM (không cạnh tranh với giảm giá tiền vì bản chất khác nhau — tặng vật lý, không giảm tiền) — 1
// đơn có thể vừa có mã giảm % vừa có quà tặng. Đây là "quy tắc cộng dồn" duy nhất được áp dụng.
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
            ->filter(fn (Promotion $p) => $this->isWithinTimeWindow($p, $now))
            // Mã theo sản phẩm/danh mục mà giỏ không có món nào khớp thì giảm = 0đ — không được tự
            // chọn mã đó (dù kỹ thuật nó "hợp SQL"), tránh gắn promotion_id vào đơn mà không mang
            // lại lợi ích thật nào cho khách.
            ->filter(fn (Promotion $p) => $this->eligibleSubtotal($p, $items) > 0)
            ->sortByDesc(fn (Promotion $p) => $this->calculateDiscount($p, $items))
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
            default => (float) $items->sum(fn ($item) => (float) $item->calculated_unit_price * (int) $item->quantity),
        };
    }

    private function eligibleSubtotalForProducts(Promotion $promotion, Collection $items): float
    {
        $productIds = $promotion->products->pluck('id')->all();

        return (float) $items
            ->filter(fn ($item) => in_array($item->product_id, $productIds, true))
            ->sum(fn ($item) => (float) $item->calculated_unit_price * (int) $item->quantity);
    }

    private function eligibleSubtotalForCategories(Promotion $promotion, Collection $items): float
    {
        $categoryIds = $promotion->categories->pluck('id')->all();

        return (float) $items
            ->filter(fn ($item) => $item->product && in_array($item->product->category_id, $categoryIds, true))
            ->sum(fn ($item) => (float) $item->calculated_unit_price * (int) $item->quantity);
    }

    // Danh sách quà Mua X tặng Y đang đủ điều kiện cho giỏ hàng hiện tại — ĐỘC LẬP với giảm giá tiền,
    // luôn được kiểm tra bất kể có mã giảm giá hay không, ở cả 2 kênh pickup/delivery (trừ khi admin tự
    // giới hạn applies_to riêng cho từng chương trình). Hàm THUẦN (không side-effect, không lưu DB) —
    // nơi gọi (OrderService khi tạo đơn thật, CartController khi hiển thị preview) tự quyết dùng kết
    // quả này thế nào.
    //
    // Trả về mảng các phần tử: ['promotion','rule','buy_product','gift_product','granted_quantity','stock_limited'].
    public function resolveGifts(Collection $items, string $channel): array
    {
        $now = now();

        $rules = PromotionBuyXGetY::query()
            ->where('auto_add_gift', true)
            ->whereHas('promotion', function ($q) use ($channel) {
                $q->where('scope', 'buy_x_get_y')
                    ->where('is_active', true)
                    ->whereIn('applies_to', ['all', $channel]);
            })
            ->with(['promotion', 'buyProduct', 'giftProduct'])
            ->get()
            ->filter(fn (PromotionBuyXGetY $rule) => $rule->promotion && $this->isWithinTimeWindow($rule->promotion, $now));

        $results = [];
        foreach ($rules as $rule) {
            $purchasedQty = (int) $items->where('product_id', $rule->buy_product_id)->sum('quantity');
            if ($purchasedQty < $rule->buy_quantity) {
                continue;
            }

            $applications = intdiv($purchasedQty, $rule->buy_quantity);
            if ($rule->max_applications_per_order) {
                $applications = min($applications, $rule->max_applications_per_order);
            }

            $giftQty = $applications * $rule->gift_quantity;
            if ($giftQty <= 0 || !$rule->giftProduct) {
                continue;
            }

            // Kho không đủ -> giảm dần số lượng tặng cho tới khi đủ khả dụng, KHÔNG chặn đơn hàng.
            $stockLimited = false;
            while ($giftQty > 0 && !$rule->giftProduct->hasSufficientMaterials($giftQty)) {
                $giftQty--;
                $stockLimited = true;
            }
            if ($giftQty <= 0) {
                continue;
            }

            $results[] = [
                'promotion' => $rule->promotion,
                'rule' => $rule,
                'buy_product' => $rule->buyProduct,
                'gift_product' => $rule->giftProduct,
                'granted_quantity' => $giftQty,
                'stock_limited' => $stockLimited,
            ];
        }

        // 2 quy tắc khác nhau cùng nhắm 1 buy_product_id -> chỉ giữ quy tắc có giá trị quà cao hơn,
        // tránh tặng trùng 2 sản phẩm cho cùng 1 lượt mua (mục "Xung đột khuyến mãi").
        return collect($results)
            ->groupBy(fn ($r) => $r['buy_product']->id)
            ->map(fn ($group) => $group->sortByDesc(
                fn ($r) => (float) $r['gift_product']->base_price * $r['granted_quantity']
            )->first())
            ->values()
            ->all();
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
