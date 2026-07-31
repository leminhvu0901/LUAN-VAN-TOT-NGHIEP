<?php
namespace App\Http\Controllers\Backend\Admin;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\User;
use App\Models\OrderItem;
use App\Models\Material;
use App\Models\MaterialImport;
use App\Models\Product;
use App\Models\Review;
use Carbon\Carbon;

class DashboardController
{
    public function index()
    {
        $now = Carbon::now();
        $startOfToday = Carbon::today();
        $endOfToday = Carbon::tomorrow()->subSecond();
        
        $startOfYesterday = Carbon::yesterday();
        $endOfYesterday = Carbon::today()->subSecond();

        // 1. Thống kê vận hành hôm nay
        $ordersTodayCount = Order::whereBetween('created_at', [$startOfToday, $endOfToday])->count();
        $ordersYesterdayCount = Order::whereBetween('created_at', [$startOfYesterday, $endOfYesterday])->count();

        $revenueToday = (float) Order::where('status', 'completed')->whereBetween('created_at', [$startOfToday, $endOfToday])->sum('final_amount');
        $revenueYesterday = (float) Order::where('status', 'completed')->whereBetween('created_at', [$startOfYesterday, $endOfYesterday])->sum('final_amount');

        $newCustomersToday = User::where('role', 'customer')->whereBetween('created_at', [$startOfToday, $endOfToday])->count();
        $newCustomersYesterday = User::where('role', 'customer')->whereBetween('created_at', [$startOfYesterday, $endOfYesterday])->count();

        $productsSoldToday = (int) OrderItem::whereHas('order', function ($q) use ($startOfToday, $endOfToday) {
            $q->where('status', 'completed')->whereBetween('created_at', [$startOfToday, $endOfToday]);
        })->sum('quantity');
        $productsSoldYesterday = (int) OrderItem::whereHas('order', function ($q) use ($startOfYesterday, $endOfYesterday) {
            $q->where('status', 'completed')->whereBetween('created_at', [$startOfYesterday, $endOfYesterday]);
        })->sum('quantity');

        // Tỷ lệ hoàn thành & thanh toán chung
        $totalOrdersAllTime = Order::count() ?: 1;
        $completedOrdersAllTime = Order::where('status', 'completed')->count();
        $paidOrdersAllTime = Order::where('payment_status', 'paid')->count();
        $completionRate = round(($completedOrdersAllTime / $totalOrdersAllTime) * 100, 1);
        $paymentSuccessRate = round(($paidOrdersAllTime / $totalOrdersAllTime) * 100, 1);

        // Xu hướng so với hôm qua
        $compareStats = [
            'orders' => [
                'value' => $ordersTodayCount,
                'diff' => $ordersTodayCount - $ordersYesterdayCount,
            ],
            'revenue' => [
                'value' => number_format($revenueToday, 0, ',', '.') . 'đ',
                'diff' => $revenueToday - $revenueYesterday,
                'diff_formatted' => number_format(abs($revenueToday - $revenueYesterday), 0, ',', '.') . 'đ',
            ],
            'customers' => [
                'value' => $newCustomersToday,
                'diff' => $newCustomersToday - $newCustomersYesterday,
            ],
            'products_sold' => [
                'value' => $productsSoldToday,
                'diff' => $productsSoldToday - $productsSoldYesterday,
            ],
            'completion_rate' => $completionRate,
            'payment_rate' => $paymentSuccessRate
        ];

        // 2. Khu vực "Cần xử lý ngay" (Tác vụ / Cảnh báo khẩn cấp)
        $pendingOrdersCount = Order::where('status', 'pending')->count();
        $shippingOrdersCount = Order::where('status', 'shipping')->count();
        $cancelledOrdersCount = Order::where('status', 'cancelled')->count();
        
        $unhandledReviewsCount = Review::where('is_visible', 0)->count();
        $hiddenProductsCount = Product::where('is_active', 0)->count();

        // Cảnh báo kho nguyên liệu
        $outOfStockMaterialsCount = Material::where('is_active', true)->where('current_stock', '<=', 0)->count();
        $lowStockMaterialsCount = Material::where('is_active', true)->where('current_stock', '>', 0)->where('current_stock', '<', 5)->count();
        
        // Hạn sử dụng nguyên liệu
        $expiringMaterialsCount = Material::where('is_active', true)
            ->whereHas('imports', function ($subQuery) {
                $subQuery->whereNotNull('expiration_date')
                    ->where('remaining_quantity', '>', 0)
                    ->whereBetween('expiration_date', [today(), today()->addDays(30)]);
            })->count();

        $actionTasks = [
            'pending_orders' => [
                'label' => 'Đơn chờ xác nhận',
                'count' => $pendingOrdersCount,
                'desc' => 'Cần xem duyệt ngay',
                'icon' => 'pending_actions',
                'color' => 'amber',
                'link' => route('admin.orders.index', ['status' => 'pending'])
            ],
            'shipping_orders' => [
                'label' => 'Đơn đang giao',
                'count' => $shippingOrdersCount,
                'desc' => 'Đang trên đường giao',
                'icon' => 'local_shipping',
                'color' => 'blue',
                'link' => route('admin.orders.index', ['status' => 'shipping'])
            ],
            'cancelled_orders' => [
                'label' => 'Đơn đã hủy',
                'count' => $cancelledOrdersCount,
                'desc' => 'Hủy trong hôm nay',
                'icon' => 'cancel',
                'color' => 'red',
                'link' => route('admin.orders.index', ['status' => 'cancelled'])
            ],
            'unhandled_reviews' => [
                'label' => 'Đánh giá ẩn',
                'count' => $unhandledReviewsCount,
                'desc' => 'Chờ kiểm duyệt hiển thị',
                'icon' => 'rate_review',
                'color' => 'purple',
                'link' => route('admin.reviews.index', ['status' => 'hidden'])
            ],
            'hidden_products' => [
                'label' => 'Sản phẩm đang ẩn',
                'count' => $hiddenProductsCount,
                'desc' => 'Đang tạm dừng kinh doanh',
                'icon' => 'visibility_off',
                'color' => 'gray',
                'link' => route('admin.products.index', ['status' => 'inactive'])
            ],
            'low_stock_materials' => [
                'label' => 'Nguyên liệu sắp hết',
                'count' => $lowStockMaterialsCount,
                'desc' => 'Tồn kho nhỏ hơn mức tối thiểu',
                'icon' => 'warning',
                'color' => 'amber',
                'link' => route('admin.materials.index', ['status' => 'low_stock'])
            ],
            'expiring_materials' => [
                'label' => 'Lô hàng sắp hết hạn',
                'count' => $expiringMaterialsCount,
                'desc' => 'Hạn sử dụng dưới 30 ngày',
                'icon' => 'hourglass_empty',
                'color' => 'red',
                'link' => route('admin.materials.index', ['status' => 'expiring'])
            ],
            'out_of_stock_materials' => [
                'label' => 'Nguyên liệu đã hết',
                'count' => $outOfStockMaterialsCount,
                'desc' => 'Cần nhập kho bổ sung gấp',
                'icon' => 'inventory_2',
                'color' => 'red',
                'link' => route('admin.materials.index', ['status' => 'out_of_stock'])
            ]
        ];

        // 3. Biểu đồ vận hành 7 ngày gần nhất
        $chartLabels = [];
        $chartRevenue = [];
        $chartOrders = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $chartLabels[] = $date->format('d/m');
            $chartRevenue[] = (float) Order::where('status', 'completed')->whereDate('created_at', $date)->sum('final_amount');
            $chartOrders[] = Order::whereDate('created_at', $date)->count();
        }

