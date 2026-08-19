<?php

namespace App\Services;

use App\Models\Promotion;
use App\Models\PromotionCombo;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PromotionService
{
    // cổng tiếp nhận và xử lý giải quyết khuyến mãi
    public function resolveBestDiscount(Collection $items, float $subtotal, ?User $user, string $channel, int $totalQuantity, ?string $manualCode = null, bool $lock = false): array
    {
        if (filled($manualCode)) {
            // xử lý mã user tự nhập
            return $this->resolveManualCode($manualCode, $items, $subtotal, $user, $channel, $totalQuantity, $lock);
        }

        return ['promotion' => null, 'discount' => 0.0, 'gifts' => []];
    }

    // Kiểm tra và xử lý áp dụng mã giảm giá
    private function resolveManualCode(string $code, Collection $items, float $subtotal, ?User $user, string $channel, int $totalQuantity, bool $lock): array
    {
        $query = Promotion::query()->where('code', strtoupper(trim($code)));
        if ($lock) {
            // Khóa dòng trong database để tránh xung đột khi nhiều
            $query->lockForUpdate();
        }
        $promotion = $query->first();
        if (!$promotion) {
            throw ValidationException::withMessages(['coupon_code' => 'Mã giảm giá không tồn tại.']);
        }
        if (!in_array($promotion->scope, ['order', 'product', 'category', 'combo'], true)) {
            throw ValidationException::withMessages(['coupon_code' => 'Mã giảm giá không hợp lệ.']);
        }
        // Kiểm tra điều kiện thời gian, số lượt dùng, đơn hàng
        $validity = $promotion->checkValidity($user, $subtotal, $channel, $totalQuantity);
        if (!$validity['valid']) {
            throw ValidationException::withMessages(['coupon_code' => $validity['message']]);
        }
        // Combo có cách tính riêng mua đủ tổ hợp món mới được
        if ($promotion->scope === 'combo') {
            return $this->resolveComboCode($promotion, $items);
        }
        $promotion->loadMissing(['products', 'categories']);
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
        return [
            'promotion' => $promotion,
            'discount' => $this->calculateDiscount($promotion, $items), //  tính số tiền chiết khấu thực tế
            'gifts' => [],
        ];
    }

    // xử lý các chương trình khuyến mãi combo
    private function resolveComboCode(Promotion $promotion, Collection $items): array
    {
        $promotion->loadMissing(['combo.giftProduct', 'comboItems.product']);
        $combo = $promotion->combo;

        if (!$combo || $promotion->comboItems->isEmpty()) {
            throw ValidationException::withMessages(['coupon_code' => 'Mã combo này chưa được cấu hình đầy đủ.']);
        }
        // Đếm số lượng từng món đang có trong giỏ để xem ghép
        $inCart = [];
        $priceByProduct = [];
        foreach ($items as $item) {
            $inCart[$item->product_id] = ($inCart[$item->product_id] ?? 0) + (int) $item->quantity;
            if (!isset($priceByProduct[$item->product_id])) {
                $priceByProduct[$item->product_id] = (float) ($item->calculated_unit_price ?? $item->unit_price);
            }
        }
        $applications = $this->comboApplications($promotion, $inCart);//// Tính số lần tối đa mà giỏ hàng đủ điều kiện áp dụng Combo
        if ($combo->max_applications_per_order) {
            $applications = min($applications, (int) $combo->max_applications_per_order);
        }
        // Chưa mua đủ tổ hợp món -> báo rõ cần mua gì thay vì
        if ($applications <= 0) {
            $required = $promotion->comboItems
                ->map(fn($ci) => $ci->quantity . ' ' . ($ci->product->name ?? 'sản phẩm'))
                ->implode(' + ');
            throw ValidationException::withMessages([
                'coupon_code' => "Mã này yêu cầu mua {$required}. Giỏ hàng của bạn chưa đủ.",
            ]);
        }
        $discount = 0.0;
        if ($combo->hasDiscount()) {
            $eligibleSubtotal = 0.0;
            foreach ($promotion->comboItems as $comboItem) {
                $eligibleSubtotal += ($priceByProduct[$comboItem->product_id] ?? 0) * $comboItem->quantity * $applications;
            }
            $discount = $this->comboDiscountAmount($combo, $eligibleSubtotal, $applications);
        }
        $gifts = [];
        if ($combo->hasGift() && $combo->giftProduct) {
            $giftQty = $applications * (int) $combo->gift_quantity;
            if ($giftQty > 0) {
                $gifts[] = [
                    'promotion' => $promotion,
                    'applications' => $applications,
                    'combo_items' => $promotion->comboItems,
                    'gift_product' => $combo->giftProduct,
                    'granted_quantity' => $giftQty,
                ];
            }
        }

        return ['promotion' => $promotion, 'discount' => $discount, 'gifts' => $gifts];
    }

    // Tính số tiền giảm giá thực tế của khuyến mãi (% hoặc số tiền)
    public function calculateDiscount(Promotion $promotion, Collection $items): float
    {
        // Lấy tổng số tiền của các sản phẩm đủ điều kiện giảm
        $eligibleSubtotal = $this->eligibleSubtotal($promotion, $items);
        if ($eligibleSubtotal <= 0) {
            return 0.0;
        }
        if ($promotion->type === 'percent') {
            $discount = round($eligibleSubtotal * ((float) $promotion->value / 100));
        } else {
            $discount = (float) $promotion->value;
        }
        if ($promotion->max_discount_amount) {
            $discount = min($discount, (float) $promotion->max_discount_amount);
        }
        return min($discount, $eligibleSubtotal);
    }

    //  tính tổng số tiền của những sản phẩm thực sự đủ điều kiện được giảm giá
    public function eligibleSubtotal(Promotion $promotion, Collection $items): float
    {
        return match ($promotion->scope) {
            // Gọi hàm tính tổng tiền riêng theo sản phẩm
            'product' => $this->eligibleSubtotalForProducts($promotion, $items),
            'category' => $this->eligibleSubtotalForCategories($promotion, $items),
            default => (float) $items->sum(fn($item) => (float) $item->calculated_unit_price * (int) $item->quantity),
        };
    }

    // TÍNH TỔNG TIỀN DS SP
    private function eligibleSubtotalForProducts(Promotion $promotion, Collection $items): float
    {
        $productIds = $promotion->products->pluck('id')->all();
        return (float) $items
            ->filter(fn($item) => in_array($item->product_id, $productIds, true))
            ->sum(fn($item) => (float) $item->calculated_unit_price * (int) $item->quantity);
    }

    // TÍNH TỔNG TIỀN RIÊNG CHO SP THUỘC DANH MỤC dc giam gia
    private function eligibleSubtotalForCategories(Promotion $promotion, Collection $items): float
    {
        $categoryIds = $promotion->categories->pluck('id')->all();
        return (float) $items
            ->filter(fn($item) => $item->product && in_array($item->product->category_id, $categoryIds, true))
            ->sum(fn($item) => (float) $item->calculated_unit_price * (int) $item->quantity);
    }

    // Các mã COMBO mà giỏ hàng hiện tại đã mua ĐỦ tổ hợp
    public function applicableCombos(Collection $items, string $channel): Collection
    {
        $now = now();
        $inCart = [];
        foreach ($items as $item) {
            $inCart[$item->product_id] = ($inCart[$item->product_id] ?? 0) + (int) $item->quantity;
        }

        return Promotion::query()
            ->where('scope', 'combo')
            ->where('is_active', true)
            ->whereIn('applies_to', ['all', $channel])
            ->where('requires_staff_verification', false)
            ->where(function ($q) {
                $q->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit');
            })
            ->whereNotNull('code')
            ->with(['combo.giftProduct', 'comboItems.product'])
            ->get()
            ->filter(fn(Promotion $p) => $p->combo && $p->comboItems->isNotEmpty() && $this->isWithinTimeWindow($p, $now))
            ->filter(fn(Promotion $p) => $this->comboApplications($p, $inCart) > 0)
            ->values();
    }

    // Tính số lần tối đa mà giỏ hàng đủ điều kiện áp dụng Combo
    private function comboApplications(Promotion $promotion, array $remaining): int
    {
        $applications = null;
        foreach ($promotion->comboItems as $comboItem) {
            $possible = intdiv($remaining[$comboItem->product_id] ?? 0, max(1, $comboItem->quantity));

            if (is_null($applications)) {
                $applications = $possible;
            } else {
                $applications = min($applications, $possible);
            }
        }
        return (int) ($applications ?? 0);
    }

    // Tính toán số tiền giảm giá chính xác của một Khuyến mãi combo
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

    // Kiểm tra khuyến mãi có nằm trong khung giờ / ngày hợp lệ không
    private function isWithinTimeWindow(Promotion $promotion, $now): bool
    {
        if ($promotion->is_recurring) {
            $nowStr = $now->format('H:i:s');
            // dayOfWeekIso: Lấy thứ trong tuần, 1 là Thứ 2, 7 là Chủ nhật
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
