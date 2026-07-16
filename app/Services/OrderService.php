<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Promotion;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private readonly CartPricingService $cartPricing,
        private readonly ShippingQuoteService $shipping,
        private readonly InventoryService $inventory,
    ) {
    }

    public function create(User $user, array $payload, string $paymentMethod): Order
    {
        $existing = Order::withTrashed()->where('user_id', $user->id)
            ->where('idempotency_key', $payload['idempotency_key'])->first();
        if ($existing)
            return $existing;

        $address = UserAddress::query()->where('user_id', $user->id)->find($payload['address_id']);
        if (!$address) {
            throw ValidationException::withMessages(['address_id' => 'Địa chỉ giao hàng không hợp lệ.']);
        }

        // 1. Kiểm tra nhận đơn hàng (orders_enabled)
        $receiveEnabled = (bool) \App\Models\Setting::getValue('orders_enabled', true);
        if (!$receiveEnabled) {
            throw ValidationException::withMessages(['cart' => 'Cửa hàng hiện đang tạm ngưng nhận đơn hàng.']);
        }

        // 2. Kiểm tra giờ mở cửa (store_open_time, store_close_time)
        $open = \App\Models\Setting::getValue('store_open_time', '08:00');
        $close = \App\Models\Setting::getValue('store_close_time', '22:00');
        $now = now()->format('H:i');

        $isOpen = false;
        if ($open < $close) {
            $isOpen = ($now >= $open && $now <= $close);
        } else { // Qua đêm
            $isOpen = ($now >= $open || $now <= $close);
        }

        if (!$isOpen) {
            throw ValidationException::withMessages(['cart' => "Cửa hàng đã đóng cửa. Thời gian hoạt động: từ {$open} đến {$close}."]);
        }

        $cart = Cart::query()->where('user_id', $user->id)->first();
        if (!$cart)
            throw ValidationException::withMessages(['cart' => 'Giỏ hàng của bạn đang trống.']);

        $previewItems = $this->cartPricing->pricedItems($cart);
        $previewSubtotal = $this->cartPricing->subtotal($previewItems);

        $quote = $this->shipping->quote($address, $previewSubtotal, $user);

        // 4. Kiểm tra khoảng cách giao hàng tối đa (shipping_max_distance_km)
        $maxDistance = (float) \App\Models\Setting::getValue('shipping_max_distance_km', ShippingQuoteService::MAX_DELIVERY_KM);
        if ($quote['distance_km'] > $maxDistance) {
            throw ValidationException::withMessages(['address_id' => "Địa chỉ vượt quá phạm vi giao hàng {$maxDistance} km."]);
        }

        return DB::transaction(function () use ($user, $payload, $paymentMethod, $address, $cart, $previewSubtotal, $quote) {
            $lockedCart = Cart::query()->whereKey($cart->id)->where('user_id', $user->id)->lockForUpdate()->firstOrFail();
            $items = $this->cartPricing->pricedItems($lockedCart, true);
            $subtotal = $this->cartPricing->subtotal($items);
            if (abs($subtotal - $previewSubtotal) > 0.01) {
                throw ValidationException::withMessages(['cart' => 'Giá giỏ hàng vừa thay đổi, vui lòng kiểm tra lại.']);
            }

            // Points redemption logic
            $pointsToRedeem = (int) ($payload['points_to_redeem'] ?? 0);
            $pointsDiscount = 0;

            if ($pointsToRedeem > 0) {
                $loyaltyEnabled = \App\Models\Setting::getValue('loyalty_enabled', '1') == '1';
                if (!$loyaltyEnabled) {
                    throw ValidationException::withMessages(['points_to_redeem' => 'Chương trình tích điểm hiện đang tạm đóng.']);
                }

                $minPointsToRedeem = (int) \App\Models\Setting::getValue('loyalty_min_points_to_redeem', 10);
                if ($pointsToRedeem < $minPointsToRedeem) {
                    throw ValidationException::withMessages(['points_to_redeem' => "Số điểm tối thiểu để được quy đổi là {$minPointsToRedeem}."]);
                }

                // Check actual points balance
                $pointsBalance = (int) $user->points;
                if ($pointsToRedeem > $pointsBalance) {
                    throw ValidationException::withMessages(['points_to_redeem' => 'Số điểm quy đổi vượt quá số dư hiện có.']);
                }

                // Check maximum redeem percent
                $maxRedeemPercent = (float) \App\Models\Setting::getValue('loyalty_max_redeem_percent', 100);
                $maxDiscountMoney = $subtotal * ($maxRedeemPercent / 100);

                $pointValue = (float) \App\Models\Setting::getValue('loyalty_point_value', 1);
                $pointsDiscount = $pointsToRedeem * $pointValue;

                if ($pointsDiscount > $maxDiscountMoney) {
                    throw ValidationException::withMessages(['points_to_redeem' => "Số điểm quy đổi vượt quá giới hạn tối đa ({$maxRedeemPercent}%) giá trị đơn hàng."]);
                }
            }

            [$promotion, $couponDiscount] = $this->promotionDiscount($payload['coupon_code'] ?? null, $user, $subtotal);
            $membershipDiscount = match ($user->membership_level) {
                'silver' => round($subtotal * 0.02),
                'gold' => round($subtotal * 0.05),
                'diamond' => round($subtotal * 0.10),
                default => 0,
            };
            $discount = min($subtotal, $couponDiscount + $membershipDiscount + $pointsDiscount);
            $finalAmount = max(0, $subtotal + $quote['shipping_fee'] + $quote['weather_fee'] - $discount);

            do {
                $orderCode = 'HPY-' . strtoupper(bin2hex(random_bytes(4)));
            } while (Order::withTrashed()->where('order_code', $orderCode)->exists());

            $order = Order::create([
                'order_code' => $orderCode,
                'idempotency_key' => $payload['idempotency_key'],
                'user_id' => $user->id,
                'customer_name' => $address->fullname,
                'customer_phone' => $address->phone,
                'delivery_address' => implode(', ', array_filter([$address->specific_address, $address->ward, $address->district, $address->province])),
                'total_amount' => $subtotal,
                'discount_amount' => $discount,
                'final_amount' => $finalAmount,
                'payment_status' => 'unpaid',
                'payment_method' => $paymentMethod,
                'status' => 'pending',
                'coupon_code' => $promotion?->code,
                'promotion_id' => $promotion?->id,
                'delivery_type' => 'delivery',
                'estimated_time' => now()->addMinutes(45),
                'distance_km' => $quote['distance_km'],
                'weather_fee' => $quote['weather_fee'],
                'peak_hour_fee' => 0,
                'shipping_fee' => $quote['shipping_fee'],
                'customer_note' => $payload['note'] ?? null,
            ]);

            foreach ($items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'product_sku' => $item->product->sku,
                    'product_image' => $item->product->image,
                    'size_name' => $item->size_name,
                    'quantity' => (int) $item->quantity,
                    'unit_price' => $item->calculated_unit_price,
                    'sugar_level' => $item->sugar_level,
                    'ice_level' => $item->ice_level,
                    'options' => $item->calculated_toppings->pluck('name')->values()->all(),
                    'note' => null,
                ]);
            }

            $this->inventory->reserveForOrder($order, $items);
            $itemIds = $items->pluck('id');
            DB::table('cart_item_toppings')->whereIn('cart_item_id', $itemIds)->delete();
            DB::table('cart_items')->where('cart_id', $lockedCart->id)->delete();
            if ($promotion)
                $promotion->increment('used_count');

            if ($pointsToRedeem > 0) {
                $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
                $lockedUser->points = max(0, $lockedUser->points - $pointsToRedeem);
                $lockedUser->save();
            }

            return $order->fresh('items');
        }, 3);
    }

    private function promotionDiscount(?string $code, User $user, float $subtotal): array
    {
        if (!filled($code))
            return [null, 0];

        $promotion = Promotion::query()->where('code', strtoupper(trim($code)))->lockForUpdate()->first();
        if (!$promotion)
            throw ValidationException::withMessages(['coupon_code' => 'Mã giảm giá không tồn tại.']);
        $validity = $promotion->checkValidity($user, $subtotal);
        if (!$validity['valid'])
            throw ValidationException::withMessages(['coupon_code' => $validity['message']]);

        $discount = $promotion->type === 'percent'
            ? round($subtotal * ((float) $promotion->value / 100))
            : (float) $promotion->value;
        if ($promotion->max_discount_amount)
            $discount = min($discount, (float) $promotion->max_discount_amount);

        return [$promotion, min($discount, $subtotal)];
    }
}
