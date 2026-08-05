<?php

namespace App\Http\Controllers\Backend\Staff\Reception;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\CartPricingService;
use App\Services\NotificationService;
use App\Services\OrderService;
use App\Services\OrderWorkflowService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Controller Quản lý Đơn hàng dành cho Lễ tân (Reception Staff).
 *
 * Lễ tân có quyền xem và xử lý TOÀN BỘ đơn hàng của cửa hàng (không bị giới hạn theo người được phân công).
 * Đảm nhiệm việc tiếp nhận đơn online, tạo đơn tại quầy (POS), phân công đơn cho Shipper và thu tiền mặt tại quầy.
 */
class OrderController
{
    public function __construct(
        private readonly OrderWorkflowService $sv_orderWorkflow,
        private readonly OrderService $sv_orderService,
        private readonly CartPricingService $sv_cartPricing,
        private readonly NotificationService $sv_notifications,
    ) {
    }

    /**
     * Hiển thị danh sách đơn hàng cho Lễ tân.
     * Hỗ trợ lọc theo trạng thái, khoảng thời gian, sắp xếp ngày tạo, tìm kiếm đa năng theo mã đơn/tên/SĐT khách,
     * và phân trang tùy chỉnh.
     * Tự động dọn dẹp các đơn thanh toán MoMo bị quá hạn (stale pending) mỗi lần tải danh sách.
     */
    public function index(Request $request)
    {
        // Dọn đơn MoMo "chờ thanh toán" bị treo quá lâu mỗi lần lễ tân mở danh sách — không cần cron
        // để thấy hiệu quả ngay, cron (nếu có cấu hình) chỉ để dọn cả khi không ai mở trang.
        $this->sv_orderWorkflow->cancelStalePendingPayments();

        $status = $request->query('status'); // Đọc tham số lọc trạng thái từ URL
        $query = Order::query()->latest(); // Khởi tạo Builder truy vấn bảng orders, sắp xếp từ mới nhất xuống
        if (in_array($status, ['pending', 'confirmed', 'shipping', 'completed', 'cancelled'], true)) {
            $query->where('status', $status); // Lọc trạng thái đơn nếu hợp lệ
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from')); // Lọc từ ngày
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to')); // Lọc đến ngày
        }
        if ($request->input('sort') === 'asc') {
            $query->reorder('created_at'); // Thay đổi sang sắp xếp đơn cũ nhất lên trước
        }

        $collection = $query->get(); // Lấy toàn bộ đơn hàng thỏa mãn điều kiện lọc để xử lý Collection nâng cao
        if ($request->filled('search')) {
            $needle = Str::ascii(mb_strtolower(trim($request->input('search')))); // Chuyển từ khóa tìm kiếm thành chữ thường không dấu để so khớp
            $collection = $collection->filter(function ($order) use ($needle) {
                $haystack = Str::ascii(mb_strtolower(implode(' ', [$order->order_code, $order->customer_name, $order->customer_phone]))); // Chuỗi nguồn để so khớp thông tin đơn hàng
                return str_contains($haystack, str_replace('#', '', $needle)); // Lọc theo mã đơn, tên hoặc số điện thoại khách hàng
            });
        }

        $page = LengthAwarePaginator::resolveCurrentPage(); // Đọc trang hiện tại từ URL phục vụ phân trang Collection thủ công
        $paginator = new LengthAwarePaginator(
            $collection->slice(($page - 1) * 10, 10)->values(), // Cắt lấy dữ liệu của trang hiện tại (10 đơn/trang)
            $collection->count(), // Tổng số đơn hàng sau khi lọc
            10,
            $page,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(), // Đường dẫn URL phân trang hiện tại
                'query' => $request->query(), // Giữ các tham số lọc trên URL khi bấm chuyển trang
            ]
        );

        $labels = [ // Danh sách nhãn trạng thái tiếng Việt và class CSS tương ứng để hiển thị Badge
            'pending' => ['Chờ xác nhận', 'warning'],
            'confirmed' => ['Đã xác nhận', 'primary'],
            'shipping' => ['Đang giao', 'info'],
            'completed' => ['Hoàn thành', 'success'],
            'cancelled' => ['Đã hủy', 'danger'],
        ];

