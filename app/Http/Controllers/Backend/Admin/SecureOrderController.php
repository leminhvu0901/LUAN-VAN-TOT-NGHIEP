<?php

namespace App\Http\Controllers\Backend\Admin;

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\OrderWorkflowService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class SecureOrderController
{
    public function __construct(private readonly OrderWorkflowService $orderWorkflow)
    {}

    public function index(Request $request)
    {
        $status = $request->query('status');
        $query = Order::query()->latest();
        if (in_array($status, ['pending', 'confirmed', 'shipping', 'completed', 'cancelled'], true)) $query->where('status', $status);
        if ($request->filled('date_from')) $query->whereDate('created_at', '>=', $request->input('date_from'));
        if ($request->filled('date_to')) $query->whereDate('created_at', '<=', $request->input('date_to'));
        if ($request->input('sort') === 'asc') $query->reorder('created_at');

        $collection = $query->get();
        if ($request->filled('search')) {
            $needle = Str::ascii(mb_strtolower(trim($request->input('search'))));
            $collection = $collection->filter(function ($order) use ($needle) {
                $haystack = Str::ascii(mb_strtolower(implode(' ', [$order->order_code, $order->customer_name, $order->customer_phone])));
                return str_contains($haystack, str_replace('#', '', $needle));
            });
        }
        $page = LengthAwarePaginator::resolveCurrentPage();
        $paginator = new LengthAwarePaginator($collection->slice(($page - 1) * 10, 10)->values(), $collection->count(), 10, $page, [
            'path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => $request->query(),
        ]);
        $labels = [
            'pending' => ['Chờ xác nhận', 'warning'], 'confirmed' => ['Đã xác nhận', 'primary'],
            'shipping' => ['Đang giao', 'info'], 'completed' => ['Hoàn thành', 'success'], 'cancelled' => ['Đã hủy', 'danger'],
        ];
        $orders = collect($paginator->items())->map(function ($order) use ($labels) {
            [$label, $color] = $labels[$order->status] ?? [$order->status, 'warning'];
            $created = Carbon::parse($order->created_at);
            return [
                'id' => $order->id, 'code' => $order->order_code ?: '#HPY-' . $order->id,
                'customer_name' => $order->customer_name, 'customer_phone' => $order->customer_phone,
                'total' => number_format($order->final_amount, 0, ',', '.') . ' VNĐ',
                'payment_method' => strtoupper($order->payment_method ?: 'COD'), 'payment_status' => $order->payment_status ?: 'unpaid',
                'status' => $label, 'raw_status' => $order->status, 'status_color' => $color,
                'time' => $created->format('H:i') . "\n" . $created->format('d/m/Y'),
            ];
        })->all();
        $stats = [
            'total_orders' => Order::count(), 'total_revenue' => Order::where('status', 'completed')->where('payment_status', 'paid')->sum('final_amount'),
            'pending_orders' => Order::where('status', 'pending')->count(), 'cancelled_orders' => Order::where('status', 'cancelled')->count(),
        ];
        if ($request->ajax() || $request->has('ajax')) {
            return response()->json([
                'table_html' =>  view('backend.admin.orders.partials.table', compact('orders', 'paginator', 'status'))->with('currentStatus', $status)->render(),
                'stats_html' =>  view('backend.admin.orders.partials.stats', compact('stats'))->render(),
            ]);
        }
        return  view('backend.admin.orders.index', compact('stats', 'orders', 'paginator'))->with('currentStatus', $status);
    }

    public function show($id)
    {
        $order = Order::find($id);
        if (!$order) return redirect()->route('admin.orders.index')->with('error', 'Không tìm thấy đơn hàng!');
        $items = OrderItem::query()->leftJoin('products', 'order_items.product_id', '=', 'products.id')
            ->where('order_items.order_id', $id)->select('order_items.*')
            ->selectRaw('COALESCE(order_items.product_name, products.name) as product_name')
            ->selectRaw('COALESCE(order_items.product_image, products.image) as product_image')->get();
        return  view('backend.admin.orders.show', compact('order', 'items'));
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,confirmed,shipping,completed,cancelled'],
            'cancel_reason' => ['nullable', 'string', 'max:500'],
        ]);
        $this->orderWorkflow->transition(Order::findOrFail($id), $validated['status'], $validated['cancel_reason'] ?? null);
        return back()->with('success', 'Đã cập nhật trạng thái đơn hàng!');
    }

    public function destroy(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $order->delete();
        
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã xóa đơn hàng thành công!'
            ]);
        }
        
        return back()->with('success', 'Đã xóa đơn hàng thành công!');
    }

    public function bulkDelete(Request $request)
    {
        $query = Order::query();
        if ($request->input('delete_all_pages') === '1') {
            if (in_array($request->input('status'), ['pending', 'confirmed', 'shipping', 'completed', 'cancelled'], true)) {
                $query->where('status', $request->input('status'));
            }
            if ($request->filled('date_from')) $query->whereDate('created_at', '>=', $request->input('date_from'));
            if ($request->filled('date_to')) $query->whereDate('created_at', '<=', $request->input('date_to'));
            if ($request->filled('search')) {
                $search = trim($request->input('search'));
                $query->where(fn ($q) => $q->where('order_code', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")->orWhere('customer_phone', 'like', "%{$search}%"));
            }
            $excluded = $request->validate(['excluded_order_ids' => ['sometimes', 'array'], 'excluded_order_ids.*' => ['integer']])['excluded_order_ids'] ?? [];
            if ($excluded) $query->whereNotIn('id', $excluded);
        } else {
            $ids = $request->validate(['order_ids' => ['required', 'array'], 'order_ids.*' => ['integer', 'exists:orders,id']])['order_ids'];
            $query->whereIn('id', $ids);
        }

        $count = $query->count();
        $query->delete();
        if ($count === 0) return back()->withErrors(['delete' => 'Không có đơn hàng nào được chọn.']);
        return back()->with('success', "Đã xóa {$count} đơn hàng thành công.");
    }

    public function export(Request $request)
    {
        $query = Order::query()->latest();
        if (in_array($request->input('status'), ['pending', 'confirmed', 'shipping', 'completed', 'cancelled'], true)) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('date_from')) $query->whereDate('created_at', '>=', $request->input('date_from'));
        if ($request->filled('date_to')) $query->whereDate('created_at', '<=', $request->input('date_to'));

        return response()->streamDownload(function () use ($query) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Mã đơn', 'Khách hàng', 'Điện thoại', 'Tổng tiền', 'Thanh toán', 'Trạng thái', 'Ngày tạo']);
            $query->chunk(500, function ($orders) use ($output) {
                foreach ($orders as $order) {
                    fputcsv($output, [$order->order_code, $order->customer_name, $order->customer_phone,
                        $order->final_amount, $order->payment_status, $order->status, $order->created_at]);
                }
            });
            fclose($output);
        }, 'orders-' . now()->format('Ymd-His') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
