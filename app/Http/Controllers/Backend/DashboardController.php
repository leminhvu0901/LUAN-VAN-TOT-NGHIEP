<?php
namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\User;
use App\Models\OrderItem;
use App\Models\MaterialImport;
use App\Models\Product;
use App\Models\Review;
use Carbon\Carbon;

class DashboardController
{
    public function index()
    {
        $now = Carbon::now();
        $currentMonth = $now->month;
        $currentYear = $now->year;
        
        $lastMonth = $now->copy()->subMonth();
        
        // CÁC HÀM XỬ LÝ (HELPER) DÙNG CHUNG BÊN TRONG HÀM INDEX
        
        // 1. Hàm tính toán phần trăm tăng/giảm so với kỳ trước
        $calculateTrend = function ($current, $previous) {
            $text = '0%';
            $color = 'text-secondary';
            $bg = 'bg-secondary-container';

            if ($previous == 0) {
                if ($current > 0) {
                    $text = '+100%';
                    $color = 'text-primary';
                    $bg = 'bg-primary/10';
                }
            } else {
                $diff = round((($current - $previous) / $previous) * 100, 1);
                $text = ($diff > 0 ? '+' : '') . $diff . '%';
                
                if ($diff > 0) {
                    $color = 'text-primary';
                    $bg = 'bg-primary/10';
                } elseif ($diff < 0) {
                    $color = 'text-error';
                    $bg = 'bg-error-container';
                }
            }
            
            return ['text' => $text, 'color' => $color, 'bg' => $bg];
        };

        // 2. Hàm gom nhóm và tính toán các chỉ số thống kê (Doanh thu, Chi phí, Lợi nhuận, Số đơn hàng, Khách mới)
        // Dựa vào khoảng thời gian hiện tại (Current) và khoảng thời gian trước đó (Previous)
        $buildStats = function($startCurrent, $endCurrent, $startPrevious, $endPrevious) use ($calculateTrend) {
            //lay doanh thu tu Order 
            $revenueCurrent = Order::where('status', 'completed')->whereBetween('created_at', [$startCurrent, $endCurrent])->sum('final_amount');
            $revenuePrevious = Order::where('status', 'completed')->whereBetween('created_at', [$startPrevious, $endPrevious])->sum('final_amount');
            
            //lay chi phi tu MaterialImport
            $expensesCurrent = MaterialImport::whereBetween('created_at', [$startCurrent, $endCurrent])->sum('total_price');
            $expensesPrevious = MaterialImport::whereBetween('created_at', [$startPrevious, $endPrevious])->sum('total_price');
            
            //lay loi nhuận
            $profitCurrent = $revenueCurrent - $expensesCurrent;
            $profitPrevious = $revenuePrevious - $expensesPrevious;
           
            //lay tong don hang
            $ordersCurrent = Order::where('status', 'completed')->whereBetween('created_at', [$startCurrent, $endCurrent])->count();
            $ordersPrevious = Order::where('status', 'completed')->whereBetween('created_at', [$startPrevious, $endPrevious])->count();
            
            //lay so khách hang
            $customersCurrent = User::where('role', 'customer')->whereBetween('created_at', [$startCurrent, $endCurrent])->count();
            $customersPrevious = User::where('role', 'customer')->whereBetween('created_at', [$startPrevious, $endPrevious])->count();
            
            return [
                'revenue' => [
                    'value' => number_format($revenueCurrent, 0, ',', '.') . 'đ',
                    'trend' => $calculateTrend($revenueCurrent, $revenuePrevious),
                ],
                'expenses' => [
                    'value' => number_format($expensesCurrent, 0, ',', '.') . 'đ',
                    'trend' => $calculateTrend($expensesCurrent, $expensesPrevious),
                ],
                'profit' => [
                    'value' => number_format($profitCurrent, 0, ',', '.') . 'đ',
                    'trend' => $calculateTrend($profitCurrent, $profitPrevious),
                ],
                'orders' => [
                    'value' => number_format($ordersCurrent, 0, ',', '.'),
                    'trend' => $calculateTrend($ordersCurrent, $ordersPrevious),
                ],
                'customers' => [
                    'value' => number_format($customersCurrent, 0, ',', '.'),
                    'trend' => $calculateTrend($customersCurrent, $customersPrevious),
                ],
            ];
        };

        // --- PHẦN 1: TÍNH TOÁN CÁC THỐNG KÊ TỔNG QUAN (CARD THỐNG KÊ) ---
        
        // A. Thống kê theo Tuần (Từ đầu tuần đến cuối tuần này SO VỚI tuần trước)
        $startOfWeek = $now->copy()->startOfWeek();
        $endOfWeek = $now->copy()->endOfWeek();
        $startOfLastWeek = $now->copy()->subWeek()->startOfWeek();
        $endOfLastWeek = $now->copy()->subWeek()->endOfWeek();
        $statsWeekly = $buildStats($startOfWeek, $endOfWeek, $startOfLastWeek, $endOfLastWeek);

        // B. Thống kê theo Tháng (Từ đầu tháng đến cuối tháng này SO VỚI tháng trước)
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();
        $statsMonthly = $buildStats($startOfMonth, $endOfMonth, $startOfLastMonth, $endOfLastMonth);

        // C. Thống kê theo Năm (Năm nay SO VỚI năm trước)
        $startOfYear = $now->copy()->startOfYear();
        $endOfYear = $now->copy()->endOfYear();
        $startOfLastYear = $now->copy()->subYear()->startOfYear();
        $endOfLastYear = $now->copy()->subYear()->endOfYear();
        $statsYearly = $buildStats($startOfYear, $endOfYear, $startOfLastYear, $endOfLastYear);

        $statsData = [
            'weekly' => $statsWeekly,
            'monthly' => $statsMonthly,
            'yearly' => $statsYearly
        ];

        // --- PHẦN 2: CÁC THỐNG KÊ VÀ DANH SÁCH BÊN DƯỚI ---

        // 1. Các chỉ số nhỏ bổ sung (Đếm số lượng Đơn chờ xác nhận, Đơn đang xử lý, Sản phẩm đang ẩn)
        $pendingProcessingOrders = Order::where('status', 'pending')->count();
        $processingOrders = Order::where('status', 'processing')->count();
        $hiddenProductsCount = Product::where('is_active', 0)->count();

        // 4. Thống kê tỷ lệ Phương thức thanh toán (Dùng cho Biểu đồ Donut 1)
        // Tính % số đơn dùng COD (Tiền mặt) và số đơn dùng MoMo
        $totalPaymentOrders = Order::count() ?: 1; 
        $codOrders = Order::where('payment_method', 'cod')->orWhereNull('payment_method')->count();
        $momoOrders = Order::where('payment_method', 'momo')->count();
        $paymentStats = [
            'cod' => [
                'count' => $codOrders,
                'percent' => round(($codOrders / $totalPaymentOrders) * 100)
            ],
            'momo' => [
                'count' => $momoOrders,
                'percent' => round(($momoOrders / $totalPaymentOrders) * 100)
            ]
        ];

        // 5. Thống kê tỷ lệ Hình thức nhận hàng (Dùng cho Biểu đồ Donut 2)
        // Tính % số đơn Giao hàng tận nơi (delivery) và Khách đến lấy (pickup)
        $deliveryOrdersCount = Order::where('delivery_type', 'delivery')->orWhereNull('delivery_type')->count();
        $pickupOrdersCount = Order::where('delivery_type', 'pickup')->count();
        $deliveryStats = [
            'delivery' => [
                'count' => $deliveryOrdersCount,
                'percent' => round(($deliveryOrdersCount / $totalPaymentOrders) * 100)
            ],
            'pickup' => [
                'count' => $pickupOrdersCount,
                'percent' => round(($pickupOrdersCount / $totalPaymentOrders) * 100)
            ]
        ];

        // 6. Lấy 5 Đánh giá (Review) mới nhất của khách hàng
        $latestReviews = Review::with(['product', 'user'])
            ->latest()
            ->take(5)
            ->get();
        // --- PHẦN 3: DỮ LIỆU ĐỂ VẼ BIỂU ĐỒ (CHART DATA) ---
        
        // A. Dữ liệu biểu đồ Tuần (Gồm 7 cột từ Thứ 2 đến Chủ nhật)
        $startOfWeek = $now->copy()->startOfWeek();
        $weeklyRevenue = [];
        $weeklyOrders = [];
        $weeklyCustomers = [];
        $weeklyLabels = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];
        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i);
            $weeklyRevenue[] = (float) Order::where('status', 'completed')->whereDate('created_at', $date)->sum('final_amount');
            $weeklyOrders[] = Order::whereDate('created_at', $date)->count();
            $weeklyCustomers[] = User::where('role', 'customer')->whereDate('created_at', $date)->count();
        }

        // B. Dữ liệu biểu đồ Tháng (Gồm 12 cột từ Tháng 1 đến Tháng 12 của năm hiện tại)
        $monthlyRevenue = [];
        $monthlyOrders = [];
        $monthlyCustomers = [];
        $monthlyLabels = ['Th 1', 'Th 2', 'Th 3', 'Th 4', 'Th 5', 'Th 6', 'Th 7', 'Th 8', 'Th 9', 'Th 10', 'Th 11', 'Th 12'];
        for ($i = 1; $i <= 12; $i++) {
            $monthlyRevenue[] = (float) Order::where('status', 'completed')->whereYear('created_at', $currentYear)->whereMonth('created_at', $i)->sum('final_amount');
            $monthlyOrders[] = Order::whereYear('created_at', $currentYear)->whereMonth('created_at', $i)->count();
            $monthlyCustomers[] = User::where('role', 'customer')->whereYear('created_at', $currentYear)->whereMonth('created_at', $i)->count();
        }

        // C. Dữ liệu biểu đồ Năm (Lấy dữ liệu của 5 năm gần nhất, bao gồm cả năm nay)
        $yearlyRevenue = [];
        $yearlyOrders = [];
        $yearlyCustomers = [];
        $yearlyLabels = [];
        for ($i = 4; $i >= 0; $i--) {
            $year = $currentYear - $i;
            $yearlyLabels[] = $year;
            $yearlyRevenue[] = (float) Order::where('status', 'completed')->whereYear('created_at', $year)->sum('final_amount');
            $yearlyOrders[] = Order::whereYear('created_at', $year)->count();
            $yearlyCustomers[] = User::where('role', 'customer')->whereYear('created_at', $year)->count();
        }

        $chartData = [
            'weekly' => ['labels' => $weeklyLabels, 'revenue' => $weeklyRevenue, 'orders' => $weeklyOrders, 'customers' => $weeklyCustomers],
            'monthly' => ['labels' => $monthlyLabels, 'revenue' => $monthlyRevenue, 'orders' => $monthlyOrders, 'customers' => $monthlyCustomers],
            'yearly' => ['labels' => $yearlyLabels, 'revenue' => $yearlyRevenue, 'orders' => $yearlyOrders, 'customers' => $yearlyCustomers],
        ];

        // --- CUỐI CÙNG: TRẢ VỀ VIEW KÈM THEO TOÀN BỘ DỮ LIỆU ĐÃ TÍNH TOÁN ---
        return view('backend.dashboard', compact(
            'statsData', 
            'chartData', 
            'pendingProcessingOrders',
            'processingOrders',
            'hiddenProductsCount',
            'paymentStats',
            'deliveryStats',
            'latestReviews'
        ));
    }
}
