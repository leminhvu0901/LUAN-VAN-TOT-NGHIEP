<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Frontend\Concerns\CreatesOnlinePaymentOrder;
use App\Http\Controllers\Frontend\Concerns\HandlesCheckoutResponse;
use App\Services\OrderWorkflowService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class VnpayController
{
    use HandlesCheckoutResponse, CreatesOnlinePaymentOrder;

    // ─── Cấu hình VNPay, chọn theo Settings admin (payment_environment: sandbox/production) ──
    private string $tmnCode;
    private string $hashSecret;
    private string $vnpUrl;
    private string $refundEndpoint;
    private bool $isProduction;
    private bool $configValid;

    public function __construct(private readonly OrderWorkflowService $orderWorkflow)
    {
        $this->isProduction = \App\Models\Setting::getValue('payment_environment', 'sandbox') === 'production';
        $vnpayConfig = config('services.vnpay.' . ($this->isProduction ? 'production' : 'sandbox'), []);

        $this->tmnCode = (string) ($vnpayConfig['tmn_code'] ?? '');
        $this->hashSecret = (string) ($vnpayConfig['hash_secret'] ?? '');
        $this->vnpUrl = (string) ($vnpayConfig['url'] ?? '');
        $this->refundEndpoint = (string) ($vnpayConfig['refund_endpoint'] ?? '');

        $this->configValid = $this->tmnCode !== '' && $this->hashSecret !== '';
    }

    /**
     * Được gọi khi khách chọn VNPay và bấm "Đặt hàng".
     * Tạo đơn hàng trong DB (payment_status = unpaid, dùng chung createPendingOrderForOnlinePayment()
     * với MoMo) rồi điều hướng sang cổng VNPay.
     */
    public function createPayment(Request $request)
    {
        $result = $this->createPendingOrderForOnlinePayment($request, 'vnpay');//tạo một đơn hàng tạm thời
        if (!($result instanceof \App\Models\Order)) {
            return $result;
        }
        $order = $result;

        return $this->checkoutRedirect($request, $this->buildPaymentUrl($order, $request));
    }

    /**
     * Build URL thanh toán VNPay đã ký chữ ký — KHÁC MoMo: không gọi API mạng nào, tự build query
     * string có chữ ký rồi redirect thẳng trình duyệt sang đó. Theo đúng thuật toán trong tài liệu
     * tích hợp chính thức VNPay (sandbox.vnpayment.vn/apis/docs/thanh-toan-pay/pay.html).
     */
    private function buildPaymentUrl(\App\Models\Order $order, Request $request): string
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

    /**
     * Gọi API hoàn tiền VNPay cho một đơn đã thanh toán. Public (không private) vì
     * Delivery\OrderController cũng cần gọi trực tiếp cho case "hàng hư hỏng" — cùng tiền lệ với
     * MomoController::requestRefund(). Chữ ký ký theo chuỗi nối bằng "|" (KHÁC hẳn query-string "&"
     * ở bước thanh toán) — theo đúng tài liệu API hoàn tiền/tra soát VNPay
     * (/merchant_webapi/api/transaction).
     *
     * @return array{success: bool, transId: ?string, message: string}
     */
    public function requestRefund(\App\Models\Order $order): array
    {
        $requestId = 'RF' . $order->order_code . '_' . time();
        $amountStr = (string) ((int) round((float) $order->final_amount) * 100);
        $transactionNo = (string) $order->payment_transaction_id;
        $transactionDate = $order->paid_at ? $order->paid_at->format('YmdHis') : now()->format('YmdHis');
        $orderInfo = 'Hoan tien don hang ' . $order->order_code;
        $createDate = now()->timezone('Asia/Ho_Chi_Minh')->format('YmdHis');
        $ipAddr = request()?->ip() ?: '127.0.0.1';
        $createBy = 'system';

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
            $httpClient = Http::timeout(30);
            if (!$this->isProduction) {
                $httpClient = $httpClient->withoutVerifying();
            }
            $response = $httpClient->post($this->refundEndpoint, $payload);
            $result = $response->json();

            Log::info('VNPay refund response', $result ?? []);

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
            Log::error('VNPay refund API error: ' . $e->getMessage());
            return ['success' => false, 'transId' => null, 'message' => 'Không thể kết nối cổng thanh toán VNPay.'];
        }
    }

    /**
     * Lễ tân/admin bấm "Hoàn tiền & Hủy đơn" cho một đơn VNPay đã thanh toán — copy y hệt logic
     * MomoController::refundOrder(), chỉ đổi guard/message theo VNPay.
     */
    public function refundOrder(Request $request, \App\Models\Order $order)
    {
        if (!$this->configValid) {
            return response()->json(['success' => false, 'message' => 'Chưa cấu hình VNPay cho môi trường chính thức. Vui lòng liên hệ quản trị viên.'], 422);
        }

        $validated = $request->validate([
            'cancel_reason' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'cancel_reason.required' => 'Vui lòng nhập lý do hủy đơn (tối thiểu 5 ký tự).',
            'cancel_reason.min' => 'Vui lòng nhập lý do hủy đơn (tối thiểu 5 ký tự).',
        ]);

        if ($order->payment_method !== 'vnpay' || $order->payment_status !== 'paid') {
            return response()->json(['success' => false, 'message' => 'Đơn hàng này không cần hoàn tiền VNPay.'], 422);
        }
        if (!in_array($order->status, ['pending', 'confirmed'], true)) {
            return response()->json(['success' => false, 'message' => 'Chỉ có thể hoàn tiền cho đơn đang chờ xác nhận/đã xác nhận.'], 422);
        }
        if (!$order->payment_transaction_id) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy mã giao dịch gốc để hoàn tiền.'], 422);
        }

        $result = $this->requestRefund($order);

        if (!$result['success']) {
            Log::error('VNPay refund failed', ['orderId' => $order->order_code, 'message' => $result['message']]);
            return response()->json(['success' => false, 'message' => 'Hoàn tiền VNPay thất bại: ' . $result['message']], 422);
        }

        try {
            $this->orderWorkflow->refundAndCancel($order, $result['transId'], $validated['cancel_reason']);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors(), 'message' => collect($e->errors())->flatten()->first()], 422);
        }

        return response()->json(['success' => true, 'message' => 'Đã hoàn tiền và hủy đơn hàng thành công!']);
    }

    /**
     * Lễ tân bấm "Thanh toán VNPay" cho một đơn tại quầy đã tồn tại — copy logic
     * MomoController::payExistingOrder(), gọi buildPaymentUrl() thay vì requestPayUrl().
     */
    public function payExistingOrder(Request $request, \App\Models\Order $order)
    {
        if (!$this->configValid) {
            return $this->checkoutError($request, 'Chưa cấu hình VNPay cho môi trường chính thức. Vui lòng chọn thanh toán tiền mặt hoặc liên hệ quản trị viên.');
        }

        if ($order->payment_method !== 'vnpay' || $order->payment_status === 'paid') {
            return $this->checkoutError($request, 'Đơn hàng này không cần thanh toán qua VNPay.');
        }

        return $this->checkoutRedirect($request, $this->buildPaymentUrl($order, $request));
    }

    /**
     * Xác thực chữ ký VNPay gửi kèm (dùng chung cho cả redirect về trình duyệt và IPN server-to-server).
     * Bỏ vnp_SecureHash/vnp_SecureHashType, ksort() phần còn lại, rebuild query string y hệt cách ký
     * ở buildPaymentUrl(), so sánh bằng hash_equals().
     */
    private function verifySignature(array $data): bool
    {
        if (empty($data['vnp_SecureHash'])) {
            return false;
        }

        $receivedHash = (string) $data['vnp_SecureHash'];
        unset($data['vnp_SecureHash'], $data['vnp_SecureHashType']);

        ksort($data);

        $hashData = '';
        $first = true;
        foreach ($data as $key => $value) {
            if (!$first) {
                $hashData .= '&';
            }
            $hashData .= urlencode((string) $key) . '=' . urlencode((string) $value);
            $first = false;
        }

        $expectedHash = hash_hmac('sha512', $hashData, $this->hashSecret);

        return hash_equals($expectedHash, $receivedHash);
    }

    /**
     * Xóa CHỈ những cart_items đã được đưa vào đơn hàng này (dựa theo product_id) — dùng chung cho
     * cả handleReturn/handleIpn, y hệt logic MomoController.
     */
    private function clearOrderedCartItems(\App\Models\Order $order): void
    {
        $cart = \App\Models\Cart::query()->where('user_id', $order->user_id)->first();
        if (!$cart) {
            return;
        }

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

    /**
     * Xử lý sau khi khách thanh toán xong và VNPay redirect về trình duyệt. VNPay ký cả redirect này
     * (không chỉ IPN) nên có thể xác thực chữ ký và tin cậy để cập nhật DB — cần thiết vì môi trường
     * demo/local thường không có URL public để VNPay gọi IPN server-to-server.
     */
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

        $order = $orderId ? \App\Models\Order::query()->where('order_code', $orderId)->first() : null;
        if (!$order) {
            return redirect()->route('checkout')->with('error', 'Không tìm thấy đơn hàng.');
        }

        if (!$this->verifySignature($data)) {//Xác thực chữ ký
            Log::warning('VNPay return: invalid or missing signature', ['orderId' => $orderId]);
            return redirect()->route('orders')->with('info', "Đang chờ xác nhận thanh toán từ VNPay cho đơn hàng {$orderId}.");
        }

        if ((string) $responseCode === '00') {
            $amount = ((float) ($data['vnp_Amount'] ?? 0)) / 100;
            $paidAt = $this->parseVnpPayDate($data['vnp_PayDate'] ?? null);

            try {
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
            \App\Models\OrderItem::query()->where('order_id', $order->id)->delete();
            \App\Models\Order::query()->where('id', $order->id)->delete();

            if ($order->promotion_id) {
                \App\Models\Promotion::query()->where('id', $order->promotion_id)->decrement('used_count');
            }
        }

        return redirect()->route('checkout')->with('error', 'Bạn đã hủy thanh toán VNPay. Giỏ hàng của bạn vẫn được giữ nguyên.');
    }

    /**
     * IPN (Instant Payment Notification) - VNPay gọi ngầm vào đây (GET) sau giao dịch. Đây là nơi cập
     * nhật trạng thái DB chính thức và đáng tin cậy nhất. KHÁC MoMo: PHẢI luôn trả JSON
     * {"RspCode":...,"Message":...} với HTTP 200 — sai định dạng khiến VNPay retry vô hạn.
     */
    public function handleIpn(Request $request)
    {
        $data = $request->query();
        Log::info('VNPay IPN received', $data);

        if (!$this->verifySignature($data)) {
            Log::warning('VNPay IPN: Invalid signature', $data);
            return response()->json(['RspCode' => '97', 'Message' => 'Invalid signature']);
        }

        $orderId = $data['vnp_TxnRef'] ?? null;
        $order = $orderId ? \App\Models\Order::query()->where('order_code', $orderId)->first() : null;
        if (!$order) {
            Log::warning('VNPay IPN: order not found', ['orderId' => $orderId]);
            return response()->json(['RspCode' => '01', 'Message' => 'Order not found']);
        }

        $vnpAmount = ((float) ($data['vnp_Amount'] ?? 0)) / 100;
        if (abs((float) $order->final_amount - $vnpAmount) > 0.01) {
            Log::warning('VNPay IPN: amount mismatch', ['orderId' => $orderId]);
            return response()->json(['RspCode' => '04', 'Message' => 'Invalid amount']);
        }

        $responseCode = (string) ($data['vnp_ResponseCode'] ?? '');
        $transactionStatus = (string) ($data['vnp_TransactionStatus'] ?? '');

        if ($responseCode === '00' && $transactionStatus === '00') {
            if ($order->payment_status === 'paid') {
                // Idempotent replay — VNPay tự động gọi lại IPN nhiều lần cho tới khi nhận được RspCode
                // hợp lệ. Đơn đã xử lý rồi -> KHÔNG chạy lại markPaid/xóa cart/gửi thông báo lần nữa.
                Log::info("VNPay IPN: Order {$orderId} already confirmed, replay ignored.");
                return response()->json(['RspCode' => '02', 'Message' => 'Order already confirmed']);
            }

            $paidAt = $this->parseVnpPayDate($data['vnp_PayDate'] ?? null);

            try {
                $this->orderWorkflow->markPaid($order, (string) ($data['vnp_TransactionNo'] ?? ''), $vnpAmount, $paidAt);
            } catch (ValidationException $e) {
                Log::warning('VNPay IPN: amount mismatch on markPaid', ['orderId' => $orderId]);
                return response()->json(['RspCode' => '04', 'Message' => 'Invalid amount']);
            }

            $this->clearOrderedCartItems($order);

            Log::info("VNPay IPN: Order {$orderId} marked as PAID");
        } elseif ($order->delivery_type === 'pickup') {
            Log::info("VNPay IPN: Pickup order {$orderId} payment failed (responseCode={$responseCode}), kept for retry.");
        } else {
            \App\Models\OrderItem::query()->where('order_id', $order->id)->delete();
            \App\Models\Order::query()->where('id', $order->id)->delete();

            if ($order->promotion_id) {
                \App\Models\Promotion::query()->where('id', $order->promotion_id)->decrement('used_count');
            }
            Log::info("VNPay IPN: Order {$orderId} marked as FAILED (responseCode={$responseCode})");
        }

        return response()->json(['RspCode' => '00', 'Message' => 'Confirm Success']);
    }

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
