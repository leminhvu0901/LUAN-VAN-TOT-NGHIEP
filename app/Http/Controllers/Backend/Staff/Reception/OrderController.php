<?php

namespace App\Http\Controllers\Backend\Staff\Reception;

use App\Http\Controllers\Frontend\VnpayController;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Setting;
use App\Models\User;
use App\Services\CartPricingService;
use App\Services\NotificationService;
use App\Services\OrderService;
use App\Services\OrderWorkflowService;
use App\Services\PromotionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderController
{
    public function __construct(
        private readonly OrderWorkflowService $sv_orderWorkflow,
        private readonly OrderService $sv_orderService,
        private readonly CartPricingService $sv_cartPricing,
        private readonly NotificationService $sv_notifications,
        private readonly PromotionService $sv_promotions,
    ) {
    }

    // Hiển thị danh sách đơn hàng cho Lễ tân.
    public function index(Request $request)
    {
        // Dọn đơn online "chờ thanh toán" bị treo quá lâu mỗi
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

        // Lọc theo tìm kiếm: Mã đơn, Tên khách hàng, Số điện thoại hoặc Tên món/sản phẩm trong đơn
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $cleanSearch = ltrim($search, '#');
            $query->where(function ($q) use ($search, $cleanSearch) {
                $q->where('order_code', 'like', "%{$search}%")
                    ->orWhere('order_code', 'like', "%{$cleanSearch}%")
                    ->orWhere('id', 'like', "%{$cleanSearch}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhereHas('items', function ($itemQuery) use ($search) {
                        $itemQuery->where('product_name', 'like', "%{$search}%")
                            ->orWhereHas('product', function ($pQuery) use ($search) {
                                $pQuery->where('name', 'like', "%{$search}%");
                            });
                    });
            });
        }

        $collection = $query->get(); // Lấy toàn bộ đơn hàng thỏa mãn điều kiện lọc để xử lý Collection nâng cao

        $page = LengthAwarePaginator::resolveCurrentPage(); // Đọc trang hiện tại từ URL phục vụ phân trang Collection thủ công
        $paginator = new LengthAwarePaginator(
            $collection->slice(($page - 1) * 10, 10)->values(), // Cắt lấy dữ liệu của trang hiện tại, 10 đơn/trang
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
                // Đơn giao hàng đã xác nhận nhưng chưa gán shipper ->
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

    // Xem chi tiết 1 đơn hàng cụ thể.
    public function show(Order $order)
    {
        // Thực hiện Left Join lấy sản phẩm gốc phòng trường hợp
        $items = OrderItem::query()->leftJoin('products', 'order_items.product_id', '=', 'products.id')
            ->where('order_items.order_id', $order->id)->select('order_items.*')
            ->selectRaw('COALESCE(order_items.product_name, products.name) as product_name')
            ->selectRaw('COALESCE(order_items.product_image, products.image) as product_image')->get();

        $order->loadMissing('deliveryStaff'); // Nạp thông tin tài khoản nhân viên vận chuyển, nếu có

        // Chỉ lấy danh sách tài khoản shipper đang hoạt động nếu
        $deliveryStaffs = $order->delivery_type === 'delivery'
            ? User::where('role', 'staff')->where('staff_type', 'delivery')->where('is_active', true)->orderBy('name')->get()
            : collect();

        $storeInfo = [ // Lấy cấu hình thông tin quán trong bảng settings để hiển thị khi in hóa đơn POS
            'name' => Setting::getValue('store_name', 'Happy Tea'),
            'phone' => Setting::getValue('store_phone', ''),
            'address' => Setting::getValue('store_address', ''),
        ];

        $largeOrderThreshold = (float) Setting::getValue('large_order_threshold', 500000);

        return view('backend.staff.reception.orders.show', compact('order', 'items', 'deliveryStaffs', 'storeInfo', 'largeOrderThreshold'));
    }

    // Cập nhật trạng thái đơn hàng
    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,confirmed,shipping,completed,cancelled'],
            'cancel_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $largeOrderThreshold = (float) Setting::getValue('large_order_threshold', 500000);

        // Chặn xác nhận nếu đơn hàng đang chờ duyệt hoặc vượt ngưỡng giá trị lớn
        if ($validated['status'] === 'confirmed') {
            if ($order->needs_admin_approval) {
                throw ValidationException::withMessages([
                    'status' => 'Đơn hàng giá trị lớn đang chờ Quản trị viên phê duyệt.',
                ]);
            }

            if ((float) $order->final_amount >= $largeOrderThreshold) {
                throw ValidationException::withMessages([
                    'status' => 'Đơn hàng có giá trị từ ' . number_format($largeOrderThreshold, 0, ',', '.') . 'đ cần gửi Admin phê duyệt trước khi xác nhận.',
                ]);
            }
        }

        // Kiểm tra điều kiện bảo vệ: lễ tân chỉ được xử lý đơn
        $isDeliveryOrder = $order->delivery_type !== 'pickup';
        if ($isDeliveryOrder && ($order->status === 'shipping' || in_array($validated['status'], ['shipping', 'completed'], true))) {
            throw ValidationException::withMessages([
                'status' => 'Đơn giao hàng đang trên đường đi (hoặc cần chuyển sang bước giao hàng) — chỉ nhân viên giao hàng được xử lý sau khi được phân công.',
            ]);
        }

        //// Chuyển trạng thái đơn hàng và xử lý các điều kiện nghiệp vụ đi kèm
        $this->sv_orderWorkflow->transition($order, $validated['status'], $validated['cancel_reason'] ?? null); // Gọi workflow service thực thi cập nhật trạng thái đơn hàng

        return back()->with('success', 'Đã cập nhật trạng thái đơn hàng!');
    }

    // Lễ tân gửi yêu cầu Admin phê duyệt đơn hàng giá trị lớn.
    public function requestAdminApproval(Request $request, Order $order)
    {
        $largeOrderThreshold = (float) Setting::getValue('large_order_threshold', 500000);

        if ((float) $order->total_amount < $largeOrderThreshold && (float) $order->final_amount < $largeOrderThreshold) {
            throw ValidationException::withMessages([
                'order' => 'Chỉ các đơn hàng có tổng giá trị từ ' . number_format($largeOrderThreshold, 0, ',', '.') . 'đ mới cần gửi yêu cầu duyệt Admin.'
            ]);
        }

        if ($order->needs_admin_approval) {
            return redirect()->back()->with('warning', 'Đơn hàng này đã được gửi yêu cầu duyệt trước đó.');
        }

        try {
            $order->update(['needs_admin_approval' => true]);

            // Gửi Mail cho Admin
            $this->sv_notifications->notifyAdminForApproval($order);
        } catch (\Throwable $e) {
            Log::error('requestAdminApproval: failed to send email', ['error' => $e->getMessage()]);
        }

        return redirect()->back()->with('success', 'Đã gửi yêu cầu phê duyệt đơn hàng đến Quản trị viên!');
    }

    // Duyệt đơn hàng trực tiếp tại quầy dành cho Lễ tân 
    public function approveDirectly(Request $request, Order $order)
    {
        $order->update([
            'needs_admin_approval' => false,
            'status' => 'confirmed',
            'assigned_at' => now(),
        ]);

        return back()->with('success', 'Đã phê duyệt đơn hàng thành công!');
    }

    // Phân công Nhân viên vận chuyển, Shipper cho đơn giao
    public function assignDelivery(Request $request, Order $order)
    {
        $validated = $request->validate([
            'delivery_staff_id' => ['required', 'integer'],
        ], [
            'delivery_staff_id.required' => 'Vui lòng chọn nhân viên giao hàng.',
        ]);
        // Phân công nhân viên vận chuyển cho đơn hàng đã xác nhận
        $this->sv_orderWorkflow->assignDeliveryStaff(
            $order,
            (int) $validated['delivery_staff_id'],
            Auth::id()
        );

        return back()->with('success', 'Đã phân công nhân viên giao hàng!');
    }

    // Hiển thị màn hình Tạo đơn tại quầy, POS cho Lễ tân.
    public function createOrder()
    {
        // Vẫn lấy cả sản phẩm hết hàng, đưa xuống cuối danh sách
        $products = Product::with([
            'category',
            'sizes',
            'toppings' => function ($query) {
                $query->where('is_available', true);
            }
        ])->orderByDesc('is_active')->orderBy('name')->get();

        $categories = Category::query()->where('is_active', true)->orderBy('name')->get();
        $vnpayEnabled = (bool) Setting::getValue('vnpay_enabled', false);

        $selectedCustomer = old('customer_id')
            ? User::where('role', 'customer')->find(old('customer_id'))
            : null;

        // Token chống tạo trùng đơn, phải sinh ở đây và nhúng vào form thì bấm đúp mới gửi lên cùng một giá trị
        $posToken = (string) Str::uuid();
        session(['pos_order_token' => $posToken]);

        return view('backend.staff.reception.orders.create', compact('products', 'categories', 'vnpayEnabled', 'selectedCustomer', 'posToken'));
    }

    // Xem trước tổng tiền đơn hàng tại quầy
    public function previewTotal(Request $request)
    {
        $cart = Cart::query()->where('user_id', Auth::id())->first();
        $items = $cart ? CartItem::query()->where('cart_id', $cart->id)->get() : collect();

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
                'gifts' => [],
                'available_promotions' => [],
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate');
        }

        $items = $this->sv_cartPricing->pricedItems($cart);/// TÍNH TOÁN GIÁ CHUẨN NHẤT TỪNG SẢN PHẨM , trả về giá chuẩn từng sp
        $subtotal = $this->sv_cartPricing->subtotal($items);////hàm tính tổng số tiền của giỏ hàng trước khi áp dụng mã giảm giá.
        $totalQuantity = (int) $items->sum('quantity');

        $customerId = $request->query('customer_id');
        $orderOwner = $customerId ? User::where('role', 'customer')->find($customerId) : null;

        $couponCode = trim((string) $request->query('coupon_code', ''));
        $couponError = null;

        // 'gifts' chỉ khác rỗng khi mã được áp là mã combo có
        $gifts = [];
        $promotion = null;
        $discount = 0;

        // Không nhập mã thì KHÔNG có khuyến mãi nào. Lễ tân phải
        if ($couponCode !== '') {
            try {
                // //Xem trước số tiền giảm khi lễ tân nhập thủ công một
                $preview = $this->sv_orderService->previewManualCoupon($couponCode, $items, $orderOwner, $subtotal, 'pickup', $totalQuantity);
                $promotion = $preview['promotion'];
                $discount = $preview['discount'];
                $gifts = $preview['gifts'] ?? [];
            } catch (ValidationException $e) {
                $couponError = collect($e->errors())->flatten()->first();
            }
        }

        $pointsToRedeem = (int) $request->query('points_to_redeem', 0);
        ////Xem trước số tiền giảm giá khi quy đổi điểm tích lũy của khách hàng thành viên.
        $pointsPreview = $this->sv_orderService->previewPointsDiscount($pointsToRedeem, $orderOwner, $subtotal);
        $pointsDiscount = $pointsPreview['discount'];
        $pointsError = $pointsPreview['error'];

        $totalDiscount = min($subtotal, $discount + $pointsDiscount);

        return response()->json([
            'subtotal' => $subtotal,
            'discount' => $discount,
            'shipping_fee' => 0,
            'promotion_code' => $promotion?->code,
            'promotion_label' => $promotion
                ? ($promotion->type === 'percent' ? "Khuyến mãi -{$promotion->value}%" : 'Khuyến mãi') . " ({$promotion->code})"
                : null,
            'points_discount' => $pointsDiscount,
            'points_error' => $pointsError,
            'final_amount' => max(0, $subtotal - $totalDiscount),
            'coupon_error' => $couponError,
            'gifts' => collect($gifts)->map(fn($g) => [
                'gift_product_name' => $g['gift_product']->name,
                'quantity' => $g['granted_quantity'],
            ])->values(),
            // Danh sách mã lễ tân bấm chọn được với giỏ hiện tại
            'available_promotions' => $this->applicablePromotionsForPos($items, $subtotal, $totalQuantity, $orderOwner),
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    // Các mã khuyến mãi mà giỏ tại quầy hiện tại dùng được
    private function applicablePromotionsForPos($items, float $subtotal, int $totalQuantity, ?User $orderOwner): \Illuminate\Support\Collection
    {
        $promotions = Promotion::query()
            ->where('is_active', 1)
            ->whereIn('scope', ['order', 'product', 'category'])
            ->whereNotNull('code')
            ->where(function ($q) {
                $q->whereIn('applies_to', ['all', 'pickup'])->orWhereNull('applies_to');
            })
            ->where(function ($q) {
                $q->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit');
            })
            ->with(['products', 'categories'])
            ->get()
            // checkValidity lo phần thời gian/hạng thành viên/đơn
            ->filter(fn($promo) => $promo->checkValidity($orderOwner, $subtotal, 'pickup', $totalQuantity)['valid'] === true)
            // Bỏ mã mà giỏ không có sản phẩm nào thuộc phạm vi áp dụng
            ->filter(fn($promo) => $this->sv_promotions->eligibleSubtotal($promo, $items) > 0);

        // Mã combo: thêm riêng vì còn phải xét giỏ có đủ tổ hợp
        $combos = $this->sv_promotions->applicableCombos($items, 'pickup')
            ->filter(fn($promo) => $promo->checkValidity($orderOwner, $subtotal, 'pickup', $totalQuantity)['valid'] === true);

        return $promotions->concat($combos)->values()->map(fn($promo) => [
            'code' => $promo->code,
            'label' => $this->promotionShortLabel($promo),
        ]);
    }

    // Nhãn ngắn mô tả mã để hiện cạnh chip cho lễ tân biết
    private function promotionShortLabel(Promotion $promo): string
    {
        if ($promo->scope === 'combo') {
            $combo = $promo->combo;
            $parts = [];
            if ($combo?->hasDiscount()) {
                $parts[] = $combo->discount_type === 'percent'
                    ? 'giảm ' . (float) $combo->discount_value . '%'
                    : 'giảm ' . number_format($combo->discount_value, 0, ',', '.') . 'đ';
            }
            if ($combo?->hasGift() && $combo->giftProduct) {
                $parts[] = 'tặng ' . $combo->gift_quantity . ' ' . $combo->giftProduct->name;
            }
            return 'Combo: ' . implode(', ', $parts);
        }

        if ($promo->type === 'percent') {
            $label = 'Giảm ' . (float) $promo->value . '%';
            if ($promo->max_discount_amount) {
                $label .= ' (tối đa ' . number_format($promo->max_discount_amount, 0, ',', '.') . 'đ)';
            }
            return $label;
        }

        return 'Giảm ' . number_format($promo->value, 0, ',', '.') . 'đ';
    }

    // Lưu và tạo Đơn hàng mới tại quầy, POS.
    public function storeOrder(Request $request)
    {

        $validated = $request->validate([
            'payment_method' => ['required', 'in:cash,vnpay'],
            'note' => ['nullable', 'string', 'max:500'],
            'pickup_mode' => ['nullable', 'in:dine_in,takeaway'],
            'idempotency_key' => ['required', 'uuid'],
            'customer_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('role', 'customer')],
            'points_to_redeem' => ['nullable', 'integer', 'min:0'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
        ], [
            'payment_method.required' => 'Vui lòng chọn phương thức thanh toán.',
            'payment_method.in' => 'Phương thức thanh toán không hợp lệ.',
            'pickup_mode.in' => 'Loại đơn không hợp lệ.',
            'customer_id.exists' => 'Khách hàng không hợp lệ.',
            'idempotency_key.required' => 'Phiên tạo đơn không hợp lệ, vui lòng tải lại trang.',
            'idempotency_key.uuid' => 'Phiên tạo đơn không hợp lệ, vui lòng tải lại trang.',
        ]);

        // Token phải khớp với token đã phát khi mở màn hình tạo đơn, chặn gửi lại form cũ sau khi đã đặt xong
        if (!hash_equals((string) session('pos_order_token'), $validated['idempotency_key'])) {
            throw ValidationException::withMessages([
                'idempotency_key' => 'Đơn này đã được tạo hoặc phiên đã hết hạn, vui lòng tải lại trang.',
            ]);
        }

        $customer = !empty($validated['customer_id'])
            ? User::where('role', 'customer')->find($validated['customer_id'])
            : null;

        if (!$customer && !empty($validated['points_to_redeem'])) {
            // Ném ValidationException để Laravel tự thương lượng
            throw ValidationException::withMessages(['points_to_redeem' => 'Không thể dùng điểm tích lũy cho khách vãng lai.']);
        }

        $payload = [ // Chuẩn bị thông tin cấu trúc dữ liệu gửi vào OrderService
            'idempotency_key' => $validated['idempotency_key'], // Key chống trùng lặp dữ liệu khi nhấn đúp chuột gửi yêu cầu
            'delivery_type' => 'pickup',
            'pickup_mode' => $validated['pickup_mode'] ?? 'dine_in',
            // Luôn gửi key này, kể cả null để OrderService gắn đúng
            'customer_id' => $customer?->id,
            'customer_name' => $customer->name ?? 'Khách tại quầy',
            'customer_phone' => $customer->phone ?? null,
            'points_to_redeem' => $customer ? ($validated['points_to_redeem'] ?? 0) : 0,
            'coupon_code' => $validated['coupon_code'] ?? null,
            'note' => $validated['note'] ?? null,
        ];

        $paymentMethod = $validated['payment_method'];
        $order = $this->sv_orderService->create(Auth::user(), $payload, $paymentMethod); // Gọi OrderService để xử lý lưu đơn hàng chính thức

        // Dùng xong thì huỷ token, lần gửi lại form cũ sẽ bị chặn ngay ở bước kiểm tra phía trên
        session()->forget('pos_order_token');

        if ($paymentMethod === 'vnpay') {
            // Lễ tân bấm "Thanh toán VNPay" cho một đơn tại quầy đã
            $response = app(VnpayController::class)->payExistingOrder($request, $order);
        } else {
            $response = redirect()->route('staff.reception.orders.show', $order->id)
                ->with('success', "Đã tạo đơn {$order->order_code}. Vui lòng xác nhận đã thu tiền mặt để hoàn tất.");
        }
        // Giao diện POS tạo đơn qua fetch Accept:
        if ($request->expectsJson()) {
            if ($response instanceof JsonResponse) {
                return $response; // Trả về phản hồi của VNPay nếu là JsonResponse
            }
            return response()->json(['success' => true, 'redirect_url' => $response->getTargetUrl()]); // Trả về link chuyển hướng để JS điều phối
        }

        return $response;
    }

    // Xác nhận đã thu tiền mặt tại quầy từ khách hàng.
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

    // Tìm kiếm thông tin Khách hàng thành viên qua AJAX
    public function searchCustomer(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        if (strlen($search) < 2) {
            return response()->json(['customers' => []]);
        }

        $customers = User::query()->where('role', 'customer')
            ->where(function ($q) use ($search) {
                $q->where('phone', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->select('id', 'name', 'phone', 'email', 'points', 'membership_level')
            ->limit(10)
            ->get();

        return response()->json(['results' => $customers]);
    }
}
