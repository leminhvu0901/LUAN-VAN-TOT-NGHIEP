<?php

namespace App\Http\Controllers\Backend\Staff\Delivery;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class DashboardController
{
    // Hiển thị trang Dashboard tổng quan của Shipper
    public function index()
    {
        $staffId = Auth::id(); // Lấy ID tài khoản Shipper đang đăng nhập

        // 1. Thống kê số lượng đơn hàng do shipper này phụ trách
        $pendingPickupCount = Order::where('delivery_staff_id', $staffId)->where('status', 'confirmed')->count(); // Đơn đã được gán nhưng chờ shipper đến nhận tại quầy
        $shippingCount = Order::where('delivery_staff_id', $staffId)->where('status', 'shipping')->count(); // Đơn shipper đang trên đường đi giao
        $completedCount = Order::where('delivery_staff_id', $staffId)->where('status', 'completed')->count(); // Tổng đơn shipper giao thành công
        $failedCount = Order::where('delivery_staff_id', $staffId)->whereNotNull('delivery_failed_at')->count(); // Tổng số đơn giao thất bại của shipper này

        // 2. Thống kê tiền mặt thu hộ (COD)
        $codToCollect = (float) Order::where('delivery_staff_id', $staffId)
            ->where('status', 'shipping')->where('payment_method', 'cod')->sum('final_amount');

        // Tổng số tiền COD shipper thực tế đã thu được từ các
        $codCollectedTotal = (float) Order::where('delivery_staff_id', $staffId)
            ->where('status', 'completed')->where('payment_method', 'cod')->sum('final_amount');

        // Trong số đã thu, chia làm 2 phần:
        $codUnsettledTotal = (float) Order::where('delivery_staff_id', $staffId)
            ->where('status', 'completed')->where('payment_method', 'cod')
            ->whereNull('cod_settled_at')->sum('final_amount');

        // Số tiền COD shipper đã nộp lại cho quầy thành công (đã
        $codSettledTotal = $codCollectedTotal - $codUnsettledTotal;

        // Lấy danh sách 5 đơn hàng mới nhất đang được phân
        $recentOrders = Order::where('delivery_staff_id', $staffId)
            ->whereIn('status', ['confirmed', 'shipping'])
            ->latest('assigned_at')->limit(5)->get();

        return view('backend.staff.delivery.dashboard', compact( // Đẩy số liệu ra giao diện hiển thị
            'pendingPickupCount',
            'shippingCount',
            'completedCount',
            'failedCount',
            'codToCollect',
            'codCollectedTotal',
            'codUnsettledTotal',
            'codSettledTotal',
            'recentOrders'
        ));
    }
}
