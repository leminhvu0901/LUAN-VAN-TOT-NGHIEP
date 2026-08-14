<?php

namespace App\Http\Controllers\Backend\Staff\Reception;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Material;
use App\Models\MaterialImport;


class DashboardController
{
    // Hiển thị trang Dashboard tổng quan của quầy Lễ tân.
    public function index()
    {
        // Thống kê số lượng đơn hàng theo từng trạng thái cần chú ý
        $pendingOrdersCount = Order::where('status', 'pending')->count(); // Số lượng đơn chờ xác nhận
        $shippingOrdersCount = Order::where('status', 'shipping')->count(); // Số lượng đơn shipper đang đi giao
        $cancelledOrdersCount = Order::where('status', 'cancelled')->count(); // Số lượng đơn bị hủy

        // Đếm số đơn giao hàng đã xác nhận nhưng chưa gán
        $needsAssignmentCount = Order::where('delivery_type', 'delivery')
            ->where('status', 'confirmed')
            ->whereNull('delivery_staff_id')
            ->count();

        // Doanh thu hôm nay theo hình thức thanh toán, chỉ
        $todayRange = [today(), today()->endOfDay()]; // Khai báo khoảng thời gian từ 00:00:00 đến 23:59:59 ngày hôm nay

        $cashRevenueToday = (float) Order::whereIn('payment_method', ['cash', 'cod']) // Lấy các đơn trả bằng tiền mặt/tiền COD
            ->where('payment_status', 'paid')
            ->whereBetween('paid_at', $todayRange)
            ->sum('final_amount');
        $transferRevenueToday = (float) Order::where('payment_method', 'vnpay') // Lấy các đơn chuyển khoản VNPay
            ->where('payment_status', 'paid')
            ->whereBetween('paid_at', $todayRange)
            ->sum('final_amount');
        $totalRevenueToday = $cashRevenueToday + $transferRevenueToday; // Tính tổng doanh thu thực thu trong ngày

        // Tính toán tỷ lệ phần trăm đóng góp doanh thu của từng
        $cashRevenuePercent = $totalRevenueToday > 0 ? round($cashRevenueToday / $totalRevenueToday * 100) : 0;
        $transferRevenuePercent = $totalRevenueToday > 0 ? 100 - $cashRevenuePercent : 0;

        // Cảnh báo kho nguyên liệu pha chế
        $outOfStockMaterialsCount = Material::where('is_active', true)->where('current_stock', '<=', 0)->count(); // Nguyên liệu đã hết hoàn toàn
        $lowStockMaterialsCount = Material::where('is_active', true)->where('current_stock', '>', 0)->where('current_stock', '<', 5)->count(); // Cảnh báo tồn kho sắp hết, dưới 5 đơn vị

        // Thống kê số lô nguyên liệu sắp hết hạn sử dụng trong
        $expiringMaterialsCount = Material::where('is_active', true)
            ->whereHas('imports', function ($subQuery) {
                $subQuery->whereNotNull('expiration_date')
                    ->where('remaining_quantity', '>', 0)
                    ->whereBetween('expiration_date', [today(), today()->addDays(30)]);
            })->count();

        // Lấy danh sách 5 đơn hàng mới tạo gần đây nhất
        $recentOrders = Order::latest()->limit(5)->get()->map(function ($order) {
            $labels = [ // Đắp class màu và nhãn tiếng Việt tương ứng trạng thái
                'pending' => ['Chờ xác nhận', 'bg-warning-container text-warning-onContainer border border-warning'],
                'confirmed' => ['Đã xác nhận', 'bg-primary-container text-primary-onContainer border border-primary'],
                'shipping' => ['Đang giao', 'bg-info-container text-info-onContainer border border-info'],
                'completed' => ['Hoàn thành', 'bg-emerald-50 text-emerald-700 border border-emerald-100'],
                'cancelled' => ['Đã hủy', 'bg-error-container text-error-onContainer border border-error'],
            ];
            [$label, $classes] = $labels[$order->status] ?? [$order->status, 'bg-gray-100 text-gray-700'];
            return [
                'id' => $order->id,
                'code' => $order->order_code ?: '#HPY-' . $order->id,
                'customer_name' => $order->customer_name,
                'total' => number_format($order->final_amount, 0, ',', '.') . 'đ',
                'status_label' => $label,
                'status_class' => $classes,
                'time' => $order->created_at->format('d/m/Y H:i')
            ];
        });

        return view('backend.staff.reception.dashboard', compact( // Đẩy toàn bộ dữ liệu thống kê ra View Dashboard của quầy lễ tân
            'pendingOrdersCount',
            'shippingOrdersCount',
            'cancelledOrdersCount',
            'needsAssignmentCount',
            'outOfStockMaterialsCount',
            'lowStockMaterialsCount',
            'expiringMaterialsCount',
            'recentOrders',
            'cashRevenueToday',
            'transferRevenueToday',
            'totalRevenueToday',
            'cashRevenuePercent',
            'transferRevenuePercent'
        ));
    }
}
