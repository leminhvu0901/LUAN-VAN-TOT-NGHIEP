<?php

namespace App\Http\Controllers\Frontend;

use App\Models\Order;
use Illuminate\Http\Request;

/**
 * Dispatcher dùng chung cho các hành động thao tác trên 1 đơn đã tồn tại mà route theo đúng cổng
 * thanh toán của đơn đó (momo/vnpay) — tránh nhân đôi route/nút bấm cho mỗi cổng thanh toán mới thêm
 * vào. "Hoàn tiền & Hủy đơn" (lễ tân + admin) và "Thanh toán lại" (lễ tân, đơn tại quầy) đều đi qua đây.
 */
class OnlinePaymentGatewayController
{
    public function refund(Request $request, Order $order)
    {
        return match ($order->payment_method) {
            'momo' => app(MomoController::class)->refundOrder($request, $order),
            'vnpay' => app(VnpayController::class)->refundOrder($request, $order),
            default => response()->json(['success' => false, 'message' => 'Đơn hàng này không cần hoàn tiền.'], 422),
        };
    }

    public function payExisting(Request $request, Order $order)
    {
        return match ($order->payment_method) {
            'momo' => app(MomoController::class)->payExistingOrder($request, $order),
            'vnpay' => app(VnpayController::class)->payExistingOrder($request, $order),
            default => response()->json(['success' => false, 'message' => 'Đơn hàng này không cần thanh toán qua cổng thanh toán online.'], 422),
        };
    }
}
