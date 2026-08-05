<?php

namespace App\Http\Controllers\Backend\Staff\Reception;

use App\Models\Order;
use App\Models\User;
use App\Services\OrderWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controller Đối soát tiền mặt thu hộ (COD - Cash On Delivery).
 * Nhân viên giao hàng (Shipper) khi đi giao đơn online sẽ thu tiền mặt từ khách hàng, sau đó phải nộp lại cho quầy.
 * Trang này giúp Lễ tân quản lý số tiền shipper đang cầm, xác nhận shipper đã nộp tiền mặt cho quán.
 */
class CodController
{
    // Inject dịch vụ quản lý trạng thái đơn hàng và đối soát tiền
    public function __construct(private readonly OrderWorkflowService $sv_orderWorkflow) {}

    /**
     * Hiển thị giao diện đối soát tiền COD cho từng nhân viên giao hàng.
     */
    public function index()
    {
        // Lấy danh sách toàn bộ nhân viên giao hàng (Shipper) đang hoạt động
        $deliveryStaffList = User::where('role', 'staff')->where('staff_type', 'delivery')->orderBy('name')->get();

        // Gom nhóm các đơn hàng hoàn thành cần nộp tiền theo từng Shipper
        $groups = $deliveryStaffList->map(function (User $staff) {
            $unsettledOrders = Order::where('delivery_staff_id', $staff->id) // Tìm đơn do Shipper này phụ trách
                ->where('payment_method', 'cod') // Phương thức thanh toán khi nhận hàng
                ->where('status', 'completed') // Đơn giao đã hoàn thành công
                ->whereNull('cod_settled_at') // Tiền mặt thu hộ chưa được đối soát (chưa nộp quầy)
                ->orderBy('created_at')
                ->get();

            // Tính tổng số tiền COD mà Shipper này đã nộp quầy thành công trong ngày hôm nay
            $settledTotalToday = (float) Order::where('delivery_staff_id', $staff->id)
                ->where('payment_method', 'cod')
                ->where('status', 'completed')
                ->whereDate('cod_settled_at', today()) // Lọc theo ngày hôm nay
                ->sum('final_amount');

            return [
                'staff' => $staff,
                'unsettled_orders' => $unsettledOrders,
                'unsettled_total' => (float) $unsettledOrders->sum('final_amount'), // Tổng tiền Shipper còn đang nợ chưa nộp quầy
                'settled_total_today' => $settledTotalToday,
            ];
        });

        return view('backend.staff.reception.cod-settlement.index', compact('groups')); // Trả về giao diện đối soát tiền mặt
    }

    /**
     * Xác nhận đối soát cho một đơn hàng COD riêng lẻ.
     */
    public function settleOne(Request $request, Order $order)
    {
        $this->sv_orderWorkflow->settleCod($order, Auth::id()); // Gọi workflow service đánh dấu đơn hàng đã nộp tiền (lưu ID người nhận là lễ tân hiện tại)
        $message = "Đã xác nhận nhận tiền COD cho đơn {$order->order_code}.";

        return back()->with('success', $message);
    }

    /**
     * Xác nhận đối soát toàn bộ (tất cả) đơn hàng COD còn treo của một Shipper cụ thể.
     */
    public function settleAll(Request $request, User $deliveryStaff)
    {
        // Ngăn chặn nếu đối tượng truyền vào không phải là tài khoản Shipper
        abort_unless($deliveryStaff->role === 'staff' && $deliveryStaff->staff_type === 'delivery', 404);

        // Gọi workflow service đối soát toàn bộ đơn COD của shipper này, trả về số lượng đơn được đối soát
        $count = $this->sv_orderWorkflow->settleAllCodForDeliveryStaff($deliveryStaff->id, Auth::id());

        if ($count === 0) {
            return back()->with('info', "{$deliveryStaff->name} không có đơn COD nào cần đối soát.");
        }

        return back()->with('success', "Đã xác nhận nộp quầy {$count} đơn COD từ {$deliveryStaff->name}.");
    }
}
