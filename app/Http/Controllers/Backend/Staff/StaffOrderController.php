<?php

namespace App\Http\Controllers\Backend\Staff;

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\OrderWorkflowService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class StaffOrderController
{
    public function __construct(private readonly OrderWorkflowService $orderWorkflow)
    {}

    public function index(Request $request)
    {
        $status = $request->query('status');
        $query = Order::query()->latest();
        if (in_array($status, ['pending', 'confirmed', 'shipping', 'completed', 'cancelled'], true)) {
            $query->where('status', $status);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }
        if ($request->input('sort') === 'asc') {
            $query->reorder('created_at');
        }

        $collection = $query->get();
        if ($request->filled('search')) {
            $needle = Str::ascii(mb_strtolower(trim($request->input('search'))));
            $collection = $collection->filter(function ($order) use ($needle) {
                $haystack = Str::ascii(mb_strtolower(implode(' ', [$order->order_code, $order->customer_name, $order->customer_phone])));
                return str_contains($haystack, str_replace('#', '', $needle));
            });
        }

        $page = LengthAwarePaginator::resolveCurrentPage();
        $paginator = new LengthAwarePaginator(
            $collection->slice(($page - 1) * 10, 10)->values(),
            $collection->count(),
            10,
            $page,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'query' => $request->query(),
            ]
        );

        $labels = [
            'pending' => ['Chờ xác nhận', 'warning'],
            'confirmed' => ['Đã xác nhận', 'primary'],
            'shipping' => ['Đang giao', 'info'],
            'completed' => ['Hoàn thành', 'success'],
            'cancelled' => ['Đã hủy', 'danger'],
        ];

        $orders = collect($paginator->items())->map(function ($order) use ($labels) {
            [$label, $color] = $labels[$order->status] ?? [$order->status, 'warning'];
            $created = Carbon::parse($order->created_at);
            return [
                'id' => $order->id,
                'code' => $order->order_code ?: '#HPY-' . $order->id,
                'customer_name' => $order->customer_name,
                'customer_phone' => $order->customer_phone,
                'total' => number_format($order->final_amount, 0, ',', '.') . ' VNĐ',
                'payment_method' => strtoupper($order->payment_method ?: 'COD'),
                'payment_status' => $order->payment_status ?: 'unpaid',
                'status' => $label,
                'raw_status' => $order->status,
                'status_color' => $color,
                'time' => $created->format('H:i') . "\n" . $created->format('d/m/Y'),
            ];
        })->all();

        $stats = [
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'cancelled_orders' => Order::where('status', 'cancelled')->count(),
        ];

        if ($request->ajax() || $request->has('ajax')) {
            return response()->json([
                'table_html' => view('staff.orders.partials.table', compact('orders', 'paginator', 'status'))->with('currentStatus', $status)->render(),
                'stats_html' => view('staff.orders.partials.stats', compact('stats'))->render(),
            ]);
        }

        return view('staff.orders.index', compact('stats', 'orders', 'paginator'))->with('currentStatus', $status);
    }

    public function show(Order $order)
    {
        $items = OrderItem::query()->leftJoin('products', 'order_items.product_id', '=', 'products.id')
            ->where('order_items.order_id', $order->id)->select('order_items.*')
            ->selectRaw('COALESCE(order_items.product_name, products.name) as product_name')
            ->selectRaw('COALESCE(order_items.product_image, products.image) as product_image')->get();

        return view('staff.orders.show', compact('order', 'items'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,confirmed,shipping,completed,cancelled'],
            'cancel_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $this->orderWorkflow->transition($order, $validated['status'], $validated['cancel_reason'] ?? null);

        return back()->with('success', 'Đã cập nhật trạng thái đơn hàng!');
    }
}