        $orders = collect($paginator->items())->map(function ($order) use ($labels) { // Định dạng lại mảng thông tin để đưa sang View
            [$label, $color] = $labels[$order->status] ?? [$order->status, 'warning'];
            $created = Carbon::parse($order->created_at);
            return [
                'id' => $order->id,
                'code' => $order->order_code ?: '#HPY-' . $order->id,
                'customer_name' => $order->customer_name,
                'customer_phone' => $order->customer_phone,
                'total' => number_format($order->final_amount, 0, ',', '.') . ' VNĐ', // Định dạng số tiền
                'payment_method' => strtoupper($order->payment_method ?: 'COD'),
                'payment_status' => $order->payment_status ?: 'unpaid',
                'status' => $label,
                'raw_status' => $order->status,
                'status_color' => $color,
                'delivery_type' => $order->delivery_type,
                // Đơn giao hàng đã xác nhận nhưng chưa gán shipper -> cần nổi bật để lễ tân biết mà phân công,
                // tránh đơn "kẹt" âm thầm ở trạng thái đã xác nhận không ai xử lý tiếp.
                'needs_delivery_assignment' => $order->delivery_type === 'delivery'
                    && $order->status === 'confirmed'
                    && !$order->delivery_staff_id,
                'needs_admin_approval' => (bool) $order->needs_admin_approval,
                'time' => $created->format('H:i') . "\n" . $created->format('d/m/Y'), // Định dạng thời gian tạo đơn
            ];
        })->all();

        $stats = [ // Thu thập số liệu thống kê đơn hàng để đưa lên đầu trang danh sách
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'cancelled_orders' => Order::where('status', 'cancelled')->count(),
        ];

