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
    private string $refundEndpoint;
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
        $this->refundEndpoint = (string) ($momoConfig['refund_endpoint'] ?? '');

        $this->configValid = $this->partnerCode !== '' && $this->accessKey !== '' && $this->secretKey !== '';
    }

    /**
     * Trả lỗi đúng định dạng theo kiểu request: JSON 422 cho fetch (trang checkout submit qua AJAX,
     * xem checkout.js) để JS hiện lỗi tại chỗ không cần tải lại trang; redirect-back cổ điển cho
     * request thường (không mất thông tin khách đã nhập nhờ withInput()).
     */
    private function checkoutError(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message], 422);
        }
        return redirect()->back()->with('error', $message)->withInput();
    }

    /**
     * Điều hướng đúng định dạng theo kiểu request — dùng cho các đích đến "thành công" (sang cổng
     * MoMo, hoặc quay lại trang đăng nhập). AJAX nhận URL để tự window.location.href, request thường
     * nhận redirect thật.
     */
    private function checkoutRedirect(Request $request, string $url)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'redirect_url' => $url]);
        }
        return redirect()->away($url);
    }

    /**
     * Được gọi khi khách chọn MoMo và bấm "Đặt hàng".
     * Tạo đơn hàng trong DB (payment_status = unpaid) rồi điều hướng sang cổng MoMo.
     * Theo mẫu: php/PayMoMo/init_payment.php từ github.com/momo-wallet/payment
     */
    public function createPayment(Request $request)
    {
        if (!Auth::check()) {
            return $this->checkoutRedirect($request, route('login'));
        }

        if (!$this->configValid) {
            return $this->checkoutError($request, 'Chưa cấu hình MoMo cho môi trường chính thức. Vui lòng liên hệ quản trị viên.');
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
            return $this->checkoutError($request, 'Địa chỉ giao hàng không hợp lệ.');
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
            return $this->checkoutError($request, 'Giỏ hàng của bạn đang trống.');
        }

        $cartItemQuery = \App\Models\CartItem::query()->where('cart_id', $cart->id);

        // Lọc theo danh sách đã chọn (nếu có trong session)
        $selectedIds = session('selected_cart_item_ids');
        if (!empty($selectedIds)) {
            // Validate: chỉ lấy id thuộc cart của user này
            $cartItemQuery->whereIn('id', $selectedIds);
        }

        $cartItems = $cartItemQuery->get();
        if ($cartItems->isEmpty()) {
            return $this->checkoutError($request, 'Giỏ hàng của bạn đang trống.');
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
            return $this->checkoutError($request, 'Sản phẩm đã hết hàng: ' . $names . '. Vui lòng xóa khỏi giỏ hàng trước khi đặt.');
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
            // Đơn MoMo ở luồng này luôn là đơn giao hàng -> mã chỉ dành riêng cho "Tại quầy" không được áp dụng.
            if ($coupon && $coupon->is_active && $coupon->applies_to !== 'pickup' && (!$coupon->usage_limit || $coupon->used_count < $coupon->usage_limit)) {
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
                'delivery_latitude' => $address->latitude,
                'delivery_longitude' => $address->longitude,
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
            return $this->checkoutError($request, 'Có lỗi khi tạo đơn hàng: ' . $e->getMessage());
        }

        // ── 7. Gọi API MoMo (theo mẫu PayMoMo/init_payment.php) ─────────────
        $orderInfo = 'Thanh toan don hang ' . $orderCode;
        // payWithMethod: MoMo tự hiện màn hình chọn phương thức (Ví MoMo, ATM, Visa/Master...)
        $payUrl = $this->requestPayUrl($orderCode, $finalAmount, $orderInfo, 'payWithMethod');

        if ($payUrl) {
            // ✅ MoMo trả payUrl → chuyển sang trang MoMo (CHƯA xóa giỏ hàng, chờ xác nhận thanh toán)
            return $this->checkoutRedirect($request, $payUrl);
        }

        // ❌ MoMo không trả payUrl → hủy đơn hàng đã tạo (giỏ hàng vẫn còn)
        \App\Models\OrderItem::query()->where('order_id', $orderId)->delete();
        \App\Models\Order::query()->where('id', $orderId)->delete();
        if ($promotionId) {
            \App\Models\Promotion::query()->where('id', $promotionId)->decrement('used_count');
        }

        return $this->checkoutError($request, 'MoMo: Không thể kết nối cổng thanh toán MoMo. Vui lòng thử lại.');
    }

    /**
     * Yêu cầu MoMo tạo payUrl cho một đơn hàng (mã đơn + số tiền + mô tả) — dùng chung cho cả
     * luồng khách tự checkout (createPayment) và luồng lễ tân bấm thanh toán MoMo cho đơn tại quầy
     * (payExistingOrder) để tránh trùng lặp logic ký chữ ký HMAC dễ phát sinh lỗi nếu tách rời.
     */
    private function requestPayUrl(string $orderCode, float $amount, string $orderInfo, string $requestType = 'payWithATM'): ?string
    {
        $requestId = $orderCode . '_' . time();
        $redirectUrl = route('momo.return');
        $ipnUrl = route('momo.ipn');
        // MoMo yêu cầu amount là số nguyên VNĐ (không thập phân)
        $amountStr = (string) (int) round($amount);
        $extraData = '';

        // Tạo chữ ký HMAC SHA256 - đúng theo tài liệu MoMo PayMoMo
        $rawHash = "accessKey={$this->accessKey}"
            . "&amount={$amountStr}"
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
            'amount' => $amountStr,
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

            return (!empty($result['payUrl'])) ? $result['payUrl'] : null;
        } catch (\Exception $e) {
            Log::error('MoMo API error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Gọi API hoàn tiền MoMo cho một đơn đã thanh toán (transId = payment_transaction_id gốc).
     * Mirror cấu trúc requestPayUrl() nhưng field set riêng của refund API. Public (không private) vì
     * Delivery\OrderController cũng cần gọi trực tiếp cho case "hàng hư hỏng" — cùng tiền lệ với
     * payExistingOrder() đã được gọi cross-controller từ Reception\OrderController::storeOrder().
     * Không mở transaction DB / lock đơn ở đây — chỉ gọi API thuần, việc cập nhật DB nguyên tử diễn ra
     * SAU KHI đã có kết quả (xem OrderWorkflowService::refundAndCancel/markDeliveryFailedWithRefund),
     * để không giữ lock trong lúc chờ mạng.
     *
     * @return array{success: bool, transId: ?string, message: string}
     */
    public function requestRefund(\App\Models\Order $order): array
    {
        $requestId = 'RF' . $order->order_code . '_' . time();
        $refundOrderId = $requestId;
        $amountStr = (string) (int) round((float) $order->final_amount);
        $description = 'Hoan tien don hang ' . $order->order_code;
        $transId = (string) $order->payment_transaction_id;

        $rawHash = "accessKey={$this->accessKey}"
            . "&amount={$amountStr}"
            . "&description={$description}"
            . "&orderId={$refundOrderId}"
            . "&partnerCode={$this->partnerCode}"
            . "&requestId={$requestId}"
            . "&transId={$transId}";

        $signature = hash_hmac('sha256', $rawHash, $this->secretKey);

        $payload = [
            'partnerCode' => $this->partnerCode,
            'orderId' => $refundOrderId,
            'requestId' => $requestId,
            'amount' => $amountStr,
            'transId' => $transId,
            'lang' => 'vi',
            'description' => $description,
            'signature' => $signature,
        ];

        try {
            $httpClient = Http::timeout(30);
            if (!$this->isProduction) {
                $httpClient = $httpClient->withoutVerifying();
            }
            $response = $httpClient->post($this->refundEndpoint, $payload);
            $result = $response->json();

            Log::info('MoMo refund response', $result ?? []);

            if ((int) ($result['resultCode'] ?? -1) === 0) {
                return [
                    'success' => true,
                    'transId' => (string) ($result['transId'] ?? $refundOrderId),
                    'message' => 'OK',
                ];
            }

            return [
                'success' => false,
                'transId' => null,
                'message' => (string) ($result['message'] ?? 'Lỗi không xác định từ MoMo.'),
            ];
        } catch (\Exception $e) {
            Log::error('MoMo refund API error: ' . $e->getMessage());
            return ['success' => false, 'transId' => null, 'message' => 'Không thể kết nối cổng thanh toán MoMo.'];
        }
    }

    /**
     * Lễ tân/admin bấm "Hoàn tiền & Hủy đơn" cho một đơn MoMo đã thanh toán — dùng chung cho cả 2 route
     * (staff.reception.orders.refund / admin.orders.refund), tương tự payExistingOrder() dùng chung.
     * Chỉ hủy đơn NẾU hoàn tiền MoMo thành công — không bao giờ hủy "chay" một đơn đã thanh toán.
     */
    public function refundOrder(Request $request, \App\Models\Order $order)
    {
        if (!$this->configValid) {
            return response()->json(['success' => false, 'message' => 'Chưa cấu hình MoMo cho môi trường chính thức. Vui lòng liên hệ quản trị viên.'], 422);
        }

        $validated = $request->validate([
            'cancel_reason' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'cancel_reason.required' => 'Vui lòng nhập lý do hủy đơn (tối thiểu 5 ký tự).',
            'cancel_reason.min' => 'Vui lòng nhập lý do hủy đơn (tối thiểu 5 ký tự).',
        ]);

        if ($order->payment_method !== 'momo' || $order->payment_status !== 'paid') {
            return response()->json(['success' => false, 'message' => 'Đơn hàng này không cần hoàn tiền MoMo.'], 422);
        }
        if (!in_array($order->status, ['pending', 'confirmed'], true)) {
            return response()->json(['success' => false, 'message' => 'Chỉ có thể hoàn tiền cho đơn đang chờ xác nhận/đã xác nhận.'], 422);
        }
        if (!$order->payment_transaction_id) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy mã giao dịch gốc để hoàn tiền.'], 422);
        }

        $result = $this->requestRefund($order);

        if (!$result['success']) {
            Log::error('MoMo refund failed', ['orderId' => $order->order_code, 'message' => $result['message']]);
            return response()->json(['success' => false, 'message' => 'Hoàn tiền MoMo thất bại: ' . $result['message']], 422);
        }

        try {
            $this->orderWorkflow->refundAndCancel($order, $result['transId'], $validated['cancel_reason']);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors(), 'message' => collect($e->errors())->flatten()->first()], 422);
        }

        return response()->json(['success' => true, 'message' => 'Đã hoàn tiền và hủy đơn hàng thành công!']);
    }

    /**
     * Lễ tân bấm "Thanh toán MoMo" cho một đơn tại quầy đã tồn tại (tạo ở StaffReceptionOrderController).
     * Khác với createPayment(): đơn đã có sẵn trong DB (không tạo mới), chỉ xin payUrl rồi chuyển hướng.
     * Đơn tại quầy đã đặt trước tồn kho thật nên KHÔNG tự xóa khi thất bại — để lễ tân thử lại hoặc
     * đổi phương thức thanh toán, tránh làm rác/orphan dữ liệu tồn kho đã trừ.
     */
    public function payExistingOrder(Request $request, \App\Models\Order $order)
    {
        // show.js (trang chi tiết đơn của lễ tân) submit nút "Thanh toán MoMo" qua fetch — dùng lại
        // đúng 2 helper checkoutError()/checkoutRedirect() đã có ở trên (chung logic AJAX-aware cho
        // cả 2 luồng MoMo: khách tự checkout lẫn lễ tân thanh toán hộ đơn tại quầy).
        $showUrl = route('staff.reception.orders.show', $order->id);

        if (!$this->configValid) {
            return $this->checkoutError($request, 'Chưa cấu hình MoMo cho môi trường chính thức. Vui lòng chọn thanh toán tiền mặt hoặc liên hệ quản trị viên.');
        }

        if ($order->payment_method !== 'momo' || $order->payment_status === 'paid') {
            return $this->checkoutError($request, 'Đơn hàng này không cần thanh toán qua MoMo.');
        }

        // Dùng payWithMethod để MoMo hiện đầy đủ các phương thức: ví MoMo, ATM, thẻ tín dụng...
        // (giống luồng khách tự checkout online)
        $payUrl = $this->requestPayUrl($order->order_code, (float) $order->final_amount, 'Thanh toan don hang ' . $order->order_code, 'payWithMethod');

        if (!$payUrl) {
            return $this->checkoutError($request, 'Không thể kết nối cổng thanh toán MoMo. Vui lòng thử lại.');
        }

        return $this->checkoutRedirect($request, $payUrl);
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

            // Xóa CHỈ những cart_items đã được đưa vào đơn hàng này (dựa theo product_id + size)
            $cart = \App\Models\Cart::query()->where('user_id', $order->user_id)->first();
            if ($cart) {
                // Lấy product_id của các sản phẩm trong đơn
                $orderedProductIds = $order->items->pluck('product_id')->toArray();
                $cartItemIds = \App\Models\CartItem::query()
                    ->where('cart_id', $cart->id)
                    ->whereIn('product_id', $orderedProductIds)
                    ->pluck('id');
                if ($cartItemIds->isNotEmpty()) {
                    \App\Models\CartItemTopping::query()->whereIn('cart_item_id', $cartItemIds)->delete();
                    \App\Models\CartItem::query()->whereIn('id', $cartItemIds)->delete();
                }
            }

            // Đơn tại quầy (lễ tân tạo) → quay về trang chi tiết đơn phía backend, không phải trang "đơn của tôi" của khách.
            if ($order->delivery_type === 'pickup') {
                return redirect()->route('staff.reception.orders.show', $order->id)
                    ->with('success', "Thanh toán MoMo thành công! Đơn hàng {$orderId} đã được xác nhận.");
            }

            return redirect()->route('orders')->with('success', "Thanh toán MoMo thành công! Đơn hàng {$orderId} đã được xác nhận.");
        }

        // ❌ Thanh toán thất bại / người dùng ấn Quay về chưa thanh toán
        if ($order->delivery_type === 'pickup') {
            // Đơn tại quầy đã đặt trước tồn kho thật (không có giỏ hàng nào để "giữ nguyên") —
            // không tự xóa, để lễ tân thử thanh toán lại hoặc hủy đúng quy trình (giải phóng tồn kho đàng hoàng).
            return redirect()->route('staff.reception.orders.show', $order->id)
                ->with('error', 'Thanh toán MoMo chưa hoàn tất. Bạn có thể thử lại hoặc hủy đơn.');
        }

        // Chữ ký đã xác thực nên an toàn để dọn đơn checkout của khách (giỏ hàng khách vẫn còn nguyên)
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

            // Xóa CHỈ những cart_items thuộc đơn hàng này (safety backup)
            $cart = \App\Models\Cart::query()->where('user_id', $order->user_id)->first();
            if ($cart) {
                $orderedProductIds = $order->items->pluck('product_id')->toArray();
                $cartItemIds = \App\Models\CartItem::query()
                    ->where('cart_id', $cart->id)
                    ->whereIn('product_id', $orderedProductIds)
                    ->pluck('id');
                if ($cartItemIds->isNotEmpty()) {
                    \App\Models\CartItemTopping::query()->whereIn('cart_item_id', $cartItemIds)->delete();
                    \App\Models\CartItem::query()->whereIn('id', $cartItemIds)->delete();
                }
            }

            Log::info("MoMo IPN: Order {$orderId} marked as PAID");
        } elseif ($order->delivery_type === 'pickup') {
            // Đơn tại quầy đã đặt trước tồn kho thật → không tự xóa, giữ lại để lễ tân thử lại/hủy đúng quy trình.
            Log::info("MoMo IPN: Pickup order {$orderId} payment failed (resultCode={$resultCode}), kept for retry.");
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
