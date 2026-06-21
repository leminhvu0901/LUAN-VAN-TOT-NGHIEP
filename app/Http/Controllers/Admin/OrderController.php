<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class OrderController
{
    public function index(Request $request)
    {
        $today     = Carbon::today();
        $yesterday = Carbon::yesterday();
        $currentStatus = $request->query('status');

        // ── Đơn trong ngày ──────────────────────────────────────
        $todayOrdersCount     = DB::table('orders')->whereDate('created_at', $today)->count();
        $yesterdayOrdersCount = DB::table('orders')->whereDate('created_at', $yesterday)->count();

        // ── Doanh thu ngày (đơn hoàn thành) ─────────────────────
        $todayRevenue     = DB::table('orders')->whereDate('created_at', $today)->where('status', 'completed')->sum('final_amount');
        $yesterdayRevenue = DB::table('orders')->whereDate('created_at', $yesterday)->where('status', 'completed')->sum('final_amount');

        // ── Đơn chờ xử lý ───────────────────────────────────────
        $pendingOrdersCount = DB::table('orders')->where('status', 'pending')->count();

        // ── Đơn hủy tháng này vs tháng trước ────────────────────
        $now           = Carbon::now();
        $currentMonth  = $now->month;
        $currentYear   = $now->year;
        $lastMonth     = $now->copy()->subMonth();

        $cancelledOrdersCount = DB::table('orders')
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at',  $currentYear)
            ->where('status', 'cancelled')
            ->count();

        $lastMonthCancelledCount = DB::table('orders')
            ->whereMonth('created_at', $lastMonth->month)
            ->whereYear('created_at',  $lastMonth->year)
            ->where('status', 'cancelled')
            ->count();

        // ── Helper: tính % thay đổi ──────────────────────────────
        $pct = function (int|float $current, int|float $previous): string {
            if ($previous == 0) {
                return $current > 0 ? '+100%' : '0%';
            }
            $diff = round((($current - $previous) / $previous) * 100, 1);
            return ($diff >= 0 ? '+' : '') . $diff . '%';
        };

        // ── Trend labels ─────────────────────────────────────────
        $ordersTrend   = $pct($todayOrdersCount, $yesterdayOrdersCount)   . ' so với hôm qua';
        $revenueTrend  = $pct($todayRevenue,     $yesterdayRevenue)        . ' so với hôm qua';
        $cancelTrend   = $pct($cancelledOrdersCount, $lastMonthCancelledCount) . ' so với tháng trước';

        $stats = [
            'today_orders' => [
                'value'  => $todayOrdersCount,
                'trend'  => $ordersTrend,
                'is_up'  => $todayOrdersCount >= $yesterdayOrdersCount,
            ],
            'today_revenue' => [
                'value'  => number_format($todayRevenue, 0, ',', '.') . 'đ',
                'trend'  => $revenueTrend,
                'is_up'  => $todayRevenue >= $yesterdayRevenue,
            ],
            'pending_orders' => [
                'value'  => $pendingOrdersCount,
                'trend'  => $pendingOrdersCount > 0 ? 'Cần phê duyệt gấp' : 'Không có đơn chờ',
                'is_up'  => null,
            ],
            'cancelled_orders' => [
                'value'  => $cancelledOrdersCount,
                'trend'  => $cancelTrend,
                'is_up'  => $cancelledOrdersCount <= $lastMonthCancelledCount, // ít hủy hơn = tốt
            ],
        ];

        // Pagination and real data
        $ordersQuery = DB::table('orders')->orderBy('created_at', 'desc');
        
        if ($currentStatus && in_array($currentStatus, ['pending', 'confirmed', 'shipping', 'completed', 'cancelled'])) {
            $ordersQuery->where('status', $currentStatus);
        }

        // Search was previously handled here via DB query, now moved to Collection filter below for unaccent support

        if ($request->filled('date_from')) {
            $ordersQuery->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $ordersQuery->whereDate('created_at', '<=', $request->input('date_to'));
        }
        
        $allOrders = $ordersQuery->get();

        if ($request->filled('search')) {
            $search = mb_strtolower(trim($request->input('search')), 'UTF-8');
            $searchAscii = Str::ascii($search); // Xóa dấu tiếng Việt
            $searchCode = strtolower(str_replace('#', '', $search));
            $searchPhone = str_replace([' ', '.', '-'], '', $search);

            $allOrders = $allOrders->filter(function($dbOrder) use ($search, $searchAscii, $searchCode, $searchPhone) {
                $name = mb_strtolower($dbOrder->customer_name ?? '', 'UTF-8');
                $nameAscii = Str::ascii($name);
                
                $code = strtolower($dbOrder->order_code ?? ('hpy-' . $dbOrder->id));
                $phone = $dbOrder->customer_phone ?? '';
                
                if (str_contains($code, $searchCode)) return true;
                if (str_contains($phone, $searchPhone)) return true;
                if (str_contains($name, $search)) return true;
                if (str_contains($nameAscii, $searchAscii)) return true;
                
                return false;
            });
        }

        // Tạo phân trang thủ công
        $perPage = 10;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentPageItems = $allOrders->slice(($currentPage - 1) * $perPage, $perPage)->all();
        $paginator = new LengthAwarePaginator($currentPageItems, $allOrders->count(), $perPage, $currentPage, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
            'query' => $request->query()
        ]);
        
        $orders = [];
        foreach ($paginator as $dbOrder) {
            $statusLabel = 'Chờ xác nhận';
            $statusColor = 'warning';
            switch ($dbOrder->status) {
                case 'pending': $statusLabel = 'Chờ xác nhận'; $statusColor = 'warning'; break;
                case 'confirmed': $statusLabel = 'Đã xác nhận'; $statusColor = 'primary'; break;
                case 'shipping': $statusLabel = 'Đang giao'; $statusColor = 'info'; break;
                case 'completed': $statusLabel = 'Hoàn thành'; $statusColor = 'success'; break;
                case 'cancelled': $statusLabel = 'Đã hủy'; $statusColor = 'danger'; break;
            }

            $createdAt = Carbon::parse($dbOrder->created_at);

            $orders[] = [
                'id' => $dbOrder->id,
                'code' => $dbOrder->order_code ?? ('#HPY-' . $dbOrder->id),
                'customer_name' => $dbOrder->customer_name,
                'customer_phone' => $dbOrder->customer_phone,
                'total' => number_format($dbOrder->final_amount, 0, ',', '.') . ' VNĐ',
                'payment_method' => strtoupper($dbOrder->payment_method ?? 'COD'),
                'payment_status' => $dbOrder->payment_status ?? 'unpaid',
                'status' => $statusLabel,
                'raw_status' => $dbOrder->status,
                'status_color' => $statusColor,
                'time' => $createdAt->format('H:i') . "\n" . $createdAt->format('d/m/Y'),
            ];
        }

        if ($request->ajax() || $request->has('ajax')) {
            return view('admin.orders.partials.table', compact('orders', 'paginator', 'currentStatus'))->render();
        }

        return view('admin.orders.index', compact('stats', 'orders', 'paginator', 'currentStatus'));
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,shipping,completed,cancelled',
            'cancel_reason' => 'nullable|string|max:500'
        ]);

        $updateData = [
            'status' => $request->input('status'),
            'updated_at' => now()
        ];

        if ($request->input('status') === 'cancelled') {
            $updateData['cancel_reason'] = $request->input('cancel_reason');
        }

        DB::table('orders')
            ->where('id', $id)
            ->update($updateData);

        return redirect()->back()->with('success', 'Đã cập nhật trạng thái đơn hàng thành công!');
    }
    public function show($id)
    {
        $order = DB::table('orders')->where('id', $id)->first();
        if (!$order) {
            return redirect()->route('admin.orders.index')->with('error', 'Không tìm thấy đơn hàng!');
        }

        $items = DB::table('order_items')
            ->leftJoin('products', 'order_items.product_id', '=', 'products.id')
            ->where('order_items.order_id', $id)
            ->select('order_items.*', 'products.name as product_name', 'products.image as product_image')
            ->get();

        return view('admin.orders.show', compact('order', 'items'));
    }

    public function create()
    {
        // Fetch categories if table exists, otherwise mock
        $categories = Schema::hasTable('categories') 
            ? DB::table('categories')->get() 
            : collect([
                (object)['id' => 1, 'name' => 'Cà phê'],
                (object)['id' => 2, 'name' => 'Trà sữa'],
                (object)['id' => 3, 'name' => 'Trà trái cây'],
                (object)['id' => 4, 'name' => 'Đá xay']
            ]);

        // Fetch products
        $products = Schema::hasTable('products') 
            ? DB::table('products')->get() 
            : collect([]); // If no table, return empty

        return view('admin.orders.create', compact('categories', 'products'));
    }

    public function store(Request $request)
    {
        try {
            $items = $request->input('items', []);
            if (empty($items)) {
                return response()->json(['success' => false, 'message' => 'Giỏ hàng trống']);
            }

            // Generate order code
            $orderCode = 'POS-' . strtoupper(bin2hex(random_bytes(3)));

            // Determine status based on type
            $orderType = $request->input('order_type', 'dine-in');
            $status = in_array($orderType, ['dine-in', 'takeaway']) ? 'completed' : 'pending';

            DB::beginTransaction();

            $orderId = DB::table('orders')->insertGetId([
                'order_code' => $orderCode,
                'customer_name' => $request->input('customer_name', 'Khách lẻ'),
                'customer_phone' => $request->input('customer_phone', ''),
                'total_amount' => $request->input('final_amount', 0),
                'discount_amount' => 0,
                'final_amount' => $request->input('final_amount', 0),
                'payment_status' => 'paid', // POS orders are usually paid immediately
                'payment_method' => 'cash',
                'status' => $status,
                'delivery_type' => $orderType,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            foreach ($items as $item) {
                DB::table('order_items')->insert([
                    'order_id' => $orderId,
                    'product_id' => $item['id'],
                    'size_name' => 'M', // Default for POS fast order
                    'quantity' => $item['qty'],
                    'unit_price' => $item['price'],
                    'sugar_level' => '100%',
                    'ice_level' => '100%',
                    'options' => json_encode(['Mặc định'], JSON_UNESCAPED_UNICODE),
                    'note' => null
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true, 
                'message' => 'Đã tạo đơn hàng thành công!',
                'order_code' => $orderCode
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function export(Request $request)
    {
        // Áp dụng cùng bộ lọc như trang index
        $ordersQuery = DB::table('orders')->orderBy('created_at', 'desc');

        $currentStatus = $request->query('status');
        if ($currentStatus && in_array($currentStatus, ['pending', 'confirmed', 'shipping', 'completed', 'cancelled'])) {
            $ordersQuery->where('status', $currentStatus);
        }

        if ($request->filled('date_from')) {
            $ordersQuery->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $ordersQuery->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $allOrders = $ordersQuery->get();

        if ($request->filled('search')) {
            $search = mb_strtolower(trim($request->input('search')), 'UTF-8');
            $searchAscii = Str::ascii($search);
            $searchCode  = strtolower(str_replace('#', '', $search));
            $searchPhone = str_replace([' ', '.', '-'], '', $search);

            $allOrders = $allOrders->filter(function ($o) use ($search, $searchAscii, $searchCode, $searchPhone) {
                $name      = mb_strtolower($o->customer_name ?? '', 'UTF-8');
                $nameAscii = Str::ascii($name);
                $code      = strtolower($o->order_code ?? ('hpy-' . $o->id));
                $phone     = $o->customer_phone ?? '';

                return str_contains($code, $searchCode)
                    || str_contains($phone, $searchPhone)
                    || str_contains($name, $search)
                    || str_contains($nameAscii, $searchAscii);
            });
        }

        // Map trạng thái sang tiếng Việt
        $statusMap = [
            'pending'   => 'Chờ xác nhận',
            'confirmed' => 'Đã xác nhận',
            'shipping'  => 'Đang giao',
            'completed' => 'Hoàn thành',
            'cancelled' => 'Đã hủy',
        ];

        // Tên file theo ngày xuất & bộ lọc
        $filename = 'don-hang_' . now()->format('Ymd_His') . '.csv';

        // Stream CSV response
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
            'Pragma'              => 'no-cache',
            'Expires'             => '0',
        ];

        $callback = function () use ($allOrders, $statusMap) {
            $handle = fopen('php://output', 'w');

            // BOM UTF-8 để Excel nhận dạng tiếng Việt đúng
            fputs($handle, "\xEF\xBB\xBF");

            // Header row
            fputcsv($handle, [
                'Mã đơn hàng',
                'Tên khách hàng',
                'Số điện thoại',
                'Tổng tiền (VNĐ)',
                'Phương thức thanh toán',
                'Trạng thái thanh toán',
                'Trạng thái đơn hàng',
                'Loại đơn',
                'Thời gian tạo',
            ]);

            foreach ($allOrders as $o) {
                fputcsv($handle, [
                    $o->order_code ?? ('#HPY-' . $o->id),
                    $o->customer_name  ?? 'Khách lẻ',
                    $o->customer_phone ?? '',
                    number_format($o->final_amount, 0, ',', '.'),
                    strtoupper($o->payment_method ?? 'COD'),
                    $o->payment_status === 'paid' ? 'Đã thanh toán' : 'Chưa thanh toán',
                    $statusMap[$o->status] ?? $o->status,
                    $o->delivery_type   ?? '',
                    Carbon::parse($o->created_at)->format('d/m/Y H:i'),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