        return view('backend.staff.reception.orders.index', compact('stats', 'orders', 'paginator'))->with('currentStatus', $status);
    }

    /**
     * Xem chi tiết 1 đơn hàng cụ thể.
     * Nạp thông tin danh sách sản phẩm (kèm tên/ảnh fallback từ bảng products), thông tin nhân viên giao hàng,
     * danh sách Shipper khả dụng (để phân công nếu đơn cần giao) và thông tin cửa hàng để chuẩn bị in hóa đơn.
     */
    public function show(Order $order)
    {
        // Thực hiện Left Join lấy sản phẩm gốc phòng trường hợp sản phẩm ban đầu bị admin xóa khỏi hệ thống
        $items = OrderItem::query()->leftJoin('products', 'order_items.product_id', '=', 'products.id')
            ->where('order_items.order_id', $order->id)->select('order_items.*')
            ->selectRaw('COALESCE(order_items.product_name, products.name) as product_name')
            ->selectRaw('COALESCE(order_items.product_image, products.image) as product_image')->get();

        $order->loadMissing('deliveryStaff'); // Nạp thông tin tài khoản nhân viên vận chuyển (nếu có)

        // Chỉ lấy danh sách tài khoản shipper đang hoạt động nếu đơn là đơn giao và chưa được gán shipper
        $availableDeliveryStaff = ($order->delivery_type === 'delivery' && $order->status === 'confirmed' && !$order->delivery_staff_id)
            ? \App\Models\User::where('role', 'staff')->where('staff_type', 'delivery')->where('is_active', true)->orderBy('name')->get()
            : collect();

        $storeInfo = [ // Lấy cấu hình thông tin quán trong bảng settings để hiển thị khi in hóa đơn POS
            'name' => \App\Models\Setting::getValue('store_name', 'Happy Tea'),
            'phone' => \App\Models\Setting::getValue('store_phone', ''),
            'address' => \App\Models\Setting::getValue('store_address', ''),
        ];

        return view('backend.staff.reception.orders.show', compact('order', 'items', 'availableDeliveryStaff', 'storeInfo'));
    }

    /**
     * Cập nhật trạng thái đơn hàng (Xác nhận, Hủy đơn,...).
     * Ràng buộc nghiệp vụ: Đối với đơn giao hàng (delivery), Lễ tân chỉ được chuyển trạng thái TRƯỚC khi đơn sang
     * bước giao hàng ('shipping'). Khi đơn đã ở trạng thái 'shipping', quyền cập nhật (hoàn thành/giao thất bại)
     * thuộc về Nhân viên vận chuyển để đảm bảo đúng quy trình kiểm soát.
     */
    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,confirmed,shipping,completed,cancelled'],
            'cancel_reason' => ['nullable', 'string', 'max:500'],
        ]);

        // Kiểm tra điều kiện bảo vệ: lễ tân chỉ được xử lý đơn giao hàng TRƯỚC khi gán cho shipper và shipper đi giao.
        // Khi shipper đã đi giao (shipping), lễ tân không được can thiệp vào tiến trình của đơn hàng nữa.
        $isDeliveryOrder = $order->delivery_type !== 'pickup';
        if ($isDeliveryOrder && ($order->status === 'shipping' || in_array($validated['status'], ['shipping', 'completed'], true))) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'status' => 'Đơn giao hàng đang trên đường đi (hoặc cần chuyển sang bước giao hàng) — chỉ nhân viên giao hàng được xử lý sau khi được phân công.',
            ]);
        }

        $this->sv_orderWorkflow->transition($order, $validated['status'], $validated['cancel_reason'] ?? null); // Gọi workflow service thực thi cập nhật trạng thái đơn hàng

        return back()->with('success', 'Đã cập nhật trạng thái đơn hàng!');
    }

    /**
     * Lễ tân gửi yêu cầu Admin phê duyệt đơn hàng giá trị lớn.
     * Đặt cờ needs_admin_approval = true và gửi email thông báo cho Admin.
     */
    public function requestAdminApproval(Request $request, Order $order)
    {
        // Chỉ cho gửi yêu cầu khi đơn đang ở trạng thái pending
        if ($order->status !== 'pending') {
            return back()->withErrors(['approval' => 'Chỉ có thể gửi yêu cầu cho đơn đang chờ xác nhận.']);
        }

        // Đặt cờ và lưu
        $order->needs_admin_approval = true;
        $order->save();

        // Gửi email thông báo cho Admin
        try {
            $this->sv_notifications->sendAdminApprovalRequest($order);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('requestAdminApproval: failed to send email', ['error' => $e->getMessage()]);
        }

        return back()->with('success', 'Đã gửi yêu cầu phê duyệt cho Admin! Vui lòng chờ Admin xác nhận.');
    }

    /**
     * Phân công Nhân viên vận chuyển (Shipper) cho đơn giao hàng đã xác nhận.
     */
    public function assignDelivery(Request $request, Order $order)
    {
        $validated = $request->validate([
            'delivery_staff_id' => ['required', 'integer'],
        ], [
            'delivery_staff_id.required' => 'Vui lòng chọn nhân viên giao hàng.',
        ]);

        $this->sv_orderWorkflow->assignDeliveryStaff(
            $order,
            (int) $validated['delivery_staff_id'],
            \Illuminate\Support\Facades\Auth::id()
        );

        return back()->with('success', 'Đã phân công nhân viên giao hàng!');
    }

    /**
     * Hiển thị màn hình Tạo đơn tại quầy (POS) cho Lễ tân.
     *
     * Lấy danh sách sản phẩm đang bán (kèm danh mục, kích thước, topping còn khả dụng)
     * và trạng thái kích hoạt cổng thanh toán MoMo để phục vụ bán hàng trực tiếp tại quầy.
     *
     * @return \Illuminate\View\View
     */
    public function createOrder()
    {
        // Vẫn lấy cả sản phẩm hết hàng (is_active=false) - đưa xuống cuối danh sách (view tự hiện nút
        // "Hết hàng" bị khoá cho các sản phẩm này) để lễ tân biết mà báo khách, thay vì món biến mất
        // khỏi màn hình như không tồn tại.
        $products = Product::with([
            'category',
            'sizes',
            'toppings' => function ($query) {
                $query->where('is_available', true);
            }
        ])->orderByDesc('is_active')->orderBy('name')->get();

        $categories = \App\Models\Category::query()->where('is_active', true)->orderBy('name')->get();
        // POS chỉ nên cho chọn MoMo/VNPay nếu admin đã bật kênh đó trong Cài đặt (tiền mặt luôn khả
        // dụng vì không phụ thuộc cổng thanh toán ngoài).
        $momoEnabled = (bool) \App\Models\Setting::getValue('momo_enabled', false);
        $vnpayEnabled = (bool) \App\Models\Setting::getValue('vnpay_enabled', false);

        // Khi trang này được tải lại sau redirect-back do lỗi validate lúc tạo đơn (vd. điểm vượt số
        // dư), old('customer_id') vẫn còn nhưng tên/SĐT/số điểm khách hàng không nằm trong old() -
        // truy lại từ DB để view tự hiện lại đúng khách đã chọn, tránh lễ tân phải tìm lại từ đầu.
        $selectedCustomer = old('customer_id')
            ? User::where('role', 'customer')->find(old('customer_id'))
            : null;

        return view('backend.staff.reception.orders.create', compact('products', 'categories', 'momoEnabled', 'vnpayEnabled', 'selectedCustomer'));
    }

    /**
     * Xem trước tổng tiền đơn hàng tại quầy (POS) qua AJAX trước khi tạo đơn.
     *
     * Tính toán tạm tính, kiểm tra áp dụng mã giảm giá nhập tay hoặc tự động chọn ưu đãi tốt nhất,
     * tính số tiền được giảm và tổng thanh toán cuối cùng. Vì là đơn tại quầy (pickup) nên phí giao hàng = 0.
     * 
     * Trả về JSON:
     * {
     *   "subtotal": float, "discount": float, "combo_discount": float, "membership_discount": float,
     *   "shipping_fee": 0, "promotion_code": string|null, "promotion_label": string|null,
     *   "points_discount": float, "points_error": string|null, "final_amount": float,
     *   "coupon_error": string|null, "gifts": array
     * }
     */
    public function previewTotal(Request $request)
    {
        $cart = Cart::query()->where('user_id', Auth::id())->first();
        $items = $cart ? \App\Models\CartItem::query()->where('cart_id', $cart->id)->get() : collect();

        if ($items->isEmpty()) {
            return response()->json([
                'subtotal' => 0,
                'discount' => 0,
                'shipping_fee' => 0,
                'promotion_code' => null,
                'promotion_label' => null,
                'points_discount' => 0,
                'points_error' => null,
                'final_amount' => 0,
                'coupon_error' => null,
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate');
        }

        $pricedItems = $this->sv_cartPricing->pricedItems($cart);
        $subtotal = $this->sv_cartPricing->subtotal($pricedItems);
        $totalQuantity = (int) $pricedItems->sum('quantity');

        $customerId = $request->query('customer_id');
        $orderOwner = $customerId ? User::where('role', 'customer')->find($customerId) : null;

        $couponCode = trim((string) $request->query('coupon_code', ''));
        $couponError = null;

        if ($couponCode !== '') {
            try {
                //xem trước số tiền giảm 
                $preview = $this->sv_orderService->previewManualCoupon($couponCode, $pricedItems, $orderOwner, $subtotal, 'pickup', $totalQuantity);
                $promotion = $preview['promotion'];
                $discount = $preview['discount'];
            } catch (\Illuminate\Validation\ValidationException $e) {
                $promotion = null;
                $discount = 0;
                $couponError = collect($e->errors())->flatten()->first();
            }
        } else {
            //Xem trước khuyến mãi tự động sẽ áp dụng cho một đơn tại quầy
            $preview = $this->sv_orderService->previewAutoPromotion($pricedItems, $subtotal, $totalQuantity);
            $promotion = $preview['promotion'];
            $discount = $preview['discount'];
        }

        // Xem trước giảm giá theo hạng thành viên — dùng ĐÚNG hàm OrderService::create() dùng thật,
        // để tổng tiền hiển thị trước khi tạo đơn luôn khớp với đơn thật sau khi tạo.
        $membershipDiscount = $this->sv_orderService->membershipDiscount($orderOwner, $subtotal);

        // Xem trước số tiền giảm từ điểm tích lũy (nếu lễ tân đã chọn khách hàng và nhập số điểm) —
        // dùng chung logic/hạn mức với lúc tạo đơn thật (OrderService::previewPointsDiscount()) để
        // không bao giờ lệch giữa preview và kết quả tạo đơn.
        $pointsToRedeem = (int) $request->query('points_to_redeem', 0);
        $pointsPreview = $this->sv_orderService->previewPointsDiscount($pointsToRedeem, $orderOwner, $subtotal);
        $pointsDiscount = $pointsPreview['discount'];
        $pointsError = $pointsPreview['error'];

        // Xem trước thưởng combo (phần tặng quà độc lập với mã giảm giá ở trên; phần giảm giá của combo
        // chỉ cộng thêm nếu không trùng sản phẩm với mã đó — xem PromotionService::resolveComboRewards())
        // để lễ tân biết trước khi tạo đơn.
        $comboResult = app(\App\Services\PromotionService::class)->resolveComboRewards(
            $pricedItems,
            'pickup',
            ['promotion' => $promotion, 'discount' => $discount]
        );
        $gifts = collect($comboResult['entries'])->where('type', 'gift');
        $comboDiscount = collect($comboResult['entries'])->where('type', 'discount')->sum('discount_amount');
        $discount = $comboResult['auto_discount'];

        $totalDiscount = min($subtotal, $discount + $comboDiscount + $membershipDiscount + $pointsDiscount);

        return response()->json([
            'subtotal' => $subtotal,
            'discount' => $discount,
            'combo_discount' => $comboDiscount,
            'membership_discount' => $membershipDiscount,
            'shipping_fee' => 0,
            'promotion_code' => $promotion?->code,
            'promotion_label' => $promotion
                ? ($promotion->type === 'percent' ? "Khuyến mãi -{$promotion->value}%" : 'Khuyến mãi') . " ({$promotion->code})"
                : null,
            'points_discount' => $pointsDiscount,
            'points_error' => $pointsError,
            'final_amount' => max(0, $subtotal - $totalDiscount),
            'coupon_error' => $couponError,
            'gifts' => $gifts->map(fn($g) => [
                'gift_product_name' => $g['gift_product']->name,
                'quantity' => $g['granted_quantity'],
            ])->values(),
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    /**
     * Lưu và tạo Đơn hàng mới tại quầy (POS).
     * Tiếp nhận phương thức thanh toán (tiền mặt / MoMo), loại nhận hàng (uống tại chỗ / mang về),
     * thông tin khách hàng (khách thành viên hoặc vãng lai), điểm thưởng áp dụng và mã giảm giá.
     * Nếu thanh toán MoMo -> chuyển hướng sang cổng MoMo; Nếu tiền mặt -> chuyển sang trang chi tiết đơn để chờ thu tiền.
     * 
     * Phản hồi trả về:
     * - Trả về kết quả JSON điều hướng cho JS tại file [public/js/backend/staff/reception/orders/create.js]
     *   để JS tự chuyển đổi trang bằng window.location.href.
     */
    public function storeOrder(Request $request)
    {

        $validated = $request->validate([
            'payment_method' => ['required', 'in:cash,momo,vnpay'],
            'note' => ['nullable', 'string', 'max:500'],
            'pickup_mode' => ['nullable', 'in:dine_in,takeaway'],
            // Khách hàng có tài khoản đã chọn qua ô tìm SĐT/tên — để trống = khách vãng lai.
            'customer_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('role', 'customer')],
            // Chỉ có ý nghĩa khi đã chọn customer_id — validate ràng buộc số dư/hạn mức thật nằm ở
            // OrderService::create() (nguồn tính toán duy nhất, POS không tự tin số liệu từ JS).
            'points_to_redeem' => ['nullable', 'integer', 'min:0'],
            // Mã khuyến mãi lễ tân nhập tay (tùy chọn) — nếu để trống, OrderService tự chọn mã tốt
            // nhất (resolveAutoPromotion) như trước. Validate hợp lệ thật nằm ở OrderService::create().
            'coupon_code' => ['nullable', 'string', 'max:50'],
        ], [
            'payment_method.required' => 'Vui lòng chọn phương thức thanh toán.',
            'payment_method.in' => 'Phương thức thanh toán không hợp lệ.',
            'pickup_mode.in' => 'Loại đơn không hợp lệ.',
            'customer_id.exists' => 'Khách hàng không hợp lệ.',
        ]);

        $customer = !empty($validated['customer_id'])
            ? User::where('role', 'customer')->find($validated['customer_id'])
            : null;

        if (!$customer && !empty($validated['points_to_redeem'])) {
            // Ném ValidationException để Laravel tự thương lượng định dạng phản hồi đúng: JSON 422 cho request AJAX
            throw ValidationException::withMessages(['points_to_redeem' => 'Không thể dùng điểm tích lũy cho khách vãng lai.']);
        }

        $payload = [ // Chuẩn bị thông tin cấu trúc dữ liệu gửi vào OrderService
            'idempotency_key' => (string) Str::uuid(), // Key chống trùng lặp dữ liệu khi nhấn đúp chuột gửi yêu cầu
            'delivery_type' => 'pickup',
            'pickup_mode' => $validated['pickup_mode'] ?? 'dine_in',
            // Luôn gửi key này (kể cả null) để OrderService gắn đúng "khách vãng lai" thay vì mặc
            // định đứng tên tài khoản lễ tân đang thao tác.
            'customer_id' => $customer?->id,
            'customer_name' => $customer->name ?? 'Khách tại quầy',
            'customer_phone' => $customer->phone ?? null,
            'points_to_redeem' => $customer ? ($validated['points_to_redeem'] ?? 0) : 0,
            'coupon_code' => $validated['coupon_code'] ?? null,
            'note' => $validated['note'] ?? null,
        ];

        $order = $this->sv_orderService->create(Auth::user(), $payload, $validated['payment_method']); // Gọi OrderService để xử lý lưu đơn hàng chính thức

        if ($validated['payment_method'] === 'momo') {
            // Chuyển sang cổng MoMo để khách quét QR thanh toán ngay tại quầy.
            $response = app(\App\Http\Controllers\Frontend\MomoController::class)->payExistingOrder($request, $order);
        } elseif ($validated['payment_method'] === 'vnpay') {
            // Chuyển sang cổng VNPay để khách thanh toán ngay tại quầy.
            $response = app(\App\Http\Controllers\Frontend\VnpayController::class)->payExistingOrder($request, $order);
        } else {
            // Tiền mặt: KHÔNG tự động đánh dấu đã thanh toán ngay ở đây nữa — lễ tân phải nhập số
            // tiền khách đưa và bấm xác nhận rõ ràng ở trang chi tiết đơn (confirmCashPayment()) sau
            // khi thực sự đã cầm tiền, tránh in hóa đơn/phiếu pha chế cho đơn chưa thu tiền thật.
            $response = redirect()->route('staff.reception.orders.show', $order->id)
                ->with('success', "Đã tạo đơn {$order->order_code}. Vui lòng xác nhận đã thu tiền mặt để hoàn tất.");
        }

        // Giao diện POS tạo đơn qua fetch (Accept: application/json) — trả URL đích để JS tự điều
        // hướng bằng window.location.href, thay vì để trình duyệt tự redirect như request thường.
        if ($request->expectsJson()) {
            if ($response instanceof \Illuminate\Http\JsonResponse) {
                return $response; // Trả về phản hồi của MoMo/VNPay nếu là JsonResponse
            }
            return response()->json(['success' => true, 'redirect_url' => $response->getTargetUrl()]); // Trả về link chuyển hướng để JS điều phối
        }

        return $response;
    }

    /**
     * Xác nhận đã thu tiền mặt tại quầy từ khách hàng.
     * Kiểm tra số tiền khách đưa (`amount_tendered`) phải lớn hơn hoặc bằng tổng giá trị đơn hàng (`final_amount`).
     * Cập nhật số tiền nhận, chuyển trạng thái đơn sang Đã thanh toán (`paid`) và sẵn sàng cho việc in hóa đơn/phiếu pha chế.
     * 
     * Phản hồi trả về:
     * - Trả về dữ liệu JSON thành công để JS xử lý thông báo tại file [public/js/backend/staff/reception/orders/show.js]
     */
    public function confirmCashPayment(Request $request, Order $order)
    {
        if ($order->payment_method !== 'cash' || $order->payment_status === 'paid') {
            return redirect()->route('staff.reception.orders.show', $order->id)
                ->with('error', 'Đơn này không cần xác nhận thanh toán tiền mặt.');
        }

        $validated = $request->validate([
            'amount_tendered' => ['required', 'numeric', 'min:0'],
        ], [
            'amount_tendered.required' => 'Vui lòng nhập số tiền khách đưa.',
            'amount_tendered.numeric' => 'Số tiền không hợp lệ.',
        ]);

        if ((float) $validated['amount_tendered'] < (float) $order->final_amount) {
            return back()->withErrors(['amount_tendered' => 'Số tiền khách đưa không đủ để thanh toán đơn hàng.']);
        }

        $order->forceFill(['amount_tendered' => $validated['amount_tendered']])->save();
        $this->sv_orderWorkflow->markPaid($order, 'CASH-' . $order->order_code, (float) $order->final_amount);

        return redirect()->route('staff.reception.orders.show', $order->id)
            ->with('success', 'Đã xác nhận thu tiền mặt thành công!');
    }

    /**
     * Tìm kiếm thông tin Khách hàng thành viên qua AJAX (theo Tên hoặc Số điện thoại).
     *
     * Phục vụ màn hình POS tại quầy để gắn thông tin khách hàng vào đơn và xem số điểm tích lũy hiện có.
     *
     */
    public function searchCustomer(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        if (mb_strlen($search) < 2) {
            return response()->json(['results' => []]);
        }

        $customers = \App\Models\User::query()->where('role', 'customer')
            ->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%");
            })
            ->limit(8)
            ->get(['id', 'name', 'phone', 'points']);

        return response()->json(['results' => $customers]);
    }
}
