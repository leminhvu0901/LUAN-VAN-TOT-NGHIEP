<?php

namespace App\Http\Controllers\Frontend;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Services\OrderWorkflowService;

class MomoController
{
    // ─── Cấu hình MoMo, chọn theo Settings admin (payment_environment: sandbox/production) ──
    private string $partnerCode;
    private string $accessKey;
    private string $secretKey;
    private string $endpoint;
    private bool $isProduction;
    private bool $configValid;

    public function __construct(private readonly OrderWorkflowService $orderWorkflow)
    {
        $this->isProduction = \App\Models\Setting::getValue('payment_environment', 'sandbox') === 'production';
        $momoConfig = config('services.momo.' . ($this->isProduction ? 'production' : 'sandbox'), []);

        $this->partnerCode = (string) ($momoConfig['partner_code'] ?? '');
        $this->accessKey = (string) ($momoConfig['access_key'] ?? '');
        $this->secretKey = (string) ($momoConfig['secret_key'] ?? '');
        $this->endpoint = (string) ($momoConfig['endpoint'] ?? '');

        $this->configValid = $this->partnerCode !== '' && $this->accessKey !== '' && $this->secretKey !== '';
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

        if (!$this->configValid) {
            return redirect()->back()->with('error', 'Chưa cấu hình MoMo cho môi trường chính thức. Vui lòng liên hệ quản trị viên.')->withInput();
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
        // MoMo yêu cầu amount là số nguyên VNĐ (không thập phân)
        $amount = (string) (int) round($finalAmount);
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
            $httpClient = Http::timeout(30);
            if (!$this->isProduction) {
                // Chỉ bỏ qua xác thực TLS ở môi trường thử nghiệm (một số máy dev gặp lỗi cert cục bộ).
                $httpClient = $httpClient->withoutVerifying();
            }
            $response = $httpClient->post($this->endpoint, $payload);
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
     * Xác thực chữ ký MoMo gửi kèm (dùng chung cho cả redirect về trình duyệt và IPN server-to-server).
     * MoMo ký cả hai bằng cùng bộ field/thuật toán HMAC-SHA256.
     */
    private function verifyMomoSignature(array $data): bool
    {
        if (empty($data['signature'])) {
            return false;
        }

        $rawHash = "accessKey={$this->accessKey}"
            . "&amount=" . ($data['amount'] ?? '')
            . "&extraData=" . ($data['extraData'] ?? '')
            . "&message=" . ($data['message'] ?? '')
            . "&orderId=" . ($data['orderId'] ?? '')
            . "&orderInfo=" . ($data['orderInfo'] ?? '')
            . "&orderType=" . ($data['orderType'] ?? '')
            . "&partnerCode=" . ($data['partnerCode'] ?? '')
            . "&payType=" . ($data['payType'] ?? '')
            . "&requestId=" . ($data['requestId'] ?? '')
            . "&responseTime=" . ($data['responseTime'] ?? '')
            . "&resultCode=" . ($data['resultCode'] ?? '')
            . "&transId=" . ($data['transId'] ?? '');

        $expectedSignature = hash_hmac('sha256', $rawHash, $this->secretKey);

        return hash_equals($expectedSignature, (string) $data['signature']);
    }

    /**
     * Xử lý sau khi khách thanh toán xong và MoMo redirect về.
     * Theo mẫu: php/PayMoMo/result.php
     * MoMo ký cả redirect này (không chỉ IPN) nên có thể xác thực chữ ký và tin cậy để cập nhật DB —
     * cần thiết vì môi trường demo/local thường không có URL public để MoMo gọi IPN server-to-server.
     */
    public function handleReturn(Request $request)
    {
        $data = $request->query();
        $orderId = $data['orderId'] ?? null;
        $resultCode = $data['resultCode'] ?? null;

        Log::info('MoMo return received', [
            'orderId' => $orderId,
            'resultCode' => $resultCode,
            'message' => $data['message'] ?? null,
            'transId' => $data['transId'] ?? null,
        ]);

        $order = $orderId ? \App\Models\Order::query()->where('order_code', $orderId)->first() : null;
        if (!$order) {
            return redirect()->route('checkout')->with('error', 'Không tìm thấy đơn hàng.');
        }

        if (!$this->verifyMomoSignature($data)) {
            // Chữ ký thiếu/sai → không thể tin cậy nguồn gốc request này, không đụng vào payment_status.
            Log::warning('MoMo return: invalid or missing signature', ['orderId' => $orderId]);
            return redirect()->route('orders')->with('info', "Đang chờ xác nhận thanh toán từ MoMo cho đơn hàng {$orderId}.");
        }

        if ((string) $resultCode === '0') {
            // ✅ Thanh toán thành công - chữ ký hợp lệ nên tin cậy để cập nhật DB
            try {
                $this->orderWorkflow->markPaid($order, (string) ($data['transId'] ?? ''), (float) ($data['amount'] ?? 0));
            } catch (ValidationException $e) {
                Log::warning('MoMo return: amount mismatch', ['orderId' => $orderId]);
                return redirect()->route('orders')->with('error', 'Số tiền thanh toán không khớp đơn hàng, vui lòng liên hệ hỗ trợ.');
            }

            // Xóa giỏ hàng sau khi thanh toán thành công
            $cart = \App\Models\Cart::query()->where('user_id', $order->user_id)->first();
            if ($cart) {
                $cartItemIds = \App\Models\CartItem::query()->where('cart_id', $cart->id)->pluck('id');
                \App\Models\CartItemTopping::query()->whereIn('cart_item_id', $cartItemIds)->delete();
                \App\Models\CartItem::query()->where('cart_id', $cart->id)->delete();
            }

            return redirect()->route('orders')->with('success', "Thanh toán MoMo thành công! Đơn hàng {$orderId} đã được xác nhận.");
        }

        // ❌ Thanh toán thất bại / người dùng ấn Quay về chưa thanh toán (chữ ký đã xác thực nên an toàn để dọn đơn)
        if (in_array($order->payment_status, ['unpaid', 'failed'])) {
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

        if (!$this->verifyMomoSignature($data)) {
            Log::warning('MoMo IPN: Invalid signature', ['received' => $data['signature'] ?? 'none']);
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        // ── Cập nhật trạng thái đơn hàng ─────────────────────────────────────
        $orderId = $data['orderId'] ?? null;
        $resultCode = (int) ($data['resultCode'] ?? -1);

        $order = $orderId ? \App\Models\Order::query()->where('order_code', $orderId)->first() : null;
        if (!$order) {
            Log::warning('MoMo IPN: order not found', ['orderId' => $orderId]);
            return response()->json(['message' => 'Order not found'], 404);
        }

        if ($resultCode === 0) {
            // ✅ Thanh toán thành công
            try {
                $this->orderWorkflow->markPaid($order, (string) ($data['transId'] ?? ''), (float) ($data['amount'] ?? 0));
            } catch (ValidationException $e) {
                Log::warning('MoMo IPN: amount mismatch', ['orderId' => $orderId]);
                return response()->json(['message' => 'Amount mismatch'], 400);
            }

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
