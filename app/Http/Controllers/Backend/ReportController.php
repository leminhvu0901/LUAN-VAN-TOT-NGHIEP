<?php

namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Material;
use App\Models\MaterialImport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController
{
    public function index(Request $request)
    {
        $preset = $request->input('preset', '30_days');
        $now = Carbon::now();

        // 1. Xác định khoảng thời gian hiện tại và khoảng thời gian trước đó để so sánh
        switch ($preset) {
            case 'today':
                $start = $now->copy()->startOfDay();
                $end = $now->copy()->endOfDay();
                $prevStart = $start->copy()->subDay();
                $prevEnd = $end->copy()->subDay();
                break;
            case '7_days':
                $start = $now->copy()->subDays(6)->startOfDay();
                $end = $now->copy()->endOfDay();
                $prevStart = $start->copy()->subDays(7);
                $prevEnd = $end->copy()->subDays(7);
                break;
            case '30_days':
                $start = $now->copy()->subDays(29)->startOfDay();
                $end = $now->copy()->endOfDay();
                $prevStart = $start->copy()->subDays(30);
                $prevEnd = $end->copy()->subDays(30);
                break;
            case 'this_month':
                $start = $now->copy()->startOfMonth();
                $end = $now->copy()->endOfMonth();
                $prevStart = $start->copy()->subMonth()->startOfMonth();
                $prevEnd = $start->copy()->subMonth()->endOfMonth();
                break;
            case 'last_month':
                $start = $now->copy()->subMonth()->startOfMonth();
                $end = $now->copy()->subMonth()->endOfMonth();
                $prevStart = $start->copy()->subMonths(2)->startOfMonth();
                $prevEnd = $start->copy()->subMonths(2)->endOfMonth();
                break;
            case 'this_year':
                $start = $now->copy()->startOfYear();
                $end = $now->copy()->endOfYear();
                $prevStart = $start->copy()->subYear()->startOfYear();
                $prevEnd = $start->copy()->subYear()->endOfYear();
                break;
            case 'custom':
                $start = $request->filled('date_from') ? Carbon::parse($request->input('date_from'))->startOfDay() : $now->copy()->subDays(29)->startOfDay();
                $end = $request->filled('date_to') ? Carbon::parse($request->input('date_to'))->endOfDay() : $now->copy()->endOfDay();
                $diffInDays = $start->diffInDays($end) + 1;
                $prevStart = $start->copy()->subDays($diffInDays);
                $prevEnd = $end->copy()->subDays($diffInDays);
                break;
            default:
                $start = $now->copy()->subDays(29)->startOfDay();
                $end = $now->copy()->endOfDay();
                $prevStart = $start->copy()->subDays(30);
                $prevEnd = $end->copy()->subDays(30);
                break;
        }

        // 2. Hàm tính toán xu hướng tăng/giảm (%)
        $getTrend = function ($current, $previous) {
            if ($previous == 0) {
                return $current > 0 ? ['text' => '+100%', 'color' => 'text-emerald-600', 'direction' => 'up'] : ['text' => '0%', 'color' => 'text-gray-400', 'direction' => 'none'];
            }
            $percent = round((($current - $previous) / $previous) * 100, 1);
            if ($percent > 0) {
                return ['text' => '+' . $percent . '%', 'color' => 'text-emerald-600', 'direction' => 'up'];
            } elseif ($percent < 0) {
                return ['text' => $percent . '%', 'color' => 'text-red-600', 'direction' => 'down'];
            }
            return ['text' => '0%', 'color' => 'text-gray-400', 'direction' => 'none'];
        };

        // 3. Tính toán các chỉ số thống kê trong kỳ hiện tại
        $revenue = (float) Order::where('status', 'completed')->whereBetween('created_at', [$start, $end])->sum('final_amount');
        $ordersCount = Order::whereBetween('created_at', [$start, $end])->count();
        $completedCount = Order::where('status', 'completed')->whereBetween('created_at', [$start, $end])->count();
        $cancelledCount = Order::where('status', 'cancelled')->whereBetween('created_at', [$start, $end])->count();
        $newCustomersCount = User::where('role', 'customer')->whereBetween('created_at', [$start, $end])->count();
        $productsSoldCount = (int) OrderItem::whereHas('order', function ($q) use ($start, $end) {
            $q->where('status', 'completed')->whereBetween('created_at', [$start, $end]);
        })->sum('quantity');

        // 4. Tính toán các chỉ số thống kê trong kỳ trước để so sánh
        $prevRevenue = (float) Order::where('status', 'completed')->whereBetween('created_at', [$prevStart, $prevEnd])->sum('final_amount');
        $prevOrdersCount = Order::whereBetween('created_at', [$prevStart, $prevEnd])->count();
        $prevCompletedCount = Order::where('status', 'completed')->whereBetween('created_at', [$prevStart, $prevEnd])->count();
        $prevCancelledCount = Order::where('status', 'cancelled')->whereBetween('created_at', [$prevStart, $prevEnd])->count();
        $prevNewCustomersCount = User::where('role', 'customer')->whereBetween('created_at', [$prevStart, $prevEnd])->count();
        $prevProductsSoldCount = (int) OrderItem::whereHas('order', function ($q) use ($prevStart, $prevEnd) {
            $q->where('status', 'completed')->whereBetween('created_at', [$prevStart, $prevEnd]);
        })->sum('quantity');

        // Thống kê thẻ tổng quan
        $overviewStats = [
            'revenue' => [
                'label' => 'Tổng doanh thu',
                'value' => number_format($revenue, 0, ',', '.') . 'đ',
                'trend' => $getTrend($revenue, $prevRevenue),
                'icon' => 'payments',
                'color' => 'emerald'
            ],
            'orders' => [
                'label' => 'Tổng đơn hàng',
                'value' => number_format($ordersCount, 0, ',', '.'),
                'trend' => $getTrend($ordersCount, $prevOrdersCount),
                'icon' => 'shopping_bag',
                'color' => 'blue'
            ],
            'completed' => [
                'label' => 'Đơn hoàn thành',
                'value' => number_format($completedCount, 0, ',', '.'),
                'trend' => $getTrend($completedCount, $prevCompletedCount),
                'icon' => 'check_circle',
                'color' => 'teal'
            ],
            'cancelled' => [
                'label' => 'Đơn đã hủy',
                'value' => number_format($cancelledCount, 0, ',', '.'),
                'trend' => $getTrend($cancelledCount, $prevCancelledCount),
                'icon' => 'cancel',
                'color' => 'red'
            ],
            'customers' => [
                'label' => 'Khách hàng mới',
                'value' => number_format($newCustomersCount, 0, ',', '.'),
                'trend' => $getTrend($newCustomersCount, $prevNewCustomersCount),
                'icon' => 'person_add',
                'color' => 'indigo'
            ],
            'products_sold' => [
                'label' => 'Sản phẩm đã bán',
                'value' => number_format($productsSoldCount, 0, ',', '.'),
                'trend' => $getTrend($productsSoldCount, $prevProductsSoldCount),
                'icon' => 'local_cafe',
                'color' => 'amber'
            ],
        ];

        // 5. Lấy doanh thu & số đơn theo ngày (Biểu đồ doanh thu)
        $dailyData = Order::where('status', 'completed')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as date, SUM(final_amount) as revenue, COUNT(id) as orders_count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $chartLabels = [];
        $chartRevenue = [];
        $chartOrders = [];

        $tempDate = $start->copy();
        while ($tempDate->lte($end)) {
            $dateStr = $tempDate->toDateString();
            $chartLabels[] = $tempDate->format('d/m');
            $chartRevenue[] = isset($dailyData[$dateStr]) ? (float)$dailyData[$dateStr]->revenue : 0.0;
            $chartOrders[] = isset($dailyData[$dateStr]) ? (int)$dailyData[$dateStr]->orders_count : 0;
            $tempDate->addDay();
        }

        $revenueChartData = [
            'labels' => $chartLabels,
            'revenue' => $chartRevenue,
            'orders' => $chartOrders,
        ];

        // 6. Biểu đồ trạng thái đơn hàng
        $orderStatuses = Order::whereBetween('created_at', [$start, $end])
            ->selectRaw('status, COUNT(id) as count')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $statusLabels = [
            'pending' => 'Chờ xác nhận',
            'confirmed' => 'Đã xác nhận',
            'shipping' => 'Đang giao',
            'completed' => 'Hoàn thành',
            'cancelled' => 'Đã hủy',
        ];

        $statusCounts = [];
        $statusPercentages = [];
        $totalOrdersInPeriod = $ordersCount ?: 1;

        foreach ($statusLabels as $key => $label) {
            $count = isset($orderStatuses[$key]) ? (int)$orderStatuses[$key]->count : 0;
            $statusCounts[] = $count;
            $statusPercentages[] = round(($count / $totalOrdersInPeriod) * 100, 1);
        }

        $orderStatusChartData = [
            'labels' => array_values($statusLabels),
            'counts' => $statusCounts,
            'percentages' => $statusPercentages,
        ];

        // 7. Báo cáo sản phẩm bán chạy (Top 5)
        $topProducts = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where('orders.status', 'completed')
            ->whereBetween('orders.created_at', [$start, $end])
            ->select(
                'products.id',
                'products.name',
                'products.image',
                'products.base_price as price',
                DB::raw('SUM(order_items.quantity) as total_qty'),
                DB::raw('SUM(order_items.quantity * order_items.unit_price) as total_revenue')
            )
            ->groupBy('products.id', 'products.name', 'products.image', 'products.base_price')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        $totalProductRevenue = $topProducts->sum('total_revenue') ?: 1;
        foreach ($topProducts as $p) {
            $p->percentage = round(($p->total_revenue / $totalProductRevenue) * 100, 1);
            if (empty($p->image)) {
                $p->image_url = asset('images/products/placeholder.jpg');
            } elseif (str_starts_with($p->image, 'storage/')) {
                $p->image_url = asset($p->image);
            } else {
                $p->image_url = asset('images/' . $p->image);
            }
        }

        // 8. Báo cáo doanh thu theo danh mục
        $categoryStats = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->where('orders.status', 'completed')
            ->whereBetween('orders.created_at', [$start, $end])
            ->select(
                'categories.name',
                DB::raw('SUM(order_items.quantity) as total_qty'),
                DB::raw('SUM(order_items.quantity * order_items.unit_price) as total_revenue')
            )
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_revenue')
            ->get();

        $totalCategoryRevenue = $categoryStats->sum('total_revenue') ?: 1;
        foreach ($categoryStats as $c) {
            $c->percentage = round(($c->total_revenue / $totalCategoryRevenue) * 100, 1);
        }

        // 9. Báo cáo khách hàng (Top 5 khách hàng chi tiêu nhiều nhất)
        $topCustomers = Order::query()
            ->where('status', 'completed')
            ->whereBetween('created_at', [$start, $end])
            ->select(
                'customer_name',
                'customer_phone',
                DB::raw('COUNT(id) as total_orders'),
                DB::raw('SUM(final_amount) as total_spend')
            )
            ->groupBy('customer_name', 'customer_phone')
            ->orderByDesc('total_spend')
            ->limit(5)
            ->get();

        // 10. Báo cáo tồn kho nguyên liệu
        $lowStockMaterials = Material::where('is_active', true)
            ->where('current_stock', '>', 0)
            ->where('current_stock', '<=', 10)
            ->orderBy('current_stock')
            ->limit(5)
            ->get();

        $outOfStockMaterials = Material::where('is_active', true)
            ->where('current_stock', '<=', 0)
            ->orderBy('name')
            ->limit(5)
            ->get();

        // Tính tổng giá trị tồn kho ước tính hiện tại
        $estimatedInventoryValue = (float) MaterialImport::where('remaining_quantity', '>', 0)
            ->get()
            ->sum(function ($import) {
                return $import->remaining_quantity * ($import->quantity > 0 ? ($import->total_price / $import->quantity) : 0);
            });

        // 11. Đơn hàng gần đây (Top 5 đơn đặt gần đây nhất)
        $recentOrders = Order::latest()
            ->limit(5)
            ->get()
            ->map(function ($order) {
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
                    'time' => $order->created_at->format('d/m/Y H:i'),
                ];
            });

        // Các biến dữ liệu dùng cho render partial hoặc view chính
        $data = compact(
            'preset',
            'start',
            'end',
            'overviewStats',
            'revenueChartData',
            'orderStatusChartData',
            'topProducts',
            'categoryStats',
            'topCustomers',
            'lowStockMaterials',
            'outOfStockMaterials',
            'estimatedInventoryValue',
            'recentOrders'
        );

        if ($request->ajax()) {
            return response()->json([
                'html' => view('backend.reports.partials.content', $data)->render(),
                'revenueChartData' => $revenueChartData,
                'orderStatusChartData' => $orderStatusChartData,
            ]);
        }

        return view('backend.reports.index', $data);
    }
}
