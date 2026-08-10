<?php

namespace App\Http\Controllers\Backend\Admin;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DeliveryStatisticsController
{
    //Thống kê số đơn hàng giao thành công của từng nhân viên giao hàng theo khoảng thời gian
    public function index(Request $request)
    {
        $preset = $request->input('preset', 'today');
        $now = Carbon::now();

        switch ($preset) {
            case 'this_week':
                $start = $now->copy()->startOfWeek();
                $end = $now->copy()->endOfWeek();
                break;
            case 'this_month':
                $start = $now->copy()->startOfMonth();
                $end = $now->copy()->endOfMonth();
                break;
            case 'this_year':
                $start = $now->copy()->startOfYear();
                $end = $now->copy()->endOfYear();
                break;
            case 'custom':
                $start = $request->filled('date_from') ? Carbon::parse($request->input('date_from'))->startOfDay() : $now->copy()->startOfDay();
                $end = $request->filled('date_to') ? Carbon::parse($request->input('date_to'))->endOfDay() : $now->copy()->endOfDay();
                break;
            case 'today':
            default:
                $preset = 'today';
                $start = $now->copy()->startOfDay();
                $end = $now->copy()->endOfDay();
                break;
        }

        // Lấy toàn bộ nhân viên giao hàng kèm số đơn đã giao thành công/thất bại trong khoảng thời gian đang lọc
        $staffs = User::query()
            ->where('role', 'staff')
            ->where('staff_type', 'delivery')
            ->withCount([
                'completedDeliveries as completed_orders_count' => function ($query) use ($start, $end) {
                    $query->whereBetween('completed_at', [$start, $end]);
                }
            ])
            ->withCount([
                'failedDeliveries as failed_orders_count' => function ($query) use ($start, $end) {
                    $query->whereBetween('delivery_failed_at', [$start, $end]);
                }
            ])
            ->orderByDesc('completed_orders_count')
            ->orderBy('name')
            ->get();

        // Tổng đơn = đơn giao thành công + đơn giao thất bại trong kỳ
        $staffs->each(function ($staff) {
            $staff->total_orders_count = $staff->completed_orders_count + $staff->failed_orders_count;
        });

        $totalDeliveryStaff = $staffs->count();
        $activeDeliveryStaff = $staffs->where('is_active', 1)->count();
        $totalCompletedOrders = $staffs->sum('completed_orders_count');

        return view('backend.admin.delivery-statistics.index', compact(
            'staffs',
            'preset',
            'start',
            'end',
            'totalDeliveryStaff',
            'activeDeliveryStaff',
            'totalCompletedOrders'
        ));
    }
}
