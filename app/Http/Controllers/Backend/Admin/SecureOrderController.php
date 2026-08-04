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
    public function __construct(private readonly OrderWorkflowService $orderWorkflow) {}

    /**
     * Hàm lấy danh sách và hiển thị tất cả đơn hàng lên trang quản trị (Trang Index).
     * Hỗ trợ các bộ lọc nâng cao (trạng thái, ngày bắt đầu, ngày kết thúc, từ khóa tìm kiếm) và phân trang.
     * 
     * Phản hồi trả về:
     * - Nếu là request AJAX: Trả về JSON chứa HTML của bảng danh sách mới và thẻ thống kê mới 
     *   để nhận diện và cập nhật lại giao diện trong JS tại file [public/js/backend/admin/orders/index.js].
     * - Nếu là request thường: Trả về view HTML đầy đủ.
     */
    public function index(Request $request)
    {
        $status = $request->query('status'); // Lấy tham số trạng thái từ đường dẫn URL (query string)
        $query = Order::query()->latest(); // Khởi tạo đối tượng truy vấn Builder của bảng orders, mặc định sắp xếp đơn mới nhất lên đầu

        if ($request->query('status')) {
            if (in_array($status, ['pending', 'confirmed', 'shipping', 'completed', 'cancelled'], true)) {
                $query->where('status', $status); // Lọc đơn hàng theo trạng thái đã chọn
            }
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from')); // Lọc đơn hàng từ ngày
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to')); // Lọc đơn hàng đến ngày
        }
        if ($request->input('sort') === 'asc') {
            $query->reorder('created_at'); // Đổi sang sắp xếp đơn cũ nhất lên trước
        }

        $collection = $query->get(); // Thực hiện truy vấn lấy toàn bộ dữ liệu đơn hàng thỏa mãn điều kiện lọc để lọc nâng cao bằng Collection
        if ($request->filled('search')) {
            $needle = Str::ascii(mb_strtolower(trim($request->input('search')))); // Chuyển từ khóa tìm kiếm về chữ thường không dấu để so khớp chính xác
            $collection = $collection->filter(function ($order) use ($needle) { // Lọc Collection động theo mã đơn hàng, tên khách hoặc số điện thoại
                $haystack = Str::ascii(mb_strtolower(implode(' ', [$order->order_code, $order->customer_name, $order->customer_phone]))); // Nối các trường thông tin đơn hàng làm chuỗi nguồn so khớp
                return str_contains($haystack, str_replace('#', '', $needle)); // Kiểm tra xem chuỗi nguồn có chứa từ khóa cần tìm hay không
            });
        }
        $page = LengthAwarePaginator::resolveCurrentPage(); // Xác định trang hiện tại từ URL phục vụ phân trang thủ công
        $paginator = new LengthAwarePaginator($collection->slice(($page - 1) * 10, 10)->values(), $collection->count(), 10, $page, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
            'query' => $request->query(), // Tạo bộ phân trang thủ công (10 đơn/trang) cho Collection
        ]);
        $labels = [
            'pending' => ['Chờ xác nhận', 'warning'],
            'confirmed' => ['Đã xác nhận', 'primary'],
            'shipping' => ['Đang giao', 'info'],
            'completed' => ['Hoàn thành', 'success'],
            'cancelled' => ['Đã hủy', 'danger'],
        ];
        $orders = collect($paginator->items())->map(function ($order) use ($labels) { // Duyệt qua danh sách các đơn hàng ở trang hiện tại để định dạng dữ liệu
            [$label, $color] = $labels[$order->status] ?? [$order->status, 'warning']; // Lấy nhãn tiếng Việt và màu sắc hiển thị tương ứng của trạng thái
            $created = Carbon::parse($order->created_at); // Phân tích định dạng thời gian tạo đơn bằng Carbon
            return [
                'id' => $order->id,
                'code' => $order->order_code ?: '#HPY-' . $order->id, // Trả về ID và mã đơn hàng (hoặc mã mặc định dự phòng nếu chưa có mã chính thức)
                'customer_name' => $order->customer_name,
                'customer_phone' => $order->customer_phone,
                'total' => number_format($order->final_amount, 0, ',', '.') . ' VNĐ', // Định dạng số tiền có dấu phân cách hàng nghìn
                'payment_method' => strtoupper($order->payment_method ?: 'COD'),
                'payment_status' => $order->payment_status ?: 'unpaid',
                'status' => $label,
                'raw_status' => $order->status,
                'status_color' => $color,
                'needs_admin_approval' => (bool) $order->needs_admin_approval,
                'time' => $created->format('H:i') . "\n" . $created->format('d/m/Y'), // Định dạng giờ phút và ngày tháng năm tạo đơn
            ];
        })->all();
        $stats = [ // Thống kê tổng số đơn hàng, doanh thu thực tế và số lượng đơn theo từng trạng thái
            'total_orders' => Order::count(),
            'total_revenue' => Order::where('status', 'completed')->where('payment_status', 'paid')->sum('final_amount'),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'cancelled_orders' => Order::where('status', 'cancelled')->count(),
        ];
        return  view('backend.admin.orders.index', compact('stats', 'orders', 'paginator'))->with('currentStatus', $status); // Trả về trang index chính thức
    }

    /**
     * Hàm hiển thị thông tin chi tiết của một đơn hàng cụ thể.
     * Thực hiện truy vấn thông tin đơn hàng và liên kết (Left Join) lấy tên, hình ảnh sản phẩm 
     * thực tế để đề phòng trường hợp sản phẩm gốc bị xóa khỏi hệ thống.
     */
    public function show($id)
    {
        $order = Order::find($id); // Tìm đơn hàng theo ID
        if (!$order) return redirect()->route('admin.orders.index')->with('error', 'Không tìm thấy đơn hàng!');
        $items = OrderItem::query()->leftJoin('products', 'order_items.product_id', '=', 'products.id')
            ->where('order_items.order_id', $id)->select('order_items.*') // Lấy danh sách sản phẩm nằm trong đơn hàng
            ->selectRaw('COALESCE(order_items.product_name, products.name) as product_name')
            ->selectRaw('COALESCE(order_items.product_image, products.image) as product_image')->get();
        return  view('backend.admin.orders.show', compact('order', 'items')); // Load giao diện chi tiết đơn hàng
    }

    /**
     * Hàm cập nhật trạng thái xử lý của đơn hàng (Confirmed, Shipping, Completed, Cancelled).
     * Gọi qua OrderWorkflowService để kiểm tra tính hợp lệ trạng thái và thực hiện trừ/hoàn kho tự động.
     * 
     * Phản hồi trả về:
     * - Nếu là cuộc gọi AJAX: Trả về JSON thành công để JS xử lý thông báo tại file [public/js/backend/admin/orders/show.js].
     * - Nếu là request thường: Chuyển hướng quay lại (back) kèm thông báo.
     */
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([ // Kiểm tra và xác thực các tham số đầu vào của trạng thái cập nhật gửi lên
            'status' => ['required', 'in:pending,confirmed,shipping,completed,cancelled'],
            'cancel_reason' => ['nullable', 'string', 'max:500'],
        ]);
        $this->orderWorkflow->transition(Order::findOrFail($id), $validated['status'], $validated['cancel_reason'] ?? null); // Gọi OrderWorkflowService để thực thi nghiệp vụ chuyển đổi trạng thái đơn hàng và ghi nhận lý do nếu hủy đơn

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Đã cập nhật trạng thái đơn hàng!']); // Trả về JSON cho JS tiếp nhận tại file [public/js/backend/admin/orders/show.js]
        }
        return back()->with('success', 'Đã cập nhật trạng thái đơn hàng!');
    }

    /**
     * Admin phê duyệt đơn hàng giá trị lớn đang chờ xác nhận.
     * Xóa cờ needs_admin_approval và chuyển trạng thái sang confirmed.
     */
    public function approveOrder(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        if (!$order->needs_admin_approval) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Đơn hàng này không cần phê duyệt.'], 422);
            }
            return back()->withErrors(['approval' => 'Đơn hàng này không cần phê duyệt.']);
        }

        // Xóa cờ chờ duyệt
        $order->needs_admin_approval = false;
        $order->save();

        // Chuyển sang confirmed qua workflow service
        $this->orderWorkflow->transition($order, 'confirmed');

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Đã phê duyệt đơn hàng!']);
        }

        return back()->with('success', 'Đã phê duyệt đơn hàng ' . $order->order_code . ' thành công!');
    }

    /**
     * Hàm xóa một đơn hàng khỏi hệ thống theo ID cụ thể.
     * 
     * Phản hồi trả về:
     * - Nếu là cuộc gọi AJAX: Trả về JSON thành công để JS thực hiện xóa dòng trên bảng tại file [public/js/backend/admin/orders/index.js] mà không cần reload trang.
     * - Nếu là request thường: Chuyển hướng back kèm thông báo.
     */
    public function destroy(Request $request, $id)
    {
        $order = Order::findOrFail($id); // Tìm đơn hàng theo ID hoặc ném lỗi 404
        $order->delete(); // Thực thi xóa đơn hàng khỏi CSDL

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([ // Trả về JSON cho JS tiếp nhận tại file [public/js/backend/admin/orders/index.js]
                'success' => true,
                'message' => 'Đã xóa đơn hàng thành công!'
            ]);
        }

        return back()->with('success', 'Đã xóa đơn hàng thành công!');
    }

    /**
     * Hàm xử lý xóa hàng loạt đơn hàng được chọn.
     * Hỗ trợ xóa theo danh sách ID cụ thể hoặc xóa toàn bộ các đơn hàng thỏa mãn điều kiện lọc của bộ tìm kiếm.
     */
    public function bulkDelete(Request $request)
    {
        $query = Order::query();
        if ($request->input('delete_all_pages') === '1') {
            if (in_array($request->input('status'), ['pending', 'confirmed', 'shipping', 'completed', 'cancelled'], true)) {
                $query->where('status', $request->input('status')); // Lọc trạng thái đơn cần xóa hàng loạt
            }
            if ($request->filled('date_from')) $query->whereDate('created_at', '>=', $request->input('date_from'));
            if ($request->filled('date_to')) $query->whereDate('created_at', '<=', $request->input('date_to'));
            if ($request->filled('search')) {
                $search = trim($request->input('search'));
                $query->where(fn($q) => $q->where('order_code', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")->orWhere('customer_phone', 'like', "%{$search}%")); // Lọc từ khóa cần xóa hàng loạt
            }
            $excluded = $request->validate(['excluded_order_ids' => ['sometimes', 'array'], 'excluded_order_ids.*' => ['integer']])['excluded_order_ids'] ?? [];
            if ($excluded) $query->whereNotIn('id', $excluded); // Loại trừ những đơn hàng admin bỏ chọn thủ công
        } else {
            $ids = $request->validate(['order_ids' => ['required', 'array'], 'order_ids.*' => ['integer', 'exists:orders,id']])['order_ids'];
            $query->whereIn('id', $ids); // Lọc theo danh sách ID đơn được tích chọn
        }

        $count = $query->count(); // Đếm tổng số đơn hàng chuẩn bị xóa trong Database
        $query->delete(); // Thực thi câu lệnh SQL DELETE xóa hàng loạt các đơn hàng được chọn khỏi Database
        if ($count === 0) return back()->withErrors(['delete' => 'Không có đơn hàng nào được chọn.']); // Báo lỗi nếu không có đơn nào khớp
        return back()->with('success', "Đã xóa {$count} đơn hàng thành công."); // Chuyển hướng quay lại và thông báo kết quả cho người dùng
    }

    /**
     * Hàm xuất file báo cáo đơn hàng dạng CSV (Excel) dựa theo các tiêu chí tìm kiếm/lọc hiện có.
     * Sử dụng streamDownload để trả về file tải xuống dạng luồng (stream), tránh đầy bộ nhớ đệm máy chủ.
     */
    public function export(Request $request)
    {
        $query = Order::query()->latest();
        if (in_array($request->input('status'), ['pending', 'confirmed', 'shipping', 'completed', 'cancelled'], true)) {
            $query->where('status', $request->input('status')); // Lọc theo trạng thái xuất báo cáo
        }
        if ($request->filled('date_from')) $query->whereDate('created_at', '>=', $request->input('date_from'));
        if ($request->filled('date_to')) $query->whereDate('created_at', '<=', $request->input('date_to'));

        return response()->streamDownload(function () use ($query) { // Thực hiện xuất file luồng tải xuống trực tiếp từ máy chủ
            $output = fopen('php://output', 'w'); // Mở luồng đầu ra php://output để ghi dữ liệu trực tiếp vào bộ nhớ đệm tải xuống
            fwrite($output, "\xEF\xBB\xBF"); // Ghi mã UTF-8 BOM vào đầu tệp để Excel đọc được tiếng Việt có dấu không bị lỗi font chữ
            fputcsv($output, ['Mã đơn', 'Khách hàng', 'Điện thoại', 'Tổng tiền', 'Thanh toán', 'Trạng thái', 'Ngày tạo']); // Ghi tiêu đề (Header) cho các cột báo cáo
            $query->chunk(500, function ($orders) use ($output) { // Chia nhỏ truy vấn DB lấy mỗi lần 500 đơn hàng nhằm tránh làm quá tải bộ nhớ RAM
                foreach ($orders as $order) {
                    fputcsv($output, [
                        $order->order_code,
                        $order->customer_name,
                        $order->customer_phone,
                        $order->final_amount,
                        $order->payment_status,
                        $order->status,
                        $order->created_at
                    ]); // Ghi thông tin chi tiết từng đơn hàng thành một dòng trong CSV
                }
            });
            fclose($output); // Đóng kết nối luồng ghi tệp sau khi đã hoàn thành
        }, 'orders-' . now()->format('Ymd-His') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']); // Thiết lập tải xuống file dạng .csv kèm mốc thời gian hiện tại
    }
}
