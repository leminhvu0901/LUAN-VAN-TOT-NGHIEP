<?php

namespace App\Http\Controllers\Frontend;

use App\Models\Order;
use Illuminate\Http\Request;

class OnlinePaymentGatewayController
{

    // Xử lý trả lại tiền cho khách hàng khi đơn hàng bị hủy hoặc có sự cố.
    public function refund(Request $request, Order $order)
    {
        return match ($order->payment_method) { 
            'vnpay' => app(VnpayController::class)->refundOrder($request, $order), 
            default => $request->expectsJson()
            ? response()->json(['success' => false, 'message' => 'Đơn hàng này không cần hoàn tiền.'], 422)
            : back()->withErrors(['refund' => 'Đơn hàng này không cần hoàn tiền.']),
        };
    }

   // Tạo lại liên kết thanh toán (URL / QR VNPay) cho một đơn hàng cũ đang chờ thanh toán.
    public function payExisting(Request $request, Order $order)
    {
        return match ($order->payment_method) { 
            'vnpay' => app(VnpayController::class)->payExistingOrder($request, $order), 
            default => response()->json(['success' => false, 'message' => 'Đơn hàng này không cần thanh toán qua cổng thanh toán online.'], 422), // Trả lỗi 422 nếu đơn không cần thanh toán online
        };
    }
}
