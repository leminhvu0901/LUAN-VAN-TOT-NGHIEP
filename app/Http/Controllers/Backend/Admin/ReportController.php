<?php

namespace App\Http\Controllers\Backend\Admin;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Material;
use App\Models\MaterialImport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController
{
    public function index(Request $request)
    {
        $data = $this->buildReportData($request);

        if ($request->ajax()) {
            return response()->json([
                'html' => view('backend.admin.reports.partials.content', $data)->render(),
                'revenueChartData' => $data['revenueChartData'],
                'orderStatusChartData' => $data['orderStatusChartData'],
                'channelRevenueChartData' => $data['channelRevenueChartData'],
                'channelOrdersChartData' => $data['channelOrdersChartData'],
            ]);
        }

        return view('backend.admin.reports.index', $data);
    }

    /**
     * Xuất báo cáo ra file Excel (.xlsx) gồm nhiều sheet, dùng đúng khoảng thời gian đang lọc trên
     * giao diện (nhận lại qua query string preset/date_from/date_to).
     *
     * Ghi bằng StreamedResponse thay vì tạo file tạm rồi trả về: không để lại rác trong storage và
     * không phụ thuộc quyền ghi đĩa của server.
     */
    public function export(Request $request): StreamedResponse
    {
        $data = $this->buildReportData($request);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('Happy Tea')
            ->setTitle('Báo cáo kinh doanh')
            ->setDescription('Xuất từ trang Báo cáo & Thống kê');

        $periodLabel = $data['start']->format('d/m/Y') . ' - ' . $data['end']->format('d/m/Y');

        $this->buildOverviewSheet($spreadsheet->getActiveSheet(), $data, $periodLabel);
        $this->buildRevenueByDaySheet($spreadsheet->createSheet(), $data, $periodLabel);
        $this->buildTopProductsSheet($spreadsheet->createSheet(), $data, $periodLabel);
        $this->buildCategorySheet($spreadsheet->createSheet(), $data, $periodLabel);
        $this->buildTopCustomersSheet($spreadsheet->createSheet(), $data, $periodLabel);
        $this->buildInventorySheet($spreadsheet->createSheet(), $data, $periodLabel);

        $spreadsheet->setActiveSheetIndex(0);

        $fileName = 'bao-cao-happy-tea_' . $data['start']->format('Ymd') . '-' . $data['end']->format('Ymd') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    /**
     * Ghi tiêu đề lớn + dòng ghi chú khoảng thời gian ở đầu mỗi sheet, trả về số dòng tiếp theo.
     */
    private function writeSheetHeading(Worksheet $sheet, string $title, string $periodLabel, string $lastColumn): int
    {
        $sheet->setCellValue('A1', $title);
        $sheet->mergeCells('A1:' . $lastColumn . '1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $sheet->setCellValue('A2', 'Kỳ báo cáo: ' . $periodLabel . '  |  Xuất lúc: ' . now()->format('d/m/Y H:i'));
        $sheet->mergeCells('A2:' . $lastColumn . '2');
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10);
        $sheet->getStyle('A2')->getFont()->getColor()->setARGB('FF6B7280');

        return 4;
    }

    /**
     * Định dạng dòng tiêu đề cột (nền xanh, chữ trắng, có viền) + bật auto filter và cố định dòng.
     */
    private function styleHeaderRow(Worksheet $sheet, int $row, string $lastColumn): void
    {
        $range = 'A' . $row . ':' . $lastColumn . $row;
        $sheet->getStyle($range)->getFont()->setBold(true);
        $sheet->getStyle($range)->getFont()->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF059669');
        $sheet->getStyle($range)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getRowDimension($row)->setRowHeight(22);
        $sheet->setAutoFilter($range);
        $sheet->freezePane('A' . ($row + 1));
    }

    /**
     * Kẻ viền vùng dữ liệu và tự giãn bề rộng cột cho dễ đọc khi mở file.
     */
    private function finishSheet(Worksheet $sheet, int $headerRow, int $lastRow, string $lastColumn): void
    {
        if ($lastRow > $headerRow) {
            $sheet->getStyle('A' . ($headerRow + 1) . ':' . $lastColumn . $lastRow)
                ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        }

        foreach (range('A', $lastColumn) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    private function buildOverviewSheet(Worksheet $sheet, array $data, string $periodLabel): void
    {
        $sheet->setTitle('Tổng quan');
        $row = $this->writeSheetHeading($sheet, 'BÁO CÁO TỔNG QUAN', $periodLabel, 'C');

        $headerRow = $row;
        $sheet->fromArray(['Chỉ số', 'Giá trị', 'So với kỳ trước'], null, 'A' . $headerRow);
        $this->styleHeaderRow($sheet, $headerRow, 'C');

        $row++;
        foreach ($data['overviewStats'] as $stat) {
            $sheet->fromArray([$stat['label'], $stat['value'], $stat['trend']['text']], null, 'A' . $row);
            $row++;
        }

        $sheet->setCellValue('A' . $row, 'Giá trị tồn kho ước tính');
        $sheet->setCellValue('B' . $row, number_format($data['estimatedInventoryValue'], 0, ',', '.') . 'đ');

        $this->finishSheet($sheet, $headerRow, $row, 'C');
    }

    private function buildRevenueByDaySheet(Worksheet $sheet, array $data, string $periodLabel): void
    {
        $sheet->setTitle('Doanh thu theo ngày');
        $row = $this->writeSheetHeading($sheet, 'DOANH THU THEO NGÀY', $periodLabel, 'C');

        $headerRow = $row;
        $sheet->fromArray(['Ngày', 'Doanh thu (đ)', 'Số đơn hoàn thành'], null, 'A' . $headerRow);
        $this->styleHeaderRow($sheet, $headerRow, 'C');

        $row++;
        $chart = $data['revenueChartData'];
        foreach ($chart['labels'] as $index => $label) {
            $sheet->setCellValue('A' . $row, $label);
            // Ghi số THÔ (không format chuỗi) để Excel còn tính tổng/vẽ biểu đồ được.
            $sheet->setCellValue('B' . $row, (float) ($chart['revenue'][$index] ?? 0));
            $sheet->setCellValue('C' . $row, (int) ($chart['orders'][$index] ?? 0));
            $row++;
        }

        $lastRow = $row - 1;
        if ($lastRow >= $headerRow + 1) {
            $sheet->getStyle('B' . ($headerRow + 1) . ':B' . $lastRow)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->setCellValue('A' . $row, 'TỔNG CỘNG');
            $sheet->setCellValue('B' . $row, '=SUM(B' . ($headerRow + 1) . ':B' . $lastRow . ')');
            $sheet->setCellValue('C' . $row, '=SUM(C' . ($headerRow + 1) . ':C' . $lastRow . ')');
            $sheet->getStyle('A' . $row . ':C' . $row)->getFont()->setBold(true);
            $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('#,##0');
        }

        $this->finishSheet($sheet, $headerRow, $row, 'C');
    }

    private function buildTopProductsSheet(Worksheet $sheet, array $data, string $periodLabel): void
    {
        $sheet->setTitle('Sản phẩm bán chạy');
        $row = $this->writeSheetHeading($sheet, 'TOP SẢN PHẨM BÁN CHẠY', $periodLabel, 'E');

        $headerRow = $row;
        $sheet->fromArray(['#', 'Tên sản phẩm', 'Giá bán (đ)', 'Số lượng bán', 'Doanh thu (đ)'], null, 'A' . $headerRow);
        $this->styleHeaderRow($sheet, $headerRow, 'E');

        $row++;
        foreach ($data['topProducts'] as $index => $product) {
            $sheet->fromArray([
                $index + 1,
                $product->name,
                (float) $product->price,
                (int) $product->total_qty,
                (float) $product->total_revenue,
            ], null, 'A' . $row);
            $row++;
        }

        $lastRow = $row - 1;
        if ($lastRow >= $headerRow + 1) {
            $sheet->getStyle('C' . ($headerRow + 1) . ':C' . $lastRow)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('E' . ($headerRow + 1) . ':E' . $lastRow)->getNumberFormat()->setFormatCode('#,##0');
        }

        $this->finishSheet($sheet, $headerRow, $lastRow, 'E');
    }

    private function buildCategorySheet(Worksheet $sheet, array $data, string $periodLabel): void
    {
        $sheet->setTitle('Doanh thu theo danh mục');
        $row = $this->writeSheetHeading($sheet, 'DOANH THU THEO DANH MỤC', $periodLabel, 'D');

        $headerRow = $row;
        $sheet->fromArray(['Danh mục', 'Số lượng bán', 'Doanh thu (đ)', 'Tỷ trọng (%)'], null, 'A' . $headerRow);
        $this->styleHeaderRow($sheet, $headerRow, 'D');

        $row++;
        foreach ($data['categoryStats'] as $category) {
            $sheet->fromArray([
                $category->name,
                (int) $category->total_qty,
                (float) $category->total_revenue,
                (float) $category->percentage,
            ], null, 'A' . $row);
            $row++;
        }

        $lastRow = $row - 1;
        if ($lastRow >= $headerRow + 1) {
            $sheet->getStyle('C' . ($headerRow + 1) . ':C' . $lastRow)->getNumberFormat()->setFormatCode('#,##0');
        }

        $this->finishSheet($sheet, $headerRow, $lastRow, 'D');
    }

    private function buildTopCustomersSheet(Worksheet $sheet, array $data, string $periodLabel): void
    {
        $sheet->setTitle('Khách hàng thân thiết');
        $row = $this->writeSheetHeading($sheet, 'TOP KHÁCH HÀNG CHI TIÊU NHIỀU NHẤT', $periodLabel, 'D');

        $headerRow = $row;
        $sheet->fromArray(['#', 'Khách hàng', 'Số điện thoại', 'Số đơn', 'Tổng chi tiêu (đ)'], null, 'A' . $headerRow);
        $this->styleHeaderRow($sheet, $headerRow, 'E');

        $row++;
        foreach ($data['topCustomers'] as $index => $customer) {
            $sheet->fromArray([
                $index + 1,
                $customer->customer_name ?: 'Khách vãng lai',
                // Ghi SĐT dạng chuỗi để Excel không cắt số 0 ở đầu (0912... -> 912...).
                (string) $customer->customer_phone,
                (int) $customer->total_orders,
                (float) $customer->total_spend,
            ], null, 'A' . $row);
            $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('@');
            $row++;
        }

        $lastRow = $row - 1;
        if ($lastRow >= $headerRow + 1) {
            $sheet->getStyle('E' . ($headerRow + 1) . ':E' . $lastRow)->getNumberFormat()->setFormatCode('#,##0');
        }

        $this->finishSheet($sheet, $headerRow, $lastRow, 'E');
    }

    private function buildInventorySheet(Worksheet $sheet, array $data, string $periodLabel): void
    {
        $sheet->setTitle('Tồn kho nguyên liệu');
        $row = $this->writeSheetHeading($sheet, 'CẢNH BÁO TỒN KHO NGUYÊN LIỆU', $periodLabel, 'D');

        $headerRow = $row;
        $sheet->fromArray(['Nguyên liệu', 'Tồn hiện tại', 'Đơn vị', 'Tình trạng'], null, 'A' . $headerRow);
        $this->styleHeaderRow($sheet, $headerRow, 'D');

        $row++;
        foreach ($data['outOfStockMaterials'] as $material) {
            $sheet->fromArray([$material->name, (float) $material->current_stock, $material->unit, 'Đã hết hàng'], null, 'A' . $row);
            $sheet->getStyle('D' . $row)->getFont()->getColor()->setARGB('FFDC2626');
            $row++;
        }
        foreach ($data['lowStockMaterials'] as $material) {
            $sheet->fromArray([$material->name, (float) $material->current_stock, $material->unit, 'Sắp hết'], null, 'A' . $row);
            $sheet->getStyle('D' . $row)->getFont()->getColor()->setARGB('FFD97706');
            $row++;
        }

        $lastRow = $row - 1;
        if ($lastRow < $headerRow + 1) {
            $sheet->setCellValue('A' . $row, 'Không có nguyên liệu nào sắp hết hoặc đã hết.');
            $lastRow = $row;
        }

        $this->finishSheet($sheet, $headerRow, $lastRow, 'D');
    }

    /**
     * Tính toàn bộ số liệu báo cáo cho khoảng thời gian đang lọc. Tách riêng để trang xem (index)
     * và file Excel (export) luôn dùng CHUNG một nguồn số liệu, tránh lệch số giữa 2 nơi.
     */
    private function buildReportData(Request $request): array
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
            $chartRevenue[] = isset($dailyData[$dateStr]) ? (float) $dailyData[$dateStr]->revenue : 0.0;
            $chartOrders[] = isset($dailyData[$dateStr]) ? (int) $dailyData[$dateStr]->orders_count : 0;
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
            $count = isset($orderStatuses[$key]) ? (int) $orderStatuses[$key]->count : 0;
            $statusCounts[] = $count;
            $statusPercentages[] = round(($count / $totalOrdersInPeriod) * 100, 1);
        }

        $orderStatusChartData = [
            'labels' => array_values($statusLabels),
            'counts' => $statusCounts,
            'percentages' => $statusPercentages,
        ];

        // 6b. Biểu đồ so sánh doanh thu Tại quầy (pickup) và Đặt online (giao hàng) — chỉ tính đơn
        // đã hoàn thành (completed), giống cách tính $revenue ở trên để khớp với "Tổng doanh thu".
        $pickupRevenue = (float) Order::where('status', 'completed')
            ->where('delivery_type', 'pickup')
            ->whereBetween('created_at', [$start, $end])
            ->sum('final_amount');
        $deliveryRevenue = (float) Order::where('status', 'completed')
            ->where('delivery_type', 'delivery')
            ->whereBetween('created_at', [$start, $end])
            ->sum('final_amount');

        $channelRevenueChartData = [
            'labels' => ['Tại quầy', 'Đặt online'],
            'amounts' => [$pickupRevenue, $deliveryRevenue],
        ];

        // 6c. Biểu đồ số lượng đơn hàng theo kênh bán — đếm TẤT CẢ đơn trong kỳ (không lọc trạng
        // thái), khớp với cách tính "Tổng đơn hàng" ($ordersCount) ở thẻ tổng quan phía trên.
        $pickupOrdersCount = Order::where('delivery_type', 'pickup')->whereBetween('created_at', [$start, $end])->count();
        $deliveryOrdersCount = Order::where('delivery_type', 'delivery')->whereBetween('created_at', [$start, $end])->count();

        $channelOrdersChartData = [
            'labels' => ['Tại quầy', 'Đặt online'],
            'counts' => [$pickupOrdersCount, $deliveryOrdersCount],
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
            $p->image_url = upload_url($p->image) ?: asset('images/products/placeholder.jpg');
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
        return compact(
            'preset',
            'start',
            'end',
            'overviewStats',
            'revenueChartData',
            'orderStatusChartData',
            'channelRevenueChartData',
            'channelOrdersChartData',
            'topProducts',
            'categoryStats',
            'topCustomers',
            'lowStockMaterials',
            'outOfStockMaterials',
            'estimatedInventoryValue',
            'recentOrders'
        );
    }
}
