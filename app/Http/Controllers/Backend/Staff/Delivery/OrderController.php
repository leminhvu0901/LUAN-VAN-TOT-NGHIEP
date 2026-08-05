<?php

namespace App\Http\Controllers\Backend\Staff\Delivery;

use App\Http\Controllers\Frontend\MomoController;
use App\Http\Controllers\Frontend\VnpayController;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\OrderWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Controller Quản lý Đơn hàng dành cho Nhân viên giao hàng (Shipper).
 * Shipper chỉ có quyền xem và xử lý trạng thái giao hàng của các đơn được phân công cho chính mình.
 * Không tích hợp các chức năng quản trị như xóa đơn hàng, xóa hàng loạt hay xuất file báo cáo.
 */
class OrderController
{
    // Inject workflow service quản lý chuyển đổi trạng thái đơn hàng
    public function __construct(private readonly OrderWorkflowService $sv_orderWorkflow) {}

    /**
     * Hiển thị danh sách đơn hàng do Shipper này phụ trách.
     * Hỗ trợ các Tab bộ lọc: Đơn được gán (assigned), Đơn đang đi giao (shipping), Lịch sử giao hàng (history).
     */
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

        // Tính tổng tiền mặt COD shipper cần thu từ các đơn hàng đang giao trên đường đi
        $codToCollect = (float) Order::where('delivery_staff_id', Auth::id())
            ->where('status', 'shipping')->where('payment_method', 'cod')->sum('final_amount');

        return view('backend.staff.delivery.orders.index', compact('orders', 'tab', 'codToCollect'));
    }

    /**
     * Hiển thị chi tiết thông tin của đơn hàng để Shipper đi giao.
     */
    public function show(Order $order)
    {
        $this->authorizeOwnership($order); // Đảm bảo an ninh kiểm soát: Đơn này phải thuộc về Shipper hiện tại

        // Left Join lấy thông tin sản phẩm đề phòng sản phẩm gốc bị xóa khỏi hệ thống
        $items = OrderItem::query()->leftJoin('products', 'order_items.product_id', '=', 'products.id')
            ->where('order_items.order_id', $order->id)->select('order_items.*')
            ->selectRaw('COALESCE(order_items.product_name, products.name) as product_name')
            ->selectRaw('COALESCE(order_items.product_image, products.image) as product_image')->get();

        return view('backend.staff.delivery.orders.show', compact('order', 'items'));
    }

    /**
     * Xác nhận "Nhận đơn giao" — Chuyển từ Chờ giao hàng sang Đang giao hàng (shipping).
     */
    public function ship(Order $order)
    {
        $this->authorizeOwnership($order);
        $this->sv_orderWorkflow->transition($order, 'shipping'); // Chuyển trạng thái đơn hàng sang 'shipping'
        return $this->success('shipping', 'Đã nhận đơn và chuyển sang đang giao!');
    }

    /**
     * Xác nhận "Giao hàng thành công" — Chuyển từ Đang giao sang Hoàn thành (completed).
     */
    public function complete(Order $order)
    {
        $this->authorizeOwnership($order);
        $this->sv_orderWorkflow->transition($order, 'completed'); // Chuyển trạng thái đơn hàng sang 'completed' (hoàn thành)
        return $this->success('history', 'Đã xác nhận giao hàng thành công!');
    }

    /**
     * Ghi nhận "Giao hàng thất bại".
     * Hỗ trợ tự động hoàn tiền online qua cổng MoMo/VNPay nếu lý do thất bại là "Hàng bị đổ vỡ/hư hỏng".
     */
    public function fail(Request $request, Order $order)
    {
        $this->authorizeOwnership($order);

        $validated = $request->validate([ // Validate lý do giao hàng thất bại
            'reason' => ['required', 'string', 'max:500'],
            'failure_type' => ['required', 'in:damaged,customer_unreachable,other'],
        ], [
            'reason.required' => 'Vui lòng nhập lý do giao thất bại.',
            'failure_type.required' => 'Vui lòng chọn loại lý do giao thất bại.',
        ]);

        // Nếu hàng hư hỏng trên đường đi và khách đã thanh toán online -> Kích hoạt hoàn tiền online ngay
        $needsRefund = $validated['failure_type'] === 'damaged'
            && in_array($order->payment_method, ['momo', 'vnpay'], true)
            && $order->payment_status === 'paid';

        if ($needsRefund) {
            $gatewayLabel = $order->payment_method === 'vnpay' ? 'VNPay' : 'MoMo';

            if (!$order->payment_transaction_id) { // Kiểm tra mã giao dịch gốc để hoàn tiền
                Log::error("{$gatewayLabel} refund skipped: missing payment_transaction_id", ['orderId' => $order->order_code]);
                $this->sv_orderWorkflow->markDeliveryFailed($order, $validated['reason'], $validated['failure_type']); // Ghi nhận thất bại thông thường
                return $this->success('history', 'Đã ghi nhận giao hàng thất bại. Không tìm thấy mã giao dịch gốc để hoàn tiền — vui lòng báo lễ tân xử lý hoàn tiền thủ công.');
            }

            // Gọi API hoàn tiền từ MomoController hoặc VnpayController tương ứng
            $refundResult = $order->payment_method === 'vnpay'
                ? app(VnpayController::class)->requestRefund($order)
                : app(MomoController::class)->requestRefund($order);

            if ($refundResult['success']) {
                $this->sv_orderWorkflow->markDeliveryFailedWithRefund($order, $validated['reason'], $validated['failure_type'], $refundResult['transId']); // Ghi nhận thất bại kèm cập nhật hoàn tiền
                return $this->success('history', 'Đã hoàn tiền và ghi nhận giao hàng thất bại.');
            }

            // Hoàn tiền thất bại: Vẫn hủy đơn để giải phóng chuyến giao hàng của shipper nhưng báo lễ tân hoàn thủ công
            Log::error("{$gatewayLabel} refund failed on delivery-failed (damaged)", ['orderId' => $order->order_code, 'message' => $refundResult['message']]);
            $this->sv_orderWorkflow->markDeliveryFailed($order, $validated['reason'], $validated['failure_type']);
            return $this->success('history', "Đã ghi nhận giao hàng thất bại. Hoàn tiền {$gatewayLabel} thất bại — vui lòng báo lễ tân xử lý hoàn tiền thủ công.");
        }

        $this->sv_orderWorkflow->markDeliveryFailed($order, $validated['reason'], $validated['failure_type']); // Ghi nhận thất bại thông thường

        return $this->success('history', 'Đã ghi nhận giao hàng thất bại.');
    }

    /**
     * Hàm helper trả về redirect kèm thông báo về đúng tab danh sách sau khi xử lý đơn xong.
     */
    private function success(string $tab, string $message)
    {
        return redirect()->route('staff.delivery.orders.index', ['tab' => $tab])->with('success', $message);
    }

    /**
     * Xác thực quyền sở hữu: Shipper chỉ được xử lý đơn hàng được gán cho chính mình (hoặc Admin).
     */
    private function authorizeOwnership(Order $order): void
    {
        $user = Auth::user();
        abort_unless($user->role === 'admin' || $order->delivery_staff_id === $user->id, 403);
    }
}
