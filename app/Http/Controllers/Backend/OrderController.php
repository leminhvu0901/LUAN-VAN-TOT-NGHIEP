<?php

namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class OrderController
{
    //TRANG CHU
    public function index(Request $request)
    {
        $currentStatus = $request->query('status');

        // ── Thống kê đơn giản ──────────────────────────────────────
        $totalOrdersCount = \App\Models\Order::query()->count();
        $totalRevenue     = \App\Models\Order::query()->where('status', 'completed')->sum('final_amount');
        $pendingOrdersCount = \App\Models\Order::query()->where('status', 'pending')->count();
        $cancelledOrdersCount = \App\Models\Order::query()->where('status', 'cancelled')->count();

        $stats = [
            'total_orders' => $totalOrdersCount,
            'total_revenue' => $totalRevenue,
            'pending_orders' => $pendingOrdersCount,
            'cancelled_orders' => $cancelledOrdersCount,
        ];

        // Pagination and real data
        $sortOrder = $request->input('sort', 'desc') === 'asc' ? 'asc' : 'desc';
        $ordersQuery = \App\Models\Order::query()->orderBy('created_at', $sortOrder);
        
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
            return response()->json([
                'table_html' => view('backend.orders.partials.table', compact('orders', 'paginator', 'currentStatus'))->render(),
                'stats_html' => view('backend.orders.partials.stats', compact('stats'))->render(),
            ]);
        }

        return view('backend.orders.index', compact('stats', 'orders', 'paginator', 'currentStatus'));
    }


    //HIEN THI CHI TIET DON HANG
    public function show($id)
    {
        $order = \App\Models\Order::query()->where('id', $id)->first();
        if (!$order) {
            return redirect()->route('admin.orders.index')->with('error', 'Không tìm thấy đơn hàng!');
        }

        $items = \App\Models\OrderItem::query()
            ->leftJoin('products', 'order_items.product_id', '=', 'products.id')
            ->where('order_items.order_id', $id)
            ->select('order_items.*', 'products.name as product_name', 'products.image as product_image')
            ->get();

        return view('backend.orders.show', compact('order', 'items'));
    }

    // CAP NHAT TRANG THAI DON HANG
    public function updateStatus(Request $request, $id)
    {
        $order     = \App\Models\Order::findOrFail($id);
        $oldStatus = $order->status;
        $newStatus = $request->input('status');

        if (in_array($newStatus, ['pending', 'confirmed', 'shipping', 'completed', 'cancelled'])) {
            $order->status = $newStatus;
            $order->save();

            // Cộng điểm tích lũy khi đơn chuyển sang "Hoàn thành" (chỉ 1 lần)
            if ($newStatus === 'completed' && $oldStatus !== 'completed' && $order->user_id) {
                $user = \App\Models\User::find($order->user_id);
                if ($user) {
                    $user->awardPoints($order->final_amount);
                }
            }
        }

        return back()->with('success', 'Đã cập nhật trạng thái đơn hàng!');
    }

    /**
     * Cộng điểm tích lũy và nâng hạng thành viên sau khi đơn hoàn thành.
     *
     * Tỷ lệ: 1 điểm = 1.000 VNĐ (dựa trên final_amount – số tiền thực trả)
     * Ngưỡng hạng:
     *   - new     :     0 –   499 điểm
     *   - silver  :   500 – 1.999 điểm
     *   - gold    : 2.000 – 4.999 điểm
     *   - diamond : ≥ 5.000 điểm
     */
    private function awardLoyaltyPoints(\App\Models\Order $order): void
    {
        $user = \App\Models\User::find($order->user_id);
        if (!$user) return;

        // 1 điểm = 1.000 VNĐ, làm tròn xuống
        $earnedPoints = (int) floor($order->final_amount / 1000);
        if ($earnedPoints <= 0) return;

        $newPoints = (int) ($user->points ?? 0) + $earnedPoints;

        // Xác định hạng thành viên theo tổng điểm tích lũy
        if ($newPoints >= 5000) {
            $newLevel = 'diamond';
        } elseif ($newPoints >= 2000) {
            $newLevel = 'gold';
        } elseif ($newPoints >= 500) {
            $newLevel = 'silver';
        } else {
            $newLevel = 'new';
        }

        $user->points          = $newPoints;
        $user->membership_level = $newLevel;
        $user->save();

        \Illuminate\Support\Facades\Log::info(
            "[Points] Order #{$order->order_code} completed. User #{$user->id} ({$user->name}): "
            . "+{$earnedPoints} điểm → tổng {$newPoints} điểm | Hạng: {$newLevel}"
        );
    }


    //XUAT FILE EXCEL
    public function export(Request $request)
    {
        // Áp dụng cùng bộ lọc như trang index
        $ordersQuery = \App\Models\Order::query()->orderBy('created_at', 'desc');

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
