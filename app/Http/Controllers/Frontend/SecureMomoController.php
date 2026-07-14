<?php

namespace App\Http\Controllers\Frontend;

use App\Models\Order;
use App\Services\OrderService;
use App\Services\OrderWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SecureMomoController
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly OrderWorkflowService $workflow,
    ) {}

    public function createPayment(Request $request)
    {
        $validated = $request->validate([
            'address_id' => ['required', 'integer'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'note' => ['nullable', 'string', 'max:500'],
            'idempotency_key' => ['required', 'uuid'],
        ]);
        if (!hash_equals((string) session('checkout_token'), $validated['idempotency_key'])) {
            throw ValidationException::withMessages(['checkout' => 'Phiên thanh toán đã hết hạn, vui lòng tải lại trang.']);
        }
        $this->ensureConfigured();

        $order = $this->orders->create(Auth::user(), $validated, 'momo');
        if ($order->status === 'cancelled') {
            throw ValidationException::withMessages(['checkout' => 'Giao dịch trước đã hủy, vui lòng tải lại trang thanh toán.']);
        }

        $requestId = $order->order_code . '-' . bin2hex(random_bytes(4));
        $amount = (string) (int) round((float) $order->final_amount);
        $payload = [
            'partnerCode' => config('services.momo.partner_code'),
            'partnerName' => config('app.name'),
            'storeId' => config('app.name'),
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => $order->order_code,
            'orderInfo' => 'Thanh toan don hang ' . $order->order_code,
            'redirectUrl' => route('momo.return'),
            'ipnUrl' => route('momo.ipn'),
            'lang' => 'vi',
            'requestType' => 'captureWallet',
            'autoCapture' => true,
            'extraData' => '',
        ];
        $raw = 'accessKey=' . config('services.momo.access_key')
            . '&amount=' . $amount . '&extraData=&ipnUrl=' . $payload['ipnUrl']
            . '&orderId=' . $order->order_code . '&orderInfo=' . $payload['orderInfo']
            . '&partnerCode=' . $payload['partnerCode'] . '&redirectUrl=' . $payload['redirectUrl']
            . '&requestId=' . $requestId . '&requestType=captureWallet';
        $payload['signature'] = hash_hmac('sha256', $raw, config('services.momo.secret_key'));

        try {
            $response = Http::timeout(30)->post(config('services.momo.endpoint'), $payload)->throw()->json();
            if (($response['resultCode'] ?? null) === 0 && !empty($response['payUrl'])) {
                session()->forget('checkout_token');
                return redirect()->away($response['payUrl']);
            }
            $this->workflow->transition($order, 'cancelled', 'Không thể khởi tạo thanh toán MoMo.');
            return back()->with('error', $response['message'] ?? 'Không thể khởi tạo thanh toán MoMo.');
        } catch (\Throwable $exception) {
            Log::error('MoMo create payment failed', ['order' => $order->order_code, 'error' => $exception->getMessage()]);
            $this->workflow->transition($order, 'cancelled', 'Kết nối MoMo thất bại khi khởi tạo giao dịch.');
            return back()->with('error', 'Không thể kết nối MoMo, vui lòng thử lại.');
        }
    }

    public function handleReturn(Request $request)
    {
        if (!$this->hasValidResultSignature($request->all())) {
            return redirect()->route('orders')->with('error', 'Kết quả MoMo không hợp lệ.');
        }
        $order = Order::query()->where('order_code', $request->query('orderId'))
            ->where('user_id', Auth::id())->firstOrFail();

        if ((int) $request->query('resultCode') === 0) {
            $this->workflow->markPaid($order, (string) $request->query('transId'), (float) $request->query('amount'));
            return redirect()->route('orders', ['open_order' => $order->id])->with('success', 'Thanh toán MoMo thành công.');
        }

        if ($order->status === 'pending' && $order->payment_status === 'unpaid') {
            $this->workflow->transition($order, 'cancelled', 'Khách hàng hủy hoặc thanh toán MoMo thất bại.');
        }
        return redirect()->route('orders')->with('error', 'Thanh toán MoMo không thành công.');
    }

    public function handleIpn(Request $request)
    {
        $data = $request->all();
        if (!$this->hasValidResultSignature($data)) {
            Log::warning('MoMo IPN invalid signature', ['orderId' => $data['orderId'] ?? null]);
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        $order = Order::query()->where('order_code', $data['orderId'])->first();
        if (!$order || (string) $data['partnerCode'] !== (string) config('services.momo.partner_code')) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        try {
            if ((int) $data['resultCode'] === 0) {
                $this->workflow->markPaid($order, (string) $data['transId'], (float) $data['amount']);
            } elseif ($order->status === 'pending' && $order->payment_status === 'unpaid') {
                $this->workflow->transition($order, 'cancelled', 'MoMo xác nhận giao dịch thanh toán thất bại.');
            }
        } catch (ValidationException $exception) {
            Log::warning('MoMo IPN rejected', ['order' => $order->order_code, 'errors' => $exception->errors()]);
            return response()->json(['message' => 'Amount mismatch'], 422);
        }

        return response()->json(['message' => 'Success']);
    }

    private function hasValidResultSignature(array $data): bool
    {
        $keys = ['partnerCode', 'orderId', 'requestId', 'amount', 'orderInfo', 'orderType', 'transId', 'resultCode', 'message', 'payType', 'responseTime', 'extraData', 'signature'];
        foreach ($keys as $key) if (!array_key_exists($key, $data)) return false;
        $raw = 'accessKey=' . config('services.momo.access_key') . '&amount=' . $data['amount']
            . '&extraData=' . $data['extraData'] . '&message=' . $data['message'] . '&orderId=' . $data['orderId']
            . '&orderInfo=' . $data['orderInfo'] . '&orderType=' . $data['orderType']
            . '&partnerCode=' . $data['partnerCode'] . '&payType=' . $data['payType']
            . '&requestId=' . $data['requestId'] . '&responseTime=' . $data['responseTime']
            . '&resultCode=' . $data['resultCode'] . '&transId=' . $data['transId'];
        $expected = hash_hmac('sha256', $raw, (string) config('services.momo.secret_key'));
        return hash_equals($expected, (string) $data['signature']);
    }

    private function ensureConfigured(): void
    {
        foreach (['partner_code', 'access_key', 'secret_key', 'endpoint'] as $key) {
            if (!config("services.momo.{$key}")) {
                throw ValidationException::withMessages(['payment' => 'Cấu hình MoMo chưa đầy đủ.']);
            }
        }
    }
}
