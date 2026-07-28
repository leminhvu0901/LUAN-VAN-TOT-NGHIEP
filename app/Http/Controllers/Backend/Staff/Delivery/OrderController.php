<?php

namespace App\Http\Controllers\Backend\Staff\Delivery;

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\OrderWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Nhân viên vận chuyển CHỈ được xem/xử lý đơn được phân công cho chính mình.
// Không có method destroy/bulkDelete/export hay bất kỳ thao tác admin-only nào.
class OrderController
{
    public function __construct(private readonly OrderWorkflowService $orderWorkflow) {}

    public function index(Request $request)
    {
        $tab = in_array($request->query('tab'), ['assigned', 'shipping', 'history'], true)
            ? $request->query('tab')
            : 'assigned';

        $query = Order::where('delivery_staff_id', Auth::id());

        if ($tab === 'shipping') {
            $query->where('status', 'shipping');
        } elseif ($tab === 'history') {
            $query->whereIn('status', ['completed', 'cancelled']);
        } else {
            $query->where('status', 'confirmed');
        }

        $orders = $query->latest('assigned_at')->paginate(10)->withQueryString();

        $codToCollect = (float) Order::where('delivery_staff_id', Auth::id())
            ->where('status', 'shipping')->where('payment_method', 'cod')->sum('final_amount');

        return view('backend.staff.delivery.orders.index', compact('orders', 'tab', 'codToCollect'));
    }

    public function show(Order $order)
    {
        $this->authorizeOwnership($order);

        $items = OrderItem::query()->leftJoin('products', 'order_items.product_id', '=', 'products.id')
            ->where('order_items.order_id', $order->id)->select('order_items.*')
            ->selectRaw('COALESCE(order_items.product_name, products.name) as product_name')
            ->selectRaw('COALESCE(order_items.product_image, products.image) as product_image')->get();

        return view('backend.staff.delivery.orders.show', compact('order', 'items'));
    }

    // "Nhận đơn giao" — chuyển từ confirmed (đã phân công, chờ lấy hàng) sang shipping (đang giao).
    public function ship(Request $request, Order $order)
    {
        $this->authorizeOwnership($order);
        $this->orderWorkflow->transition($order, 'shipping');
        return $this->success($request, 'shipping', 'Đã nhận đơn và chuyển sang đang giao!');
    }

    public function complete(Request $request, Order $order)
    {
        $this->authorizeOwnership($order);
        $this->orderWorkflow->transition($order, 'completed');
        return $this->success($request, 'history', 'Đã xác nhận giao hàng thành công!');
    }

    public function fail(Request $request, Order $order)
    {
        $this->authorizeOwnership($order);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ], [
            'reason.required' => 'Vui lòng nhập lý do giao thất bại.',
        ]);

        $this->orderWorkflow->markDeliveryFailed($order, $validated['reason']);

        return $this->success($request, 'history', 'Đã ghi nhận giao hàng thất bại.');
    }

    /**
     * Trả kết quả đúng định dạng theo kiểu request: JSON cho fetch (nút bấm ở danh sách/chi tiết đơn
     * submit qua AJAX, xem order-actions.js) để JS tự điều hướng + không mất trạng thái nút bấm giữa
     * chừng trên mạng di động chập chờn; redirect cổ điển cho request thường.
     */
    private function success(Request $request, string $tab, string $message)
    {
        $redirectUrl = route('staff.delivery.orders.index', ['tab' => $tab]);
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message, 'redirect_url' => $redirectUrl]);
        }
        return redirect($redirectUrl)->with('success', $message);
    }

    private function authorizeOwnership(Order $order): void
    {
        $user = Auth::user();
        abort_unless($user->role === 'admin' || $order->delivery_staff_id === $user->id, 403);
    }
}
