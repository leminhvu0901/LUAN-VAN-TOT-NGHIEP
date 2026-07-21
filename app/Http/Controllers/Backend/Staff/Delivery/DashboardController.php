<?php

namespace App\Http\Controllers\Backend\Staff\Delivery;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class DashboardController
{
    public function index()
    {
        $staffId = Auth::id();

        $pendingPickupCount = Order::where('delivery_staff_id', $staffId)->where('status', 'confirmed')->count();
        $shippingCount = Order::where('delivery_staff_id', $staffId)->where('status', 'shipping')->count();
        $completedCount = Order::where('delivery_staff_id', $staffId)->where('status', 'completed')->count();
        $failedCount = Order::where('delivery_staff_id', $staffId)->whereNotNull('delivery_failed_at')->count();

        $codToCollect = (float) Order::where('delivery_staff_id', $staffId)
            ->where('status', 'shipping')->where('payment_method', 'cod')->sum('final_amount');

        $codCollectedTotal = (float) Order::where('delivery_staff_id', $staffId)
            ->where('status', 'completed')->where('payment_method', 'cod')->sum('final_amount');

        // Trong số đã thu, phần nào lễ tân đã xác nhận nhận lại (đã nộp quầy) và phần nào shipper
        // còn giữ trong người (chưa nộp) — để nhân viên tự biết cần nộp lại bao nhiêu.
        $codUnsettledTotal = (float) Order::where('delivery_staff_id', $staffId)
            ->where('status', 'completed')->where('payment_method', 'cod')
            ->whereNull('cod_settled_at')->sum('final_amount');
        $codSettledTotal = $codCollectedTotal - $codUnsettledTotal;

        $recentOrders = Order::where('delivery_staff_id', $staffId)
            ->whereIn('status', ['confirmed', 'shipping'])
            ->latest('assigned_at')->limit(5)->get();

        return view('backend.staff.delivery.dashboard', compact(
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
