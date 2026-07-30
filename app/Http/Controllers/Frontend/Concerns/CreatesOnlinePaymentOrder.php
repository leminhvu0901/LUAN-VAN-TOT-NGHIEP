<?php

namespace App\Http\Controllers\Frontend\Concerns;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CartItemTopping;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Dùng chung cho MỌI cổng thanh toán online (MoMo, VNPay, ...): kiểm tra đăng nhập/giờ mở
 * cửa/giỏ hàng, tính subtotal + phí ship + phí thời tiết + giảm giá (coupon/hạng thành viên), tạo
 * Order + OrderItem trong 1 DB transaction. Tách ra khỏi MomoController để tránh có 2 bản sao logic
 * tính giá phải sửa đồng thời mỗi khi đổi công thức freeship/giảm giá.
 *
 * Class dùng trait này bắt buộc phải có sẵn `HandlesCheckoutResponse` (checkoutError/checkoutRedirect)
 * và property `configValid` (bool) từ constructor riêng của từng cổng thanh toán.
 */
trait CreatesOnlinePaymentOrder
{
    /**
     * Trả về Order vừa tạo (đã commit) khi thành công, hoặc 1 Response lỗi (đã build sẵn qua
     * checkoutError/checkoutRedirect) khi thất bại — nơi gọi PHẢI kiểm tra `instanceof Order` trước
     * khi coi là thành công.
     */
    protected function createPendingOrderForOnlinePayment(Request $request, string $paymentMethod)
    {
        if (!Auth::check()) {
            return $this->checkoutRedirect($request, route('login'));
        }

        if (!$this->configValid) {
            return $this->checkoutError($request, 'Chưa cấu hình cổng thanh toán cho môi trường chính thức. Vui lòng liên hệ quản trị viên.');
        }

        $userId = Auth::id();

        // ── 0. Kiểm tra giờ hoạt động ─────────────────────────────────────────
        $now = now()->timezone('Asia/Ho_Chi_Minh');
        $timeString = $now->format('H:i:s');
        if ($timeString < '07:00:00' || $timeString >= '23:00:00') {
            return $this->checkoutError($request, 'Cửa hàng chỉ cho phép đặt hàng từ 07:00 đến 23:00. Hiện tại cửa hàng đã đóng cửa.');
        }

        // ── 1. Validate ────────────────────────────────────────────────────────
        $request->validate([
            'address_id' => 'required',
            'payment_method' => 'required|in:' . $paymentMethod,
            'coupon_code' => 'nullable|string',
            'note' => 'nullable|string|max:500',
            'distance_km' => 'required|numeric|min:0|max:10',
            'weather_fee' => 'nullable|numeric|min:0',
        ], [
            'distance_km.max' => 'Rất tiếc, địa chỉ của bạn quá xa (vượt quá 10km) nên cửa hàng không thể giao hàng.',
        ]);

        // ── 2. Lấy địa chỉ ────────────────────────────────────────────────────
        $address = UserAddress::query()
            ->where('id', $request->input('address_id'))
            ->where('user_id', $userId)
            ->first();

        if (!$address) {
            return $this->checkoutError($request, 'Địa chỉ giao hàng không hợp lệ.');
        }

        // ── 3. Xóa đơn cùng cổng thanh toán còn pending/unpaid (user đã hủy trước đó) ─────
        $oldPendingOrders = Order::query()
            ->where('user_id', $userId)
            ->where('payment_method', $paymentMethod)
            ->where('payment_status', 'unpaid')
            ->where('status', 'pending')
            ->get();

        foreach ($oldPendingOrders as $oldOrder) {
            OrderItem::query()->where('order_id', $oldOrder->id)->delete();
            if ($oldOrder->promotion_id) {
                Promotion::query()->where('id', $oldOrder->promotion_id)->decrement('used_count');
            }
            Order::query()->where('id', $oldOrder->id)->delete();
        }

        // ── 4. Lấy giỏ hàng ───────────────────────────────────────────────────
        $cart = Cart::query()->where('user_id', $userId)->first();
        if (!$cart) {
            return $this->checkoutError($request, 'Giỏ hàng của bạn đang trống.');
        }

        $cartItemQuery = CartItem::query()->where('cart_id', $cart->id);

        // Lọc theo danh sách đã chọn (nếu có trong session)
        $selectedIds = session('selected_cart_item_ids');
        if (!empty($selectedIds)) {
            $cartItemQuery->whereIn('id', $selectedIds);
        }

        $cartItems = $cartItemQuery->get();
        if ($cartItems->isEmpty()) {
            return $this->checkoutError($request, 'Giỏ hàng của bạn đang trống.');
        }

        // Kiểm tra sản phẩm hết hàng trong giỏ
        $productIds = $cartItems->pluck('product_id')->toArray();
        $outOfStockProducts = Product::query()
            ->whereIn('id', $productIds)
            ->where('is_active', 0)
            ->pluck('name')
            ->toArray();
        if (!empty($outOfStockProducts)) {
            $names = implode(', ', $outOfStockProducts);
            return $this->checkoutError($request, 'Sản phẩm đã hết hàng: ' . $names . '. Vui lòng xóa khỏi giỏ hàng trước khi đặt.');
        }

        // ── 5. Tính tiền ───────────────────────────────────────────────────────
        $itemIds = $cartItems->pluck('id');
        $cartToppings = CartItemTopping::query()
            ->join('toppings', 'cart_item_toppings.topping_id', '=', 'toppings.id')
            ->whereIn('cart_item_toppings.cart_item_id', $itemIds)
            ->select('cart_item_toppings.cart_item_id', 'toppings.name', 'toppings.price')
            ->get();

        $subtotal = 0;
        foreach ($cartItems as $item) {
            $subtotal += $item->unit_price * $item->quantity;
        }

        // Tính phí vận chuyển và phí thời tiết dựa trên hạng thành viên:
        // - Mới: freeship từ 150.000đ
        // - Bạc: freeship từ 120.000đ
        // - Vàng: freeship từ 90.000đ
        // - Kim Cương: luôn luôn freeship
        $freeShipThreshold = 150000;
        $user = Auth::user();
        if ($user) {
            switch ($user->membership_level) {
                case 'silver':
                    $freeShipThreshold = 120000;
                    break;
                case 'gold':
                    $freeShipThreshold = 90000;
                    break;
                case 'diamond':
                    $freeShipThreshold = 0;
                    break;
            }
        }

        $distanceKm = floatval($request->input('distance_km'));
        $shippingFee = $subtotal >= $freeShipThreshold ? 0 : round($distanceKm * 3000);

        // Phí thời tiết (chỉ áp dụng nếu đơn hàng không được freeship)
        $weatherFee = $subtotal >= $freeShipThreshold ? 0 : floatval($request->input('weather_fee', 0));
        $peakHourFee = 0;

        // ── 6. Xử lý coupon ────────────────────────────────────────────────────
        $discountAmount = 0;
        $couponCode = null;
        $promotionId = null;

        $inputCoupon = trim($request->input('coupon_code'));
        if (!empty($inputCoupon)) {
            $coupon = Promotion::query()->where('code', strtoupper($inputCoupon))->first();
            // Đơn online ở luồng này luôn là đơn giao hàng -> mã chỉ dành riêng cho "Tại quầy" không được áp dụng.
            if ($coupon && $coupon->is_active && $coupon->applies_to !== 'pickup' && (!$coupon->usage_limit || $coupon->used_count < $coupon->usage_limit)) {
                $isValidDate = true;
                if ($coupon->start_at && now() < $coupon->start_at)
                    $isValidDate = false;
                if ($coupon->end_at && now() > $coupon->end_at)
                    $isValidDate = false;

                if ($isValidDate && (!$coupon->min_order_amount || $subtotal >= $coupon->min_order_amount)) {
                    $hasUsed = Order::query()
                        ->where('promotion_id', $coupon->id)
                        ->where('user_id', $userId)
                        ->where('status', '!=', 'cancelled')
                        ->exists();

                    if (!$hasUsed) {
                        $couponCode = $coupon->code;
                        $promotionId = $coupon->id;
                        if ($coupon->type === 'percent') {
                            $discountAmount = round($subtotal * ($coupon->value / 100));
                            if ($coupon->max_discount_amount && $discountAmount > $coupon->max_discount_amount) {
                                $discountAmount = $coupon->max_discount_amount;
                            }
                        } else {
                            $discountAmount = $coupon->value;
                        }
                        if ($discountAmount > $subtotal)
                            $discountAmount = $subtotal;
                    }
                }
            }
        }

        // Chiết khấu trực tiếp theo hạng thành viên (Bạc: 2%, Vàng: 5%, Kim Cương: 10%)
        $membershipDiscount = 0;
        if ($user) {
            switch ($user->membership_level) {
                case 'silver':
                    $membershipDiscount = round($subtotal * 0.02);
                    break;
                case 'gold':
                    $membershipDiscount = round($subtotal * 0.05);
                    break;
                case 'diamond':
                    $membershipDiscount = round($subtotal * 0.10);
                    break;
            }
        }

        // Tổng tiền chiết khấu = Giảm giá Coupon + Giảm giá hạng thành viên
        $discountAmount += $membershipDiscount;

        $finalAmount = max(0, $subtotal + $shippingFee + $weatherFee + $peakHourFee - $discountAmount);
        $fullAddress = $address->specific_address . ', ' . $address->ward . ', ' . $address->district . ', ' . $address->province;
        $estimatedTime = now()->addMinutes(45);
        $orderCode = 'HPY-' . strtoupper(bin2hex(random_bytes(4)));

        // ── 7. Lưu đơn hàng + order items vào DB (CHƯA xóa giỏ hàng) ──────────
        DB::beginTransaction();
        try {
            $orderId = Order::query()->insertGetId([
                'order_code' => $orderCode,
                'user_id' => $userId,
                'customer_name' => $address->fullname,
                'customer_phone' => $address->phone,
                'delivery_address' => $fullAddress,
                'delivery_latitude' => $address->latitude,
                'delivery_longitude' => $address->longitude,
                'total_amount' => $subtotal,
                'discount_amount' => $discountAmount,
                'final_amount' => $finalAmount,
                'payment_status' => 'unpaid',
                'payment_method' => $paymentMethod,
                'status' => 'pending',
                'coupon_code' => $couponCode,
                'promotion_id' => $promotionId,
                'delivery_type' => 'delivery',
                'estimated_time' => $estimatedTime,
                'distance_km' => $distanceKm,
                'weather_fee' => $weatherFee,
                'peak_hour_fee' => $peakHourFee,
                'shipping_fee' => $shippingFee,
                'customer_note' => $request->input('note'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($cartItems as $item) {
                $toppings = $cartToppings->where('cart_item_id', $item->id)->pluck('name')->toArray();
                $toppingsList = json_encode($toppings, JSON_UNESCAPED_UNICODE);

                OrderItem::query()->insert([
                    'order_id' => $orderId,
                    'product_id' => $item->product_id,
                    'size_name' => $item->size_name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'sugar_level' => $item->sugar_level,
                    'ice_level' => $item->ice_level,
                    'options' => $toppingsList,
                    'note' => null,
                ]);
            }

            // Tăng used_count coupon (nếu có)
            if ($promotionId) {
                Promotion::query()->where('id', $promotionId)->increment('used_count');
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->checkoutError($request, 'Có lỗi khi tạo đơn hàng: ' . $e->getMessage());
        }

        return Order::query()->find($orderId);
    }

    /**
     * Hủy 1 đơn vừa tạo khi bước gọi cổng thanh toán online (sau createPendingOrderForOnlinePayment)
     * thất bại — giỏ hàng khách vẫn còn nguyên (chưa từng bị xóa).
     */
    protected function rollbackPendingOnlinePaymentOrder(Order $order): void
    {
        $orderId = $order->id;
        $promotionId = $order->promotion_id;

        OrderItem::query()->where('order_id', $orderId)->delete();
        Order::query()->where('id', $orderId)->delete();
        if ($promotionId) {
            Promotion::query()->where('id', $promotionId)->decrement('used_count');
        }
    }
}
