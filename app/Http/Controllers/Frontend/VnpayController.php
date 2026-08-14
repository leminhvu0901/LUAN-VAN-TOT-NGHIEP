<?php

namespace App\Http\Controllers\Frontend;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CartItemTopping;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Promotion;
use App\Models\Setting;
use App\Models\UserAddress;
use App\Services\CartPricingService;
use App\Services\OrderService;
use App\Services\OrderWorkflowService;
use App\Services\PromotionService;
use App\Services\ShippingQuoteService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class VnpayController
{
    // Cấu hình VNPay, Môi trường thử nghiệm Sandbox
    private string $tmnCode;
    private string $hashSecret;
    private string $vnpUrl;
    private string $refundEndpoint;
    private bool $configValid;

    public function __construct(
        private readonly OrderWorkflowService $orderWorkflow,
        private readonly CartPricingService $cartPricing,
        private readonly PromotionService $promotions,
        private readonly ShippingQuoteService $shipping,
        private readonly OrderService $orderService,
    ) {
        $vnpayConfig = config('services.vnpay.sandbox', []);
        $tmn = Setting::getValue('vnpay_tmn_code');
        $this->tmnCode = !empty($tmn) ? (string) $tmn : (string) ($vnpayConfig['tmn_code'] ?? '');

        $secret = Setting::getValue('vnpay_hash_secret');
        $this->hashSecret = !empty($secret) ? (string) $secret : (string) ($vnpayConfig['hash_secret'] ?? '');

        $url = Setting::getValue('vnpay_url');
        $this->vnpUrl = !empty($url) ? (string) $url : (string) ($vnpayConfig['url'] ?? 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html');

        $refund = Setting::getValue('vnpay_refund_url');
        $this->refundEndpoint = !empty($refund) ? (string) $refund : (string) ($vnpayConfig['refund_endpoint'] ?? 'https://sandbox.vnpayment.vn/merchant_webapi/api/transaction');

        $this->configValid = !empty($this->tmnCode) && !empty($this->hashSecret);
    }

   // Trả lỗi đúng định dạng theo kiểu request.
    private function checkoutError(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message], 422);
        }
        return redirect()->back()->with('error', $message)->withInput();
    }

    // Hàm này xử lý redirect sau khi checkout VNPay thành công
    private function checkoutRedirect(Request $request, string $url)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'redirect_url' => $url]);
        }
        return redirect()->away($url);
    }

    // Tạo đơn hàng tạm, tính toàn bộ giá/phí/giảm giá trước
    private function createPendingOrderForOnlinePayment(Request $request, string $paymentMethod)
    {
        if (!Auth::check()) {
            return $this->checkoutRedirect($request, route('login'));
        }

        if (!$this->configValid) {
            return $this->checkoutError($request, 'Chưa cấu hình cổng thanh toán cho môi trường chính thức. Vui lòng liên hệ quản trị viên.');
        }

        $userId = Auth::id();

        // 0a. Kiểm tra trạng thái tắt nhận đơn hàng từ trang
        $receiveEnabled = (bool) Setting::getValue('orders_enabled', true);
        if (!$receiveEnabled) {
            return $this->checkoutError($request, 'Cửa hàng hiện đang tạm ngưng nhận đơn hàng.');
        }

        // 0b. Kiểm tra giờ hoạt động, đọc từ Settings admin
        $open = Setting::getValue('store_open_time', '08:00');
        $close = Setting::getValue('store_close_time', '22:00');
        $nowStr = now()->format('H:i');

        if ($open < $close) {
            $isOpen = ($nowStr >= $open && $nowStr <= $close);
        } else { // Khung giờ mở qua đêm, vd 22:00 -> 03:00 sáng hôm sau, hoặc 00:00-00:00 nghĩa là mở 24/7
            $isOpen = ($nowStr >= $open || $nowStr <= $close);
        }

        if (!$isOpen) {
            return $this->checkoutError($request, "Cửa hàng hiện đã đóng cửa! Giờ hoạt động của chúng tôi là từ {$open} đến {$close} hàng ngày.");
        }

        // Validate
        $request->validate([
            'address_id' => 'required',
            'payment_method' => 'required|in:' . $paymentMethod,
            'coupon_code' => 'nullable|string',
            'note' => 'nullable|string|max:500',
        ]);

        // Lấy địa chỉ
        $address = UserAddress::query()
            ->where('id', $request->input('address_id'))
            ->where('user_id', $userId)
            ->first();

        if (!$address) {
            return $this->checkoutError($request, 'Địa chỉ giao hàng không hợp lệ.');
        }

        // Xóa đơn cùng cổng thanh toán còn pending/unpaid
        $oldPendingOrders = Order::query()
            ->where('user_id', $userId)
            ->where('payment_method', $paymentMethod)
            ->where('payment_status', 'unpaid')
            ->where('status', 'pending')
            ->get();

        foreach ($oldPendingOrders as $oldOrder) {
            // Xóa đơn TRƯỚC rồi mới hoàn lượt mã giảm giá: nếu đơn
            if (Order::query()->where('id', $oldOrder->id)->delete() === 0) {
                continue;
            }
            OrderItem::query()->where('order_id', $oldOrder->id)->delete();
            if ($oldOrder->promotion_id) {
                Promotion::query()->where('id', $oldOrder->promotion_id)->where('used_count', '>', 0)->decrement('used_count');
            }
        }

        // Lấy giỏ hàng + tính giá thật dùng chung
        $cart = Cart::query()->where('user_id', $userId)->first();
        if (!$cart) {
            return $this->checkoutError($request, 'Giỏ hàng của bạn đang trống.');
        }

        $selectedIds = session('selected_cart_item_ids');
        $selectedIds = !empty($selectedIds) ? $selectedIds : null;

        $user = Auth::user();

        try {
            // pricedItems() tự kiểm tra giỏ rỗng/sản phẩm ngừng
            $items = $this->cartPricing->pricedItems($cart, selectedIds: $selectedIds);
        } catch (ValidationException $e) {
            return $this->checkoutError($request, collect($e->errors())->flatten()->first());
        }

        $subtotal = $this->cartPricing->subtotal($items);
        $totalQuantity = (int) $items->sum('quantity');

        // Phí vận chuyển + phụ thu thời tiết, tính lại từ
        $quote = $this->shipping->quote($address, $subtotal, $user);
        $maxDistance = (float) Setting::getValue('shipping_max_distance_km', ShippingQuoteService::MAX_DELIVERY_KM);
        if ($quote['distance_km'] > $maxDistance) {
            return $this->checkoutError($request, "Địa chỉ vượt quá phạm vi giao hàng {$maxDistance} km.");
        }

        // Chuẩn bị các giá trị không phụ thuộc mã giảm giá
        $manualCode = trim((string) $request->input('coupon_code'));
        $manualCode = $manualCode !== '' ? $manualCode : null;
        $fullAddress = $address->specific_address . ', ' . $address->ward . ', ' . $address->district . ', ' . $address->province;
        $estimatedTime = now()->addMinutes(45);
        $orderCode = 'HPY-' . strtoupper(bin2hex(random_bytes(4)));

        // Khóa + tính mã giảm giá + lưu đơn hàng, TẤT CẢ
        DB::beginTransaction();
        try {
            $promotion = null;
            $couponDiscount = 0.0;
            $giftEntries = [];
            if ($manualCode !== null) {
                $autoResult = $this->promotions->resolveBestDiscount($items, $subtotal, $user, 'delivery', $totalQuantity, $manualCode, lock: true);
                $promotion = $autoResult['promotion'];
                $couponDiscount = $autoResult['discount'];
                // Mã combo kèm sẵn phần quà tặng ở đây; các loại mã khác luôn rỗng.
                $giftEntries = $autoResult['gifts'] ?? [];
            }

            $membershipDiscount = $this->orderService->membershipDiscount($user, $subtotal);
            $discountAmount = min($subtotal, $couponDiscount + $membershipDiscount);
            $finalAmount = max(0, $subtotal + $quote['shipping_fee'] + $quote['weather_fee'] - $discountAmount);

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
                'coupon_code' => $promotion?->code,
                'promotion_id' => $promotion?->id,
                'delivery_type' => 'delivery',
                'estimated_time' => $estimatedTime,
                'distance_km' => $quote['distance_km'],
                'weather_fee' => $quote['weather_fee'],
                'peak_hour_fee' => 0,
                'shipping_fee' => $quote['shipping_fee'],
                'customer_note' => $request->input('note'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($items as $item) {
                $toppingsList = json_encode($item->calculated_toppings->pluck('name')->all(), JSON_UNESCAPED_UNICODE);

                OrderItem::query()->insert([
                    'order_id' => $orderId,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'product_sku' => $item->product->sku,
                    'product_image' => $item->product->image,
                    'size_name' => $item->size_name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->calculated_unit_price,
                    'sugar_level' => $item->sugar_level,
                    'ice_level' => $item->ice_level,
                    'options' => $toppingsList,
                    'note' => null,
                ]);
            }

            // Món quà tặng của mã combo khách đã áp, lưu thành dòng
            foreach ($giftEntries as $entry) {
                $comboItemNames = $entry['combo_items']->map(fn($ci) => $ci->quantity . ' ' . $ci->product->name)->implode(' + ');

                OrderItem::create([
                    'order_id' => $orderId,
                    'product_id' => $entry['gift_product']->id,
                    'product_name' => $entry['gift_product']->name,
                    'product_sku' => $entry['gift_product']->sku,
                    'product_image' => $entry['gift_product']->image,
                    'size_name' => null,
                    'quantity' => $entry['granted_quantity'],
                    'unit_price' => 0,
                    'sugar_level' => null,
                    'ice_level' => null,
                    'options' => [],
                    'note' => 'Quà tặng combo: Mua ' . $comboItemNames . ' tặng ' . $entry['granted_quantity'] . ' ' . $entry['gift_product']->name,
                    'is_gift' => true,
                    'source_promotion_id' => $entry['promotion']->id,
                ]);
            }

            if ($promotion) {
                $promotion->increment('used_count');
            }

            DB::commit();
        } catch (ValidationException $e) {
            // Mã giảm giá không hợp lệ/hết lượt: trả đúng thông báo
            DB::rollBack();
            return $this->checkoutError($request, collect($e->errors())->flatten()->first());
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->checkoutError($request, 'Có lỗi khi tạo đơn hàng: ' . $e->getMessage());
        }

        return Order::query()->find($orderId);
    }

    // TAO ĐƠN VNPAY
    public function createPayment(Request $request)
    {
        $result = $this->createPendingOrderForOnlinePayment($request, 'vnpay'); //Tạo đơn hàng tạm, tính toàn bộ giá/phí/giảm giá trước khi chuyển sang cổng thanh toán
        if (!($result instanceof Order)) {
            return $result;
        }

        $paymentUrl = $this->buildPaymentUrl($result, $request);
        return $this->checkoutRedirect($request, $paymentUrl);
    }

    // tạo ra URL thanh toán để redirect khách sang cổng VNPay:
    private function buildPaymentUrl(Order $order, Request $request): string
    {
        $inputData = [
            'vnp_Version' => '2.1.0',
            'vnp_Command' => 'pay',
            'vnp_TmnCode' => $this->tmnCode,
            'vnp_Amount' => (int) round((float) $order->final_amount) * 100,
            'vnp_CurrCode' => 'VND',
            'vnp_TxnRef' => $order->order_code,
            'vnp_OrderInfo' => 'Thanh toan don hang ' . $order->order_code,
            'vnp_OrderType' => 'other',
            'vnp_Locale' => 'vn',
            'vnp_ReturnUrl' => route('vnpay.return'),
            'vnp_IpAddr' => $request->ip(),
            'vnp_CreateDate' => now()->timezone('Asia/Ho_Chi_Minh')->format('YmdHis'),
        ];

        ksort($inputData);

        $query = '';
        $hashData = '';
        $first = true;
        foreach ($inputData as $key => $value) {
            if (!$first) {
                $hashData .= '&';
                $query .= '&';
            }
            $hashData .= urlencode((string) $key) . '=' . urlencode((string) $value);
            $query .= urlencode((string) $key) . '=' . urlencode((string) $value);
            $first = false;
        }

        $secureHash = hash_hmac('sha512', $hashData, $this->hashSecret);

        return $this->vnpUrl . '?' . $query . '&vnp_SecureHash=' . $secureHash;
    }


    // Gọi API hoàn tiền VNPay cho một đơn đã thanh toán
    public function requestRefund(Order $order): array
    {
        // Định danh riêng cho lần gọi hoàn tiền này, không liên
        $requestId = 'RF' . $order->order_code . '_' . time();
        $amountStr = (string) ((int) round((float) $order->final_amount) * 100); // VNPay tính bằng đơn vị "xu", x100
        $transactionNo = (string) $order->payment_transaction_id; // Mã giao dịch gốc lúc thanh toán, VNPay cần để đối chiếu
        $transactionDate = $order->paid_at ? $order->paid_at->format('YmdHis') : now()->format('YmdHis');
        $orderInfo = 'Hoan tien don hang ' . $order->order_code;
        $createDate = now()->timezone('Asia/Ho_Chi_Minh')->format('YmdHis');
        $ipAddr = request()?->ip() ?: '127.0.0.1';
        $createBy = 'system';

        // Chuỗi ký nối bằng "|" theo đúng thứ tự tham số quy
        $hashData = implode('|', [
            $requestId,
            '2.1.0',
            'refund',
            $this->tmnCode,
            '02', // vnp_TransactionType: hoàn toàn phần
            $order->order_code,
            $amountStr,
            $transactionNo,
            $transactionDate,
            $createBy,
            $createDate,
            $ipAddr,
            $orderInfo,
        ]);

        $secureHash = hash_hmac('sha512', $hashData, $this->hashSecret);

        $payload = [
            'vnp_RequestId' => $requestId,
            'vnp_Version' => '2.1.0',
            'vnp_Command' => 'refund',
            'vnp_TmnCode' => $this->tmnCode,
            'vnp_TransactionType' => '02',
            'vnp_TxnRef' => $order->order_code,
            'vnp_Amount' => $amountStr,
            'vnp_TransactionNo' => $transactionNo,
            'vnp_TransactionDate' => $transactionDate,
            'vnp_CreateBy' => $createBy,
            'vnp_CreateDate' => $createDate,
            'vnp_IpAddr' => $ipAddr,
            'vnp_OrderInfo' => $orderInfo,
            'vnp_SecureHash' => $secureHash,
        ];

        try {
            // withoutVerifying(): sandbox VNPay dùng SSL tự ký, bỏ
            $httpClient = Http::timeout(30)->withoutVerifying();
            $response = $httpClient->post($this->refundEndpoint, $payload);
            $result = $response->json();

            Log::info('VNPay refund response', $result ?? []);

            // '00' là mã phản hồi thành công theo tài liệu VNPay
            if ((string) ($result['vnp_ResponseCode'] ?? '') === '00') {
                return [
                    'success' => true,
                    'transId' => (string) ($result['vnp_TransactionNo'] ?? $requestId),
                    'message' => 'OK',
                ];
            }

            return [
                'success' => false,
                'transId' => null,
                'message' => (string) ($result['vnp_Message'] ?? 'Lỗi không xác định từ VNPay.'),
            ];
        } catch (\Exception $e) {
            // Lỗi mạng/timeout khi gọi API, không phải VNPay từ
            Log::error('VNPay refund API error: ' . $e->getMessage());
            return ['success' => false, 'transId' => null, 'message' => 'Không thể kết nối cổng thanh toán VNPay.'];
        }
    }


    // Lễ tân/admin bấm "Hoàn tiền & Hủy đơn", gọi API VNPay
    public function refundOrder(Request $request, Order $order)
    {
        if (!$this->configValid) {
            return $this->refundError($request, 'Chưa cấu hình VNPay cho môi trường chính thức. Vui lòng liên hệ quản trị viên.');
        }

        // Bắt buộc nhập lý do hủy, tối thiểu 5 ký tự
        $validated = $request->validate([
            'cancel_reason' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'cancel_reason.required' => 'Vui lòng nhập lý do hủy đơn (tối thiểu 5 ký tự).',
            'cancel_reason.min' => 'Vui lòng nhập lý do hủy đơn (tối thiểu 5 ký tự).',
        ]);

        // Chỉ hoàn tiền được đơn VNPay đã thanh toán thật
        if ($order->payment_method !== 'vnpay' || $order->payment_status !== 'paid') {
            return $this->refundError($request, 'Đơn hàng này không cần hoàn tiền VNPay.');
        }
        // Đơn đã giao/hoàn thành/hủy rồi thì không được hoàn tiền nữa
        if (!in_array($order->status, ['pending', 'confirmed'], true)) {
            return $this->refundError($request, 'Chỉ có thể hoàn tiền cho đơn đang chờ xác nhận/đã xác nhận.');
        }
        // Không có mã giao dịch gốc thì VNPay không biết hoàn
        if (!$order->payment_transaction_id) {
            return $this->refundError($request, 'Không tìm thấy mã giao dịch gốc để hoàn tiền.');
        }

        // Gọi API hoàn tiền thật của VNPay
        $result = $this->requestRefund($order);

        if (!$result['success']) {
            Log::error('VNPay refund failed', ['orderId' => $order->order_code, 'message' => $result['message']]);
            return $this->refundError($request, 'Hoàn tiền VNPay thất bại: ' . $result['message']);
        }

        // VNPay hoàn tiền thành công -> cập nhật đơn sang
        try {
            $this->orderWorkflow->refundAndCancel($order, $result['transId'], $validated['cancel_reason']);
        } catch (ValidationException $e) {
            return $this->refundError($request, collect($e->errors())->flatten()->first(), $e->errors());
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Đã hoàn tiền và hủy đơn hàng thành công!']);
        }
        return back()->with('success', 'Đã hoàn tiền và hủy đơn hàng thành công!');
    }

    // Trả lỗi hoàn tiền JSON
    private function refundError(Request $request, string $message, array $errors = [])
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'errors' => $errors, 'message' => $message], 422);
        }
        return back()->withErrors(['refund' => $message]);
    }

     // Lễ tân bấm "Thanh toán VNPay" cho một đơn tại quầy đã
    public function payExistingOrder(Request $request, Order $order)
    {
        if (!$this->configValid) {
            return $this->checkoutError($request, 'Chưa cấu hình VNPay cho môi trường chính thức. Vui lòng chọn thanh toán tiền mặt hoặc liên hệ quản trị viên.');
        }

        if ($order->payment_method !== 'vnpay' || $order->payment_status === 'paid') {
            return $this->checkoutError($request, 'Đơn hàng này không cần thanh toán qua VNPay.');
        }
           //buildPaymentUrl() tạo link thanh toán VNPay cho $order, rồi checkoutRedirect() đưa link đó ra cho client
        return $this->checkoutRedirect($request, $this->buildPaymentUrl($order, $request));
    }

    // xác nhận chữ ký
    private function verifySignature(array $data): bool
    {
        // Không có chữ ký gửi kèm -> chắc chắn không hợp lệ
        if (empty($data['vnp_SecureHash'])) {
            return false;
        }

        $receivedHash = (string) $data['vnp_SecureHash'];
        // Bỏ 2 trường này ra vì chúng không tham gia vào chuỗi
        unset($data['vnp_SecureHash'], $data['vnp_SecureHashType']);

        // Sắp key theo alphabet, đúng thứ tự VNPay dùng khi ký
        ksort($data);

        // Build lại chuỗi key=value nối bằng "&", y hệt thuật toán ở buildPaymentUrl()
        $hashData = '';
        $first = true;
        foreach ($data as $key => $value) {
            if (!$first) {
                $hashData .= '&';
            }
            $hashData .= urlencode((string) $key) . '=' . urlencode((string) $value);
            $first = false;
        }

        // Tự tính lại hash bằng secret key của mình, để so sánh
        $expectedHash = hash_hmac('sha512', $hashData, $this->hashSecret);

        // hash_equals() so sánh an toàn, chống timing attack, không dùng === thường
        return hash_equals($expectedHash, $receivedHash);
    }

   // Hàm này dọn giỏ hàng của khách sau khi đặt hàng VNPay
    private function clearOrderedCartItems(Order $order): void
    {
        $cart = Cart::query()->where('user_id', $order->user_id)->first();
        if (!$cart) {
            return;
        }

        $cartItemIds = CartItem::query()
            ->where('cart_id', $cart->id)
            ->pluck('id')
            ->toArray();

        if (!empty($cartItemIds)) {
            CartItemTopping::query()->whereIn('cart_item_id', $cartItemIds)->delete();
            CartItem::query()->whereIn('id', $cartItemIds)->delete();
        }
    }

    // NHẬN PHẢN HỒI THANH TOÁN VN PAY
    public function handleReturn(Request $request)
    {
        $data = $request->query();
        $orderId = $data['vnp_TxnRef'] ?? null;
        $responseCode = $data['vnp_ResponseCode'] ?? null;

        Log::info('VNPay return received', [
            'vnp_TxnRef' => $orderId,
            'vnp_ResponseCode' => $responseCode,
            'vnp_TransactionNo' => $data['vnp_TransactionNo'] ?? null,
        ]);

        $order = $orderId ? Order::query()->where('order_code', $orderId)->first() : null;
        if (!$order) {
            return redirect()->route('checkout')->with('error', 'Không tìm thấy đơn hàng.');
        }

        if (!$this->verifySignature($data)) {//Xác thực chữ ký
            Log::warning('VNPay return: invalid or missing signature', ['orderId' => $orderId]);
            return redirect()->route('orders')->with('info', "Đang chờ xác nhận thanh toán từ VNPay cho đơn hàng {$orderId}.");
        }

        if ((string) $responseCode === '00') {
            $amount = ((float) ($data['vnp_Amount'] ?? 0)) / 100;
            // chuyển chuỗi ngày giờ VNPay gửi thành
            $paidAt = $this->parseVnpPayDate($data['vnp_PayDate'] ?? null);// chuyển chuỗi ngày giờ dạng VNPay gửi về thành object

            try {
                // chuyển đơn sang trạng thái paid chính thức
                $this->orderWorkflow->markPaid($order, (string) ($data['vnp_TransactionNo'] ?? ''), $amount, $paidAt);
            } catch (ValidationException $e) {
                Log::warning('VNPay return: amount mismatch', ['orderId' => $orderId]);
                return redirect()->route('orders')->with('error', 'Số tiền thanh toán không khớp đơn hàng, vui lòng liên hệ hỗ trợ.');
            }

            $this->clearOrderedCartItems($order);

            if ($order->delivery_type === 'pickup') {
                return redirect()->route('staff.reception.orders.show', $order->id)
                    ->with('success', "Thanh toán VNPay thành công! Đơn hàng {$orderId} đã được xác nhận.");
            }

            return redirect()->route('orders')->with('success', "Thanh toán VNPay thành công! Đơn hàng {$orderId} đã được xác nhận.");
        }

        // Thanh toán thất bại / người dùng ấn Hủy giao dịch
        if ($order->delivery_type === 'pickup') {
            return redirect()->route('staff.reception.orders.show', $order->id)
                ->with('error', 'Thanh toán VNPay chưa hoàn tất. Bạn có thể thử lại hoặc hủy đơn.');
        }

        if (in_array($order->payment_status, ['unpaid', 'failed'])) {
            // Xóa trước, chỉ hoàn lượt mã nếu chính request này xóa
            // cùng đơn này song song, hoàn lượt 2 lần sẽ làm used_count âm = mã dùng vượt giới hạn.
            if (Order::query()->where('id', $order->id)->delete() > 0) {
                OrderItem::query()->where('order_id', $order->id)->delete();

                if ($order->promotion_id) {
                    Promotion::query()->where('id', $order->promotion_id)->where('used_count', '>', 0)->decrement('used_count');
                }
            }
        }

        return redirect()->route('checkout')->with('error', 'Bạn đã hủy thanh toán VNPay. Giỏ hàng của bạn vẫn được giữ nguyên.');
    }

   // VNPay gọi về, server-to-server để xác nhận thanh toán
    public function handleIpn(Request $request)
    {
        $data = $request->query();
        Log::info('VNPay IPN received', $data); // Ghi log mọi lần gọi tới, kể cả khi bị từ chối ở bước sau

        // Chữ ký sai -> không tin request này, có thể là giả mạo
        if (!$this->verifySignature($data)) {
            Log::warning('VNPay IPN: Invalid signature', $data);
            return response()->json(['RspCode' => '97', 'Message' => 'Invalid signature']);
        }

        // Không tìm thấy đơn khớp mã vnp_TxnRef -> báo VNPay
        $orderId = $data['vnp_TxnRef'] ?? null;
        $order = $orderId ? Order::query()->where('order_code', $orderId)->first() : null;
        if (!$order) {
            Log::warning('VNPay IPN: order not found', ['orderId' => $orderId]);
            return response()->json(['RspCode' => '01', 'Message' => 'Order not found']);
        }

        // Đối chiếu số tiền VNPay báo về với số tiền đơn hàng
        $vnpAmount = ((float) ($data['vnp_Amount'] ?? 0)) / 100;
        if (abs((float) $order->final_amount - $vnpAmount) > 0.01) {
            Log::warning('VNPay IPN: amount mismatch', ['orderId' => $orderId]);
            return response()->json(['RspCode' => '04', 'Message' => 'Invalid amount']);
        }

        $responseCode = (string) ($data['vnp_ResponseCode'] ?? '');
        $transactionStatus = (string) ($data['vnp_TransactionStatus'] ?? '');

        // Cả 2 mã đều phải là '00' mới coi là thanh toán thành công
        if ($responseCode === '00' && $transactionStatus === '00') {
            if ($order->payment_status === 'paid') {
                // Idempotent replay, VNPay tự động gọi lại IPN nhiều
                Log::info("VNPay IPN: Order {$orderId} already confirmed, replay ignored.");
                return response()->json(['RspCode' => '02', 'Message' => 'Order already confirmed']);
            }

            $paidAt = $this->parseVnpPayDate($data['vnp_PayDate'] ?? null);//chuyển chuỗi ngày giờ dạng VNPay gửi về thành object

            try {
                // Đây là nơi thực sự chuyển đơn sang trạng thái 'paid'
                $this->orderWorkflow->markPaid($order, (string) ($data['vnp_TransactionNo'] ?? ''), $vnpAmount, $paidAt);
            } catch (ValidationException $e) {
                Log::warning('VNPay IPN: amount mismatch on markPaid', ['orderId' => $orderId]);
                return response()->json(['RspCode' => '04', 'Message' => 'Invalid amount']);
            }

            $this->clearOrderedCartItems($order); // Xóa các sản phẩm đã đặt khỏi giỏ hàng

            Log::info("VNPay IPN: Order {$orderId} marked as PAID");
        } elseif ($order->delivery_type === 'pickup') {
            // Đơn tại quầy: giữ nguyên đơn để lễ tân tự xử lý tiếp
            Log::info("VNPay IPN: Pickup order {$orderId} payment failed (responseCode={$responseCode}), kept for retry.");
        } else {
            // Đơn giao hàng thanh toán thất bại: xóa hẳn đơn tạm +
            if (Order::query()->where('id', $order->id)->delete() > 0) {
                OrderItem::query()->where('order_id', $order->id)->delete();

                if ($order->promotion_id) {
                    Promotion::query()->where('id', $order->promotion_id)->where('used_count', '>', 0)->decrement('used_count');
                }
            }
            Log::info("VNPay IPN: Order {$orderId} marked as FAILED (responseCode={$responseCode})");
        }

        // Luôn trả RspCode 00 ở cuối, dù đơn thất bại, báo cho
        return response()->json(['RspCode' => '00', 'Message' => 'Confirm Success']);
    }
    // chuyển chuỗi ngày giờ dạng VNPay gửi về thành object
    private function parseVnpPayDate(?string $vnpPayDate): ?Carbon
    {
        if (!$vnpPayDate) {
            return null;
        }
        try {
            return Carbon::createFromFormat('YmdHis', $vnpPayDate);
        } catch (\Exception $e) {
            return null;
        }
    }
}
