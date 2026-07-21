<?php

namespace App\Http\Controllers\Backend\Staff\Reception;

use App\Models\Order;
use App\Models\User;
use App\Services\OrderWorkflowService;
use Illuminate\Support\Facades\Auth;

// Đối soát tiền COD: nhân viên vận chuyển thu tiền mặt từ khách khi hoàn thành đơn, sau đó phải
// nộp lại cho lễ tân/quầy. Trang này cho lễ tân biết chính xác đơn nào đã nộp, đơn nào shipper còn giữ.
class CodController
{
    public function __construct(private readonly OrderWorkflowService $orderWorkflow) {}

    public function index()
    {
        $deliveryStaffList = User::where('role', 'staff')->where('staff_type', 'delivery')->orderBy('name')->get();

        $groups = $deliveryStaffList->map(function (User $staff) {
            $unsettledOrders = Order::where('delivery_staff_id', $staff->id)
                ->where('payment_method', 'cod')
                ->where('status', 'completed')
                ->whereNull('cod_settled_at')
                ->orderBy('created_at')
                ->get();

            $settledTotalToday = (float) Order::where('delivery_staff_id', $staff->id)
                ->where('payment_method', 'cod')
                ->where('status', 'completed')
                ->whereDate('cod_settled_at', today())
                ->sum('final_amount');

            return [
                'staff' => $staff,
                'unsettled_orders' => $unsettledOrders,
                'unsettled_total' => (float) $unsettledOrders->sum('final_amount'),
                'settled_total_today' => $settledTotalToday,
            ];
        });

        return view('backend.staff.reception.cod-settlement.index', compact('groups'));
    }

    public function settleOne(Order $order)
    {
        $this->orderWorkflow->settleCod($order, Auth::id());

        return back()->with('success', "Đã xác nhận nhận tiền COD cho đơn {$order->order_code}.");
    }

    public function settleAll(User $deliveryStaff)
    {
        abort_unless($deliveryStaff->role === 'staff' && $deliveryStaff->staff_type === 'delivery', 404);

        $count = $this->orderWorkflow->settleAllCodForDeliveryStaff($deliveryStaff->id, Auth::id());

        if ($count === 0) {
            return back()->with('info', "{$deliveryStaff->name} không có đơn COD nào cần đối soát.");
        }

        return back()->with('success', "Đã xác nhận nộp quầy {$count} đơn COD từ {$deliveryStaff->name}.");
    }
}
