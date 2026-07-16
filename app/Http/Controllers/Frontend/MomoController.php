<?php

namespace App\Http\Controllers\Frontend;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MomoController
{
    // ─── Lấy cấu hình MoMo từ .env ───────────────────────────────────────────
    private string $partnerCode;
    private string $accessKey;
    private string $secretKey;
    private string $endpoint;

    public function __construct()
    {
        $this->partnerCode = env('MOMO_PARTNER_CODE', 'MOMOBKUN20180529');
        $this->accessKey = env('MOMO_ACCESS_KEY', 'klm05TvNBzhg7h7j');
        $this->secretKey = env('MOMO_SECRET_KEY', 'at67qH6mk8w5Y1nAyMoYKMWACiEi2bsa');
        $this->endpoint = env('MOMO_ENDPOINT', 'https://test-payment.momo.vn/v2/gateway/api/create');
    }

    /**
     * Được gọi khi khách chọn MoMo và bấm "Đặt hàng".
     * Tạo đơn hàng trong DB (payment_status = unpaid) rồi điều hướng sang cổng MoMo.
     * Theo mẫu: php/PayMoMo/init_payment.php từ github.com/momo-wallet/payment
     */
    public function createPayment(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $userId = Auth::id();

        // ── 0. Kiểm tra giờ hoạt động ─────────────────────────────────────────
        $now = now()->timezone('Asia/Ho_Chi_Minh');
        $timeString = $now->format('H:i:s');
        if ($timeString < '07:00:00' || $timeString >= '23:00:00') {
            return redirect()->back()->with('error', 'Cửa hàng chỉ cho phép đặt hàng từ 07:00 đến 23:00. Hiện tại cửa hàng đã đóng cửa.')->withInput();
        }

        // ── 1. Validate ────────────────────────────────────────────────────────
        $request->validate([
            'address_id' => 'required',
            'payment_method' => 'required|in:momo',
            'coupon_code' => 'nullable|string',
            'note' => 'nullable|string|max:500',
            'distance_km' => 'required|numeric|min:0|max:10',
            'weather_fee' => 'nullable|numeric|min:0',
        ], [
            'distance_km.max' => 'Rất tiếc, địa chỉ của bạn quá xa (vượt quá 10km) nên cửa hàng không thể giao hàng.',
        ]);

        // ── 2. Lấy địa chỉ ────────────────────────────────────────────────────
        $address = \App\Models\UserAddress::query()
            ->where('id', $request->input('address_id'))
            ->where('user_id', $userId)
            ->first();

        if (!$address) {
            return redirect()->back()->with('error', 'Địa chỉ giao hàng không hợp lệ.');
        }

        // ── 3. Xóa đơn MoMo cũ còn pending/unpaid (user đã hủy trước đó) ─────
        $oldPendingOrders = \App\Models\Order::query()
            ->where('user_id', $userId)
            ->where('payment_method', 'momo')
            ->where('payment_status', 'unpaid')
            ->where('status', 'pending')
            ->get();

        foreach ($oldPendingOrders as $oldOrder) {
            \App\Models\OrderItem::query()->where('order_id', $oldOrder->id)->delete();
            if ($oldOrder->promotion_id) {
                \App\Models\Promotion::query()->where('id', $oldOrder->promotion_id)->decrement('used_count');
            }
            \App\Models\Order::query()->where('id', $oldOrder->id)->delete();
        }

        // ── 4. Lấy giỏ hàng ───────────────────────────────────────────────────
        $cart = \App\Models\Cart::query()->where('user_id', $userId)->first();
        if (!$cart) {
            return redirect()->back()->with('error', 'Giỏ hàng của bạn đang trống.');
        }

        $cartItems = \App\Models\CartItem::query()->where('cart_id', $cart->id)->get();
        if ($cartItems->isEmpty()) {
            return redirect()->back()->with('error', 'Giỏ hàng của bạn đang trống.');
        }

        // Kiểm tra sản phẩm hết hàng trong giỏ
        $productIds = $cartItems->pluck('product_id')->toArray();
        $outOfStockProducts = \App\Models\Product::query()
            ->whereIn('id', $productIds)
            ->where('is_active', 0)
            ->pluck('name')
            ->toArray();
        if (!empty($outOfStockProducts)) {
            $names = implode(', ', $outOfStockProducts);
            return redirect()->back()->with('error', 'Sản phẩm đã hết hàng: ' . $names . '. Vui lòng xóa khỏi giỏ hàng trước khi đặt.');
        }


        // ── 4. Tính tiền ───────────────────────────────────────────────────────
        $itemIds = $cartItems->pluck('id');
        $cartToppings = \App\Models\CartItemTopping::query()
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

        // ── 5. Xử lý coupon ────────────────────────────────────────────────────
        $discountAmount = 0;
        $couponCode = null;
        $promotionId = null;

        $inputCoupon = trim($request->input('coupon_code'));
        if (!empty($inputCoupon)) {
            $coupon = \App\Models\Promotion::query()->where('code', strtoupper($inputCoupon))->first();
            if ($coupon && $coupon->is_active && (!$coupon->usage_limit || $coupon->used_count < $coupon->usage_limit)) {
                $isValidDate = true;
                if ($coupon->start_at && now() < $coupon->start_at)
                    $isValidDate = false;
                if ($coupon->end_at && now() > $coupon->end_at)
                    $isValidDate = false;

                if ($isValidDate && (!$coupon->min_order_amount || $subtotal >= $coupon->min_order_amount)) {
                    $hasUsed = \App\Models\Order::query()
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

        // ── 6. Lưu đơn hàng + order items vào DB (CHƯA xóa giỏ hàng) ──────────
        DB::beginTransaction();
        try {
            $orderId = \App\Models\Order::query()->insertGetId([
                'order_code' => $orderCode,
                'user_id' => $userId,
                'customer_name' => $address->fullname,
                'customer_phone' => $address->phone,
                'delivery_address' => $fullAddress,
                'total_amount' => $subtotal,
                'discount_amount' => $discountAmount,
                'final_amount' => $finalAmount,
                'payment_status' => 'unpaid',
                'payment_method' => 'momo',
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

                \App\Models\OrderItem::query()->insert([
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
                \App\Models\Promotion::query()->where('id', $promotionId)->increment('used_count');
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Có lỗi khi tạo đơn hàng: ' . $e->getMessage())->withInput();
        }

        // ── 7. Gọi API MoMo (theo mẫu PayMoMo/init_payment.php) ─────────────
        $requestId = $orderCode . '_' . time();
        $orderInfo = 'Thanh toan don hang ' . $orderCode;
        $redirectUrl = route('momo.return');
        $ipnUrl = route('momo.ipn');
        $amount = (string) $finalAmount;
        $extraData = '';
        $requestType = 'payWithATM'; // Chỉ cho phép thanh toán bằng Thẻ ATM nội địa

        // Tạo chữ ký HMAC SHA256 - đúng theo tài liệu MoMo PayMoMo
        $rawHash = "accessKey={$this->accessKey}"
            . "&amount={$amount}"
            . "&extraData={$extraData}"
            . "&ipnUrl={$ipnUrl}"
            . "&orderId={$orderCode}"
            . "&orderInfo={$orderInfo}"
            . "&partnerCode={$this->partnerCode}"
            . "&redirectUrl={$redirectUrl}"
            . "&requestId={$requestId}"
            . "&requestType={$requestType}";

        $signature = hash_hmac('sha256', $rawHash, $this->secretKey);

        // Payload gửi lên MoMo - đầy đủ theo mẫu chính thức
        $payload = [
            'partnerCode' => $this->partnerCode,
            'partnerName' => 'Test',
            'storeId' => 'MomoTestStore',
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => $orderCode,
            'orderInfo' => $orderInfo,
            'redirectUrl' => $redirectUrl,
            'ipnUrl' => $ipnUrl,
            'lang' => 'vi',
            'extraData' => $extraData,
            'requestType' => $requestType,
            'signature' => $signature,
        ];

        try {
            $response = Http::timeout(30)->withoutVerifying()->post($this->endpoint, $payload);
            $result = $response->json();

            Log::info('MoMo create payment response', $result ?? []);

            if (isset($result['payUrl']) && !empty($result['payUrl'])) {
                // ✅ MoMo trả payUrl → chuyển sang trang MoMo (CHƯA xóa giỏ hàng, chờ xác nhận thanh toán)
                return redirect()->away($result['payUrl']);
            }

            // ❌ MoMo không trả payUrl → hủy đơn hàng đã tạo (giỏ hàng vẫn còn)
            \App\Models\OrderItem::query()->where('order_id', $orderId)->delete();
            \App\Models\Order::query()->where('id', $orderId)->delete();
            if ($promotionId) {
                \App\Models\Promotion::query()->where('id', $promotionId)->decrement('used_count');
            }

            $errMessage = $result['message'] ?? 'Không thể kết nối cổng thanh toán MoMo. Vui lòng thử lại.';
            return redirect()->route('checkout')->with('error', 'MoMo: ' . $errMessage);

        } catch (\Exception $e) {
            // ❌ Lỗi network → hủy đơn hàng (giỏ hàng vẫn còn)
            \App\Models\OrderItem::query()->where('order_id', $orderId)->delete();
            \App\Models\Order::query()->where('id', $orderId)->delete();
            if ($promotionId) {
                \App\Models\Promotion::query()->where('id', $promotionId)->decrement('used_count');
            }

            Log::error('MoMo API error: ' . $e->getMessage());
            return redirect()->route('checkout')->with('error', 'Lỗi kết nối MoMo: ' . $e->getMessage());
        }
    }

    /**
     * Xử lý sau khi khách thanh toán xong và MoMo redirect về.
     * Theo mẫu: php/PayMoMo/result.php
     * (Chỉ dùng để hiển thị UI, KHÔNG nên cập nhật DB ở đây)
     */
    public function handleReturn(Request $request)
    {
        $orderId = $request->query('orderId');
        $resultCode = $request->query('resultCode');
        $message = $request->query('message', '');

        if ($resultCode == '0') {
            // ✅ Thanh toán thành công - cập nhật đơn hàng + xóa giỏ hàng
            $order = \App\Models\Order::query()->where('order_code', $orderId)->first();
            if ($order && $order->payment_status === 'unpaid') {
                \App\Models\Order::query()
                    ->where('order_code', $orderId)
                    ->update([
                        'payment_status' => 'paid',
                        'status' => 'pending',
                        'updated_at' => now(),
                    ]);

                // Xóa giỏ hàng sau khi thanh toán thành công
                $cart = \App\Models\Cart::query()->where('user_id', $order->user_id)->first();
                if ($cart) {
                    $cartItemIds = \App\Models\CartItem::query()->where('cart_id', $cart->id)->pluck('id');
                    \App\Models\CartItemTopping::query()->whereIn('cart_item_id', $cartItemIds)->delete();
                    \App\Models\CartItem::query()->where('cart_id', $cart->id)->delete();
                }
            }

            return redirect()->route('orders')->with('success', "Thanh toán MoMo thành công! Đơn hàng {$orderId} đã được xác nhận.");
        }

        // ❌ Thanh toán thất bại / người dùng ấn Quay về chưa thanh toán
        $order = \App\Models\Order::query()->where('order_code', $orderId)->first();
        if ($order && in_array($order->payment_status, ['unpaid', 'failed'])) {
            // Xóa hoàn toàn đơn hàng khỏi DB → giỏ hàng vẫn còn nguyên
            \App\Models\OrderItem::query()->where('order_id', $order->id)->delete();
            \App\Models\Order::query()->where('id', $order->id)->delete();

            // Hoàn lại lượt sử dụng mã giảm giá (nếu có)
            if ($order->promotion_id) {
                \App\Models\Promotion::query()->where('id', $order->promotion_id)->decrement('used_count');
            }
        }

        return redirect()->route('checkout')->with('error', 'Bạn đã hủy thanh toán MoMo. Giỏ hàng của bạn vẫn được giữ nguyên.');
    }

    /**
     * IPN (Instant Payment Notification) - MoMo gọi ngầm vào đây sau giao dịch.
     * Theo mẫu: php/PayMoMo/ipn_momo.php
     * Đây là nơi cập nhật trạng thái DB chính thức và đáng tin cậy nhất.
     */
    public function handleIpn(Request $request)
    {
        $data = $request->all();
        Log::info('MoMo IPN received', $data);

        // ── Xác thực chữ ký từ MoMo (theo mẫu ipn_momo.php) ─────────────────
        $rawHash = "accessKey={$this->accessKey}"
            . "&amount={$data['amount']}"
            . "&extraData={$data['extraData']}"
            . "&message={$data['message']}"
            . "&orderId={$data['orderId']}"
            . "&orderInfo={$data['orderInfo']}"
            . "&orderType={$data['orderType']}"
            . "&partnerCode={$data['partnerCode']}"
            . "&payType={$data['payType']}"
            . "&requestId={$data['requestId']}"
            . "&responseTime={$data['responseTime']}"
            . "&resultCode={$data['resultCode']}"
            . "&transId={$data['transId']}";

        $expectedSignature = hash_hmac('sha256', $rawHash, $this->secretKey);

        if (!isset($data['signature']) || $data['signature'] !== $expectedSignature) {
            Log::warning('MoMo IPN: Invalid signature', ['received' => $data['signature'] ?? 'none']);
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        // ── Cập nhật trạng thái đơn hàng ─────────────────────────────────────
        $orderId = $data['orderId'];
        $resultCode = (int) $data['resultCode'];

        $order = \App\Models\Order::query()->where('order_code', $orderId)->first();
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if ($resultCode === 0) {
            // ✅ Thanh toán thành công
            \App\Models\Order::query()
                ->where('order_code', $orderId)
                ->update([
                    'payment_status' => 'paid',
                    'status' => 'pending',
                    'updated_at' => now(),
                ]);

            // Xóa giỏ hàng (safety backup - phòng trường hợp handleReturn chưa xóa)
            $cart = \App\Models\Cart::query()->where('user_id', $order->user_id)->first();
            if ($cart) {
                $cartItemIds = \App\Models\CartItem::query()->where('cart_id', $cart->id)->pluck('id');
                if ($cartItemIds->isNotEmpty()) {
                    \App\Models\CartItemTopping::query()->whereIn('cart_item_id', $cartItemIds)->delete();
                    \App\Models\CartItem::query()->where('cart_id', $cart->id)->delete();
                }
            }

            Log::info("MoMo IPN: Order {$orderId} marked as PAID");
        } else {
            // Thanh toán thất bại → Xóa luôn đơn hàng (để không rác lịch sử của user)
            \App\Models\OrderItem::query()->where('order_id', $order->id)->delete();
            \App\Models\Order::query()->where('id', $order->id)->delete();

            if ($order->promotion_id) {
                \App\Models\Promotion::query()->where('id', $order->promotion_id)->decrement('used_count');
            }
            Log::info("MoMo IPN: Order {$orderId} marked as FAILED (resultCode={$resultCode})");
        }

        // MoMo yêu cầu trả về 204 No Content
        return response()->noContent();
    }
}