        $chartData = [
            'labels' => $chartLabels,
            'revenue' => $chartRevenue,
            'orders' => $chartOrders
        ];

        // 4. Danh sách đơn hàng mới nhất (Top 5)
        $recentOrders = Order::latest()->limit(5)->get()->map(function ($order) {
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
                'time' => $order->created_at->format('d/m/Y H:i')
            ];
        });

        // 5. Đánh giá mới nhất (Top 5)
        $recentReviews = Review::with(['product', 'user'])
            ->latest()
            ->limit(5)
            ->get();

        // 6. Danh sách cảnh báo tồn kho nguyên liệu và hạn dùng (Top 8)
        $stockAlertList = collect();

        // B. Nguyên liệu hết hàng hoặc sắp hết
        Material::where('is_active', true)
            ->where('current_stock', '<', 5)
            ->orderBy('current_stock')
            ->get()->each(function ($item) use ($stockAlertList) {
                $isCritical = $item->current_stock <= 0;
                $stockAlertList->push([
                    'type' => 'material',
                    'name' => '[Kho] ' . $item->name,
                    'current' => $item->current_stock . ' ' . $item->unit,
                    'min' => '5 ' . $item->unit,
                    'status' => $isCritical ? 'Hết hàng' : 'Sắp hết hàng',
                    'is_critical' => $isCritical,
                    'link' => route('admin.materials.index')
                ]);
            });

        // C. Lô hàng nguyên liệu sắp hết hạn (dưới 30 ngày)
        MaterialImport::with('material')
            ->where('remaining_quantity', '>', 0)
            ->whereNotNull('expiration_date')
            ->whereBetween('expiration_date', [today(), today()->addDays(30)])
            ->orderBy('expiration_date')
            ->get()->each(function ($import) use ($stockAlertList) {
                if ($import->material) {
                    $daysLeft = today()->diffInDays($import->expiration_date, false);
                    $daysText = $daysLeft <= 0 ? 'Hết hạn' : 'Còn ' . $daysLeft . ' ngày';
                    $stockAlertList->push([
                        'type' => 'expiry',
                        'name' => '[Hạn dùng] ' . $import->material->name . ' (Lô #' . $import->id . ')',
                        'current' => $import->remaining_quantity . ' ' . ($import->material->unit ?? ''),
                        'min' => 'HSD: ' . $import->expiration_date->format('d/m/Y'),
                        'status' => $daysText,
                        'is_critical' => $daysLeft <= 7,
                        'link' => route('admin.materials.index')
                    ]);
                }
            });

        $stockAlerts = $stockAlertList->take(8)->values();

        // 7. Dòng hoạt động gần đây (Tự động tổng hợp từ dữ liệu thực tế mới nhất)
        $activities = collect();

        // Thêm Đơn hàng mới
        Order::latest()->limit(3)->get()->each(function ($order) use ($activities) {
            $activities->push([
                'time' => $order->created_at,
                'icon' => 'shopping_cart',
                'color' => 'emerald',
                'text' => "Đơn hàng " . ($order->order_code ?: '#HPY-'.$order->id) . " vừa được đặt bởi khách hàng " . $order->customer_name,
                'link' => route('admin.orders.show', $order->id)
            ]);
        });

        // Thêm Đánh giá mới
        Review::with('product')->latest()->limit(3)->get()->each(function ($review) use ($activities) {
            $name = $review->user ? $review->user->name : 'Khách vãng lai';
            $activities->push([
                'time' => $review->created_at,
                'icon' => 'rate_review',
                'color' => 'amber',
                'text' => "Khách hàng " . $name . " đã gửi đánh giá " . $review->rating . " sao cho " . ($review->product ? $review->product->name : 'sản phẩm'),
                'link' => route('admin.reviews.index')
            ]);
        });

        // Thêm Nhập kho mới
        MaterialImport::with('material')->latest()->limit(3)->get()->each(function ($import) use ($activities) {
            $matName = $import->material ? $import->material->name : 'nguyên liệu';
            $activities->push([
                'time' => $import->created_at,
                'icon' => 'inventory_2',
                'color' => 'blue',
                'text' => "Nhập kho thành công " . $import->quantity . " " . ($import->material->unit ?? '') . " " . $matName,
                'link' => route('admin.materials.index')
            ]);
        });

        // Thêm Đăng ký mới
        User::where('role', 'customer')->latest()->limit(3)->get()->each(function ($user) use ($activities) {
            $activities->push([
                'time' => $user->created_at,
                'icon' => 'person_add',
                'color' => 'indigo',
                'text' => "Khách hàng mới " . $user->name . " vừa đăng ký tài khoản thành viên",
                'link' => route('admin.customers.index')
            ]);
        });

        $recentActivities = $activities->sortByDesc('time')->take(8)->values();

        return  view('backend.admin.dashboard.index', compact(
            'compareStats',
            'actionTasks',
            'chartData',
            'recentOrders',
            'recentReviews',
            'stockAlerts',
            'recentActivities'
        ));
    }
}
