<?php

namespace App\Http\Controllers\Backend\Staff;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Material;
use App\Models\MaterialImport;

class StaffDashboardController
{
    public function index()
    {
        // 1. Thống kê đơn hàng
        $pendingOrdersCount = Order::where('status', 'pending')->count();
        $shippingOrdersCount = Order::where('status', 'shipping')->count();
        $cancelledOrdersCount = Order::where('status', 'cancelled')->count();

        // 2. Cảnh báo kho nguyên liệu
        $outOfStockMaterialsCount = Material::where('is_active', true)->where('current_stock', '<=', 0)->count();
        $lowStockMaterialsCount = Material::where('is_active', true)->where('current_stock', '>', 0)->where('current_stock', '<', 5)->count();
        $expiringMaterialsCount = Material::where('is_active', true)
            ->whereHas('imports', function ($subQuery) {
                $subQuery->whereNotNull('expiration_date')
                    ->where('remaining_quantity', '>', 0)
                    ->whereBetween('expiration_date', [today(), today()->addDays(30)]);
            })->count();

        // 3. Danh sách đơn hàng mới nhất (Top 5)
        $recentOrders = Order::latest()->limit(5)->get()->map(function ($order) {
            $labels = [
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

        return view('staff.dashboard', compact(
            'pendingOrdersCount',
            'shippingOrdersCount',
            'cancelledOrdersCount',
            'outOfStockMaterialsCount',
            'lowStockMaterialsCount',
            'expiringMaterialsCount',
            'recentOrders'
        ));
    }
}
