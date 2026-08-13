<?php

namespace App\Http\Controllers\Frontend;

use App\Models\Order;
use Illuminate\Http\Request;

class OnlinePaymentGatewayController
{

    // Điều hướng và xử lý hoàn tiền đơn hàng thanh toán online.
    public function refund(Request $request, Order $order)
    {
        return match ($order->payment_method) { // Dùng cấu trúc match rẽ nhánh theo phương thức thanh toán
            'vnpay' => app(VnpayController::class)->refundOrder($request, $order), // Gọi hàm hoàn tiền của VnpayController xử lý yêu cầu qua API VNPay
            default => $request->expectsJson()
            ? response()->json(['success' => false, 'message' => 'Đơn hàng này không cần hoàn tiền.'], 422)
            : back()->withErrors(['refund' => 'Đơn hàng này không cần hoàn tiền.']),
        };
    }

   // Điều hướng và xử lý tạo lại liên kết thanh toán cho
    public function payExisting(Request $request, Order $order)
    {
        return match ($order->payment_method) { // Dùng cấu trúc match rẽ nhánh theo phương thức thanh toán
            'vnpay' => app(VnpayController::class)->payExistingOrder($request, $order), // Gọi hàm thanh toán lại của VnpayController xin link thanh toán VNPay mới
            default => response()->json(['success' => false, 'message' => 'Đơn hàng này không cần thanh toán qua cổng thanh toán online.'], 422), // Trả lỗi 422 nếu đơn không cần thanh toán online
        };
    }
}
