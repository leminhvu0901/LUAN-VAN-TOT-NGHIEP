<?php

namespace App\Http\Controllers\Backend\Staff\Delivery;

use App\Http\Controllers\Frontend\VnpayController;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\OrderWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OrderController
{
    public function __construct(private readonly OrderWorkflowService $sv_orderWorkflow)
    {
    }
    // Hiển thị danh sách đơn hàng do Shipper này phụ trách.
    public function index(Request $request)
    {
        // Nhận tab lọc từ URL, mặc định là tab đơn được gán
        $tab = in_array($request->query('tab'), ['assigned', 'shipping', 'history'], true)
            ? $request->query('tab')
            : 'assigned';

        $query = Order::where('delivery_staff_id', Auth::id()); // Lọc đơn hàng gán cho shipper hiện tại

        if ($tab === 'shipping') {
            $query->where('status', 'shipping'); // Đơn hàng đang đi giao
        } elseif ($tab === 'history') {
            $query->whereIn('status', ['completed', 'cancelled']); // Lịch sử giao xong hoặc đã hủy
        } else {
            $query->where('status', 'confirmed'); // Đơn đã xác nhận đang chờ shipper đến quầy nhận hàng
        }

        // Phân trang 10 đơn mỗi trang
        $orders = $query->with('items.product')->latest('assigned_at')->paginate(10)->withQueryString();

        // Tính tổng tiền mặt COD shipper cần thu từ các đơn hàng
        $codToCollect = (float) Order::where('delivery_staff_id', Auth::id())
            ->where('status', 'shipping')->where('payment_method', 'cod')->sum('final_amount');

        return view('backend.staff.delivery.orders.index', compact('orders', 'tab', 'codToCollect'));
    }

    // Hiển thị chi tiết thông tin của đơn hàng để Shipper đi giao
    public function show(Order $order)
    {
        $this->authorizeOwnership($order); // Đảm bảo an ninh kiểm soát: Đơn này phải thuộc về Shipper hiện tại

        // Left Join lấy thông tin sản phẩm đề phòng sản phẩm gốc
        $items = OrderItem::query()->leftJoin('products', 'order_items.product_id', '=', 'products.id')
            ->where('order_items.order_id', $order->id)->select('order_items.*')
            ->selectRaw('COALESCE(order_items.product_name, products.name) as product_name')
            ->selectRaw('COALESCE(order_items.product_image, products.image) as product_image')->get();

        return view('backend.staff.delivery.orders.show', compact('order', 'items'));
    }

    // Xác nhận "Nhận đơn giao", Chuyển từ Chờ giao hàng
    public function ship(Order $order)
    {
        $this->authorizeOwnership($order);
        $this->sv_orderWorkflow->transition($order, 'shipping'); // Chuyển trạng thái đơn hàng sang 'shipping'
        return $this->success('shipping', 'Đã nhận đơn và chuyển sang đang giao!');
    }

    // Xác nhận "Giao hàng thành công", Chuyển từ Đang giao
    public function complete(Order $order)
    {
        $this->authorizeOwnership($order);/// Xác thực quyền sở hữu
        $this->sv_orderWorkflow->transition($order, 'completed'); // Chuyển trạng thái 
        return $this->success('history', 'Đã xác nhận giao hàng thành công!');
    }

    // Ghi nhận "Giao hàng thất bại".
    public function fail(Request $request, Order $order)
    {
        $this->authorizeOwnership($order);/// Xác thực quyền sở hữu: Shipper chỉ được xử lý đơn hàng được gán cho chính mình, hoặc Admin

        $validated = $request->validate([ // Validate lý do giao hàng thất bại
            'reason' => ['required', 'string', 'max:500'],
            'failure_type' => ['required', 'in:damaged,customer_unreachable,other'],
        ], [
            'reason.required' => 'Vui lòng nhập lý do giao thất bại.',
            'failure_type.required' => 'Vui lòng chọn loại lý do giao thất bại.',
        ]);

        // Nếu hàng hư hỏng trên đường đi và khách đã thanh toán
        $needsRefund = $validated['failure_type'] === 'damaged'
            && $order->payment_method === 'vnpay'
            && $order->payment_status === 'paid';

        if ($needsRefund) {
            $gatewayLabel = 'VNPay';

            if (!$order->payment_transaction_id) { // Kiểm tra mã giao dịch gốc để hoàn tiền
                Log::error("{$gatewayLabel} refund skipped: missing payment_transaction_id", ['orderId' => $order->order_code]);
                $this->sv_orderWorkflow->markDeliveryFailed($order, $validated['reason'], $validated['failure_type']); // Ghi nhận thất bại thông thường
                return $this->success('history', 'Đã ghi nhận giao hàng thất bại. Không tìm thấy mã giao dịch gốc để hoàn tiền — vui lòng báo lễ tân xử lý hoàn tiền thủ công.');
            }

            // Gọi API hoàn tiền từ VnpayController
            $refundResult = app(VnpayController::class)->requestRefund($order);

            if ($refundResult['success']) {
                // Giao thất bại và ghi nhận thông tin hoàn tiền VNPay
                $this->sv_orderWorkflow->markDeliveryFailedWithRefund($order, $validated['reason'], $validated['failure_type'], $refundResult['transId']); // Ghi nhận thất bại kèm cập nhật hoàn tiền
                return $this->success('history', 'Đã hoàn tiền và ghi nhận giao hàng thất bại.');
            }

            // Hoàn tiền thất bại: Vẫn hủy đơn để giải phóng chuyến
            Log::error("{$gatewayLabel} refund failed on delivery-failed (damaged)", ['orderId' => $order->order_code, 'message' => $refundResult['message']]);
            // Ghi nhận đơn hàng giao thất bại và tiến hành hủy đơn
            $this->sv_orderWorkflow->markDeliveryFailed($order, $validated['reason'], $validated['failure_type']);
            return $this->success('history', "Đã ghi nhận giao hàng thất bại. Hoàn tiền {$gatewayLabel} thất bại — vui lòng báo lễ tân xử lý hoàn tiền thủ công.");
        }
        // Ghi nhận đơn hàng giao thất bại và tiến hành hủy đơn
        $this->sv_orderWorkflow->markDeliveryFailed($order, $validated['reason'], $validated['failure_type']); // Ghi nhận thất bại thông thường

        return $this->success('history', 'Đã ghi nhận giao hàng thất bại.');
    }

    // Hàm helper trả về redirect kèm thông báo về đúng tab
    private function success(string $tab, string $message)
    {
        return redirect()->route('staff.delivery.orders.index', ['tab' => $tab])->with('success', $message);
    }

    // Xác thực quyền sở hữu: Shipper chỉ được xử lý đơn hàng
    private function authorizeOwnership(Order $order): void
    {
        $user = Auth::user();
        abort_unless($user->role === 'admin' || $order->delivery_staff_id === $user->id, 403);
    }
}
