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
    /**
     * Điều hướng và xử lý hoàn tiền đơn hàng thanh toán online.
     * 
     * Phản hồi trả về:
     * - Trả dữ liệu JSON từ MomoController/VnpayController về cho JS xử lý thông báo 
     *   tại trang chi tiết đơn hàng của Admin [public/js/backend/admin/orders/show.js] 
     *   hoặc Lễ tân [public/js/backend/staff/reception/orders/show.js].
     */
    public function refund(Request $request, Order $order)
    {
        return match ($order->payment_method) { // Dùng cấu trúc match rẽ nhánh theo phương thức thanh toán
            'momo' => app(MomoController::class)->refundOrder($request, $order), // Gọi hàm hoàn tiền của MomoController xử lý yêu cầu qua API MoMo
            'vnpay' => app(VnpayController::class)->refundOrder($request, $order), // Gọi hàm hoàn tiền của VnpayController xử lý yêu cầu qua API VNPay
            default => response()->json(['success' => false, 'message' => 'Đơn hàng này không cần hoàn tiền.'], 422), // Trả lỗi 422 nếu đơn hàng không dùng cổng online
        };
    }
 
    /**
     * Điều hướng và xử lý tạo lại liên kết thanh toán cho đơn hàng online đã tồn tại.
     * 
     * Phản hồi trả về:
     * - Trả link chuyển hướng thanh toán (MoMo/VNPay) cho JS xử lý tại màn hình POS 
     *   của Lễ tân [public/js/backend/staff/reception/orders/create.js] hoặc trang thanh toán của khách hàng.
     */
    public function payExisting(Request $request, Order $order)
    {
        return match ($order->payment_method) { // Dùng cấu trúc match rẽ nhánh theo phương thức thanh toán
            'momo' => app(MomoController::class)->payExistingOrder($request, $order), // Gọi hàm thanh toán lại của MomoController xin link QR quét MoMo mới
            'vnpay' => app(VnpayController::class)->payExistingOrder($request, $order), // Gọi hàm thanh toán lại của VnpayController xin link thanh toán VNPay mới
            default => response()->json(['success' => false, 'message' => 'Đơn hàng này không cần thanh toán qua cổng thanh toán online.'], 422), // Trả lỗi 422 nếu đơn không cần thanh toán online
        };
    }
}
