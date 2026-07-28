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
        private readonly PromotionService $promotions,
    ) {
    }

    public function create(User $user, array $payload, string $paymentMethod): Order
    {
        $existing = Order::withTrashed()->where('user_id', $user->id)
            ->where('idempotency_key', $payload['idempotency_key'])->first();
        if ($existing)
            return $existing;

        // Đơn 'pickup' (vd: nhân viên lễ tân tạo đơn tại quầy) không cần địa chỉ/tính phí ship.
        $isPickup = ($payload['delivery_type'] ?? 'delivery') === 'pickup';

        // Tách "người thao tác" ($user — chủ giỏ hàng, luôn dùng để khóa Cart) khỏi "khách đứng tên
        // đơn" (order owner — người được tính điểm/hạng thành viên/redeem/địa chỉ giao hàng). Khi lễ
        // tân tạo đơn (POS) cho MỘT khách hàng có tài khoản cụ thể, payload gửi key 'customer_id'
        // tường minh (có thể null = khách vãng lai) để order KHÔNG đứng tên lễ tân. Mọi luồng cũ
        // (khách tự checkout, MoMo...) không gửi key này -> hành vi giữ nguyên y hệt trước đây: order
        // đứng tên & tính điểm/địa chỉ trên chính $user. Phải resolve SỚM (trước khi tìm địa chỉ giao
        // hàng ngay dưới đây) vì đơn giao hàng lễ tân tạo hộ khách phải dùng địa chỉ CỦA KHÁCH, không
        // phải của lễ tân.
        $hasExplicitOrderOwner = array_key_exists('customer_id', $payload);
        $orderOwnerId = $hasExplicitOrderOwner ? $payload['customer_id'] : $user->id;
        if ($orderOwnerId === $user->id) {
            $orderOwner = $user;
        } elseif ($orderOwnerId) {
            $orderOwner = User::query()->where('role', 'customer')->find($orderOwnerId);
            if (!$orderOwner) {
                throw ValidationException::withMessages(['customer_id' => 'Khách hàng không hợp lệ.']);
            }
        } else {
            $orderOwner = null; // Khách vãng lai — không có tài khoản để tính điểm/hạng thành viên/địa chỉ.
        }

        $address = null;
        if (!$isPickup) {
            if (!$orderOwner) {
                throw ValidationException::withMessages(['address_id' => 'Đơn giao hàng cần gắn với một khách hàng có tài khoản để lấy địa chỉ.']);
            }
            $address = UserAddress::query()->where('user_id', $orderOwner->id)->find($payload['address_id'] ?? null);
            if (!$address) {
                throw ValidationException::withMessages(['address_id' => 'Địa chỉ giao hàng không hợp lệ.']);
            }
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

        // Lấy danh sách id sản phẩm đã chọn từ payload (CustomerOrderController đã validate)
        $selectedIds = isset($payload['selected_item_ids']) && is_array($payload['selected_item_ids']) && count($payload['selected_item_ids']) > 0
            ? array_map('intval', $payload['selected_item_ids'])
            : null;

        $previewItems = $this->cartPricing->pricedItems($cart, selectedIds: $selectedIds);
        $previewSubtotal = $this->cartPricing->subtotal($previewItems);

        if ($isPickup) {
            $quote = ['shipping_fee' => 0, 'weather_fee' => 0, 'distance_km' => null];
        } else {
            // Dùng $orderOwner (khách đứng tên đơn) chứ không phải $user (lễ tân) để tính đúng ngưỡng
            // freeship theo hạng thành viên CỦA KHÁCH khi lễ tân tạo đơn giao hàng hộ qua điện thoại.
            $quote = $this->shipping->quote($address, $previewSubtotal, $orderOwner);

            // 4. Kiểm tra khoảng cách giao hàng tối đa (shipping_max_distance_km)
            $maxDistance = (float) \App\Models\Setting::getValue('shipping_max_distance_km', ShippingQuoteService::MAX_DELIVERY_KM);
            if ($quote['distance_km'] > $maxDistance) {
                throw ValidationException::withMessages(['address_id' => "Địa chỉ vượt quá phạm vi giao hàng {$maxDistance} km."]);
            }
        }

        return DB::transaction(function () use ($user, $payload, $paymentMethod, $address, $cart, $previewSubtotal, $quote, $isPickup, $selectedIds, $orderOwner, $orderOwnerId) {
            $lockedCart = Cart::query()->whereKey($cart->id)->where('user_id', $user->id)->lockForUpdate()->firstOrFail();
            $items = $this->cartPricing->pricedItems($lockedCart, true, $selectedIds);
            $subtotal = $this->cartPricing->subtotal($items);
            $totalQuantity = (int) $items->sum('quantity');
            if (abs($subtotal - $previewSubtotal) > 0.01) {
                throw ValidationException::withMessages(['cart' => 'Giá giỏ hàng vừa thay đổi, vui lòng kiểm tra lại.']);
            }

            // Points redemption logic
            $pointsToRedeem = (int) ($payload['points_to_redeem'] ?? 0);
            $pointsDiscount = $this->resolvePointsDiscount($pointsToRedeem, $orderOwner, $subtotal);

            // Lễ tân có thể nhập mã khuyến mãi thủ công cho đơn tại quầy (thay vì chỉ tự động chọn) —
            // dùng $orderOwner (khách đứng tên đơn) chứ không phải $user (lễ tân) để kiểm tra đúng
            // hạng thành viên/giới hạn "mỗi người 1 lần" của CHÍNH KHÁCH, không phải tài khoản lễ tân.
            // Tự động chọn mã CHỈ áp dụng cho đơn tại quầy (giữ nguyên hành vi cũ) — đơn giao hàng chỉ
            // dùng mã khách tự nhập. Ba loại giảm giá tiền (toàn đơn/sản phẩm/danh mục) loại trừ nhau,
            // PromotionService tự chọn mã lợi nhất; số tiền giảm được tính đúng theo phạm vi (chỉ trên
            // các dòng sản phẩm/danh mục khớp, không phải luôn toàn đơn — xem PromotionService::eligibleSubtotal).
            $channel = $isPickup ? 'pickup' : 'delivery';
            $manualCode = filled($payload['coupon_code'] ?? null) ? $payload['coupon_code'] : null;
            if ($manualCode === null && !$isPickup) {
                $promotion = null;
                $couponDiscount = 0;
            } else {
                $result = $this->promotions->resolveBestDiscount(
                    $items, $subtotal, $orderOwner, $channel, $totalQuantity, $manualCode, lock: true
                );
                $promotion = $result['promotion'];
                $couponDiscount = $result['discount'];
            }

            // Mua X tặng Y — ĐỘC LẬP với giảm giá tiền ở trên, được cộng thêm (không cạnh tranh vì bản
            // chất khác nhau: tặng vật lý chứ không giảm tiền). Tự động áp dụng ở CẢ pickup lẫn
            // delivery, không cần mã, không cần khách chọn. Tính lại NGAY TRONG transaction bằng đúng
            // $items đã lock (không tin số đã preview trước transaction) để không ai lợi dụng request
            // chỉnh sửa mà nhận quà sai điều kiện.
            $gifts = $this->promotions->resolveGifts($items, $channel);

            $membershipDiscount = $this->membershipDiscount($orderOwner, $subtotal);
            $discount = min($subtotal, $couponDiscount + $membershipDiscount + $pointsDiscount);
            $finalAmount = max(0, $subtotal + $quote['shipping_fee'] + $quote['weather_fee'] - $discount);

            do {
                $orderCode = 'HPY-' . strtoupper(bin2hex(random_bytes(4)));
            } while (Order::withTrashed()->where('order_code', $orderCode)->exists());

            $order = Order::create([
                'order_code' => $orderCode,
                'idempotency_key' => $payload['idempotency_key'],
                'user_id' => $orderOwnerId,
                // Lễ tân/admin thực sự xử lý đơn (POS) — tách biệt với user_id (khách đứng tên đơn)
                // để báo cáo sau này biết ai bán. Áp dụng cho MỌI đơn do staff/admin tạo qua
                // POS, không chỉ riêng đơn pickup (Giai đoạn 5: lễ tân cũng tạo được đơn giao hàng hộ
                // khách gọi điện) — null cho khách tự checkout (role=customer).
                'created_by' => in_array($user->role, ['staff', 'admin'], true) ? $user->id : null,
                // Dùng array_key_exists thay vì '??' — với khách vãng lai, controller gửi tường minh
                // 'customer_phone' => null (không phải bỏ trống key); '??' sẽ coi null là "chưa gửi"
                // và âm thầm fallback về SĐT của lễ tân, sai hoàn toàn ý định "khách vãng lai".
                'customer_name' => $isPickup ? (array_key_exists('customer_name', $payload) ? $payload['customer_name'] : $user->name) : $address->fullname,
                'customer_phone' => $isPickup ? (array_key_exists('customer_phone', $payload) ? $payload['customer_phone'] : $user->phone) : $address->phone,
                'delivery_address' => $isPickup ? null : implode(', ', array_filter([$address->specific_address, $address->ward, $address->district, $address->province])),
                // Snapshot tọa độ GPS tại thời điểm đặt hàng để nhân viên vận chuyển mở đúng điểm trên bản đồ
                // thay vì tìm theo chuỗi địa chỉ text (dễ ra nhiều kết quả trùng tên đường/khu vực).
                'delivery_latitude' => $isPickup ? null : $address->latitude,
                'delivery_longitude' => $isPickup ? null : $address->longitude,
                'total_amount' => $subtotal,
                'discount_amount' => $discount,
                'final_amount' => $finalAmount,
                'payment_status' => 'unpaid',
                'payment_method' => $paymentMethod,
                'status' => 'pending',
                'coupon_code' => $promotion?->code,
                'promotion_id' => $promotion?->id,
                'delivery_type' => $isPickup ? 'pickup' : 'delivery',
                // Chỉ có ý nghĩa cho đơn pickup (tại quầy/mang đi) — đơn delivery luôn null.
                'pickup_mode' => $isPickup ? ($payload['pickup_mode'] ?? 'dine_in') : null,
                'estimated_time' => now()->addMinutes($isPickup ? 15 : 45),
                'distance_km' => $quote['distance_km'],
                'weather_fee' => $quote['weather_fee'],
                'peak_hour_fee' => 0,
                'shipping_fee' => $quote['shipping_fee'],
                'customer_note' => $payload['note'] ?? null,
                // Lưu lại số điểm đã dùng (nếu có) để OrderWorkflowService::transition() hoàn đúng
                // số điểm này cho khách khi đơn bị hủy sau đó — trước đây chỉ trừ điểm ngay lúc tạo
                // đơn nhưng không lưu lại đã dùng bao nhiêu, khiến hủy đơn không hoàn được điểm.
                'points_redeemed' => $pointsToRedeem,
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

            // Vật chất hóa quà tặng Mua X tặng Y THÀNH dòng OrderItem thật, unit_price=0, đánh dấu
            // is_gift để không tính vào doanh thu. Không lưu quà vào cart_items — tránh việc khách tự
            // sửa/xóa/tăng số lượng quà qua các endpoint /cart/update, /cart/remove hiện có (các
            // endpoint đó không biết khái niệm "quà tặng").
            $giftInventoryItems = collect();
            foreach ($gifts as $gift) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $gift['gift_product']->id,
                    'product_name' => $gift['gift_product']->name,
                    'product_sku' => $gift['gift_product']->sku,
                    'product_image' => $gift['gift_product']->image,
                    'size_name' => null,
                    'quantity' => $gift['granted_quantity'],
                    'unit_price' => 0,
                    'sugar_level' => null,
                    'ice_level' => null,
                    'options' => [],
                    'note' => 'Quà tặng: Mua ' . $gift['rule']->buy_quantity . ' ' . $gift['buy_product']->name
                        . ' tặng ' . $gift['rule']->gift_quantity . ' ' . $gift['gift_product']->name,
                    'is_gift' => true,
                    'source_promotion_id' => $gift['promotion']->id,
                ]);
                // reserveForOrder() chỉ cần product_id+quantity — quà tặng vẫn trừ kho như hàng bán thật.
                $giftInventoryItems->push((object) ['product_id' => $gift['gift_product']->id, 'quantity' => $gift['granted_quantity']]);
            }

            $this->inventory->reserveForOrder($order, $items->concat($giftInventoryItems));
            // CHỈ xóa những cart_items đã được đưa vào đơn hàng này.
            // Các sản phẩm còn lại trong giỏ (không được chọn) sẽ được giữ nguyên.
            $itemIds = $items->pluck('id');
            DB::table('cart_item_toppings')->whereIn('cart_item_id', $itemIds)->delete();
            DB::table('cart_items')->whereIn('id', $itemIds)->delete();
            if ($promotion)
                $promotion->increment('used_count');
            // Mỗi chương trình Mua X tặng Y đã thực sự tặng quà trong đơn này cũng tính 1 lượt dùng
            // (dùng collect()->unique() phòng trường hợp hiếm gặp nhiều quy tắc trùng promotion_id).
            foreach (collect($gifts)->pluck('promotion')->unique('id') as $giftPromotion) {
                $giftPromotion->increment('used_count');
            }

            if ($pointsToRedeem > 0) {
                // $orderOwner chắc chắn khác null ở đây (đã chặn ở trên nếu khách vãng lai).
                $lockedOwner = User::query()->lockForUpdate()->findOrFail($orderOwner->id);
                $lockedOwner->points = max(0, $lockedOwner->points - $pointsToRedeem);
                $lockedOwner->save();
            }

            return $order->fresh('items');
        }, 3);
    }

    /**
     * Xem trước khuyến mãi tự động sẽ áp dụng cho một đơn tại quầy (nếu có) — dùng cho giao diện POS
     * hiển thị tổng tiền thực tế TRƯỚC khi lễ tân bấm "Tạo đơn", không cần tạo đơn thật mới biết được.
     * $items phải là kết quả CartPricingService::pricedItems() (có product + calculated_unit_price)
     * để tính đúng số giảm cho khuyến mãi phạm vi sản phẩm/danh mục.
     */
    public function previewAutoPromotion(\Illuminate\Support\Collection $items, float $subtotal, int $totalQuantity = 0): array
    {
        return $this->promotions->resolveBestDiscount($items, $subtotal, null, 'pickup', $totalQuantity);
    }

    /**
     * Xem trước 1 mã khuyến mãi NHẬP TAY (không phải tự động chọn) cho đơn tại quầy — dùng cho POS
     * hiển thị kết quả TRƯỚC khi tạo đơn thật. Dùng chung PromotionService với lúc tạo đơn thật nên
     * luôn khớp giữa preview và kết quả tạo đơn.
     */
    public function previewManualCoupon(
        string $code,
        \Illuminate\Support\Collection $items,
        ?User $orderOwner,
        float $subtotal,
        ?string $deliveryType = null,
        int $totalQuantity = 0
    ): array {
        return $this->promotions->resolveBestDiscount(
            $items, $subtotal, $orderOwner, $deliveryType ?? 'pickup', $totalQuantity, $code, lock: true
        );
    }

    /**
     * Xem trước số tiền giảm từ việc dùng điểm tích lũy — dùng CHUNG logic/hạn mức với
     * resolvePointsDiscount() (nguồn tính toán duy nhất) để preview trên giao diện POS luôn khớp
     * với kết quả tạo đơn thật, không tự tính riêng một lần nữa dễ bị lệch.
     */
    public function previewPointsDiscount(int $pointsToRedeem, ?User $orderOwner, float $subtotal): array
    {
        try {
            return ['discount' => $this->resolvePointsDiscount($pointsToRedeem, $orderOwner, $subtotal), 'error' => null];
        } catch (ValidationException $e) {
            return ['discount' => 0, 'error' => collect($e->errors())->flatten()->first()];
        }
    }

    /**
     * Kiểm tra hạn mức/số dư điểm tích lũy và trả về số tiền được giảm tương ứng. Ném
     * ValidationException nếu không hợp lệ (khách vãng lai, chương trình tạm đóng, dưới mức tối
     * thiểu, vượt số dư, hoặc vượt trần % giá trị đơn).
     */
    private function resolvePointsDiscount(int $pointsToRedeem, ?User $orderOwner, float $subtotal): float
    {
        if ($pointsToRedeem <= 0) {
            return 0;
        }

        if (!$orderOwner) {
            throw ValidationException::withMessages(['points_to_redeem' => 'Không thể dùng điểm tích lũy cho khách vãng lai.']);
        }

        $loyaltyEnabled = \App\Models\Setting::getValue('loyalty_enabled', '1') == '1';
        if (!$loyaltyEnabled) {
            throw ValidationException::withMessages(['points_to_redeem' => 'Chương trình tích điểm hiện đang tạm đóng.']);
        }

        $minPointsToRedeem = (int) \App\Models\Setting::getValue('loyalty_min_points_to_redeem', 10);
        if ($pointsToRedeem < $minPointsToRedeem) {
            throw ValidationException::withMessages(['points_to_redeem' => "Số điểm tối thiểu để được quy đổi là {$minPointsToRedeem}."]);
        }

        $pointsBalance = (int) $orderOwner->points;
        if ($pointsToRedeem > $pointsBalance) {
            throw ValidationException::withMessages(['points_to_redeem' => 'Số điểm quy đổi vượt quá số dư hiện có.']);
        }

        $maxRedeemPercent = (float) \App\Models\Setting::getValue('loyalty_max_redeem_percent', 100);
        $maxDiscountMoney = $subtotal * ($maxRedeemPercent / 100);

        $pointValue = (float) \App\Models\Setting::getValue('loyalty_point_value', 1);
        $pointsDiscount = $pointsToRedeem * $pointValue;

        if ($pointsDiscount > $maxDiscountMoney) {
            throw ValidationException::withMessages(['points_to_redeem' => "Số điểm quy đổi vượt quá giới hạn tối đa ({$maxRedeemPercent}%) giá trị đơn hàng."]);
        }

        return $pointsDiscount;
    }

    /**
     * Giảm giá theo hạng thành viên (silver/gold/diamond) — dùng chung cho lúc tạo đơn thật lẫn
     * preview POS để 2 nơi luôn khớp nhau.
     */
    public function membershipDiscount(?User $orderOwner, float $subtotal): float
    {
        return match ($orderOwner?->membership_level) {
            'silver' => round($subtotal * 0.02),
            'gold' => round($subtotal * 0.05),
            'diamond' => round($subtotal * 0.10),
            default => 0,
        };
    }
}
