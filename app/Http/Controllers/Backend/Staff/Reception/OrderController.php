<?php

namespace App\Http\Controllers\Backend\Staff\Reception;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\CartPricingService;
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
        private readonly OrderWorkflowService $orderWorkflow,
        private readonly OrderService $orderService,
        private readonly CartPricingService $cartPricing,
    ) {
    }

    /**
     * Hiển thị danh sách đơn hàng cho Lễ tân.
     *
     * Hỗ trợ lọc theo trạng thái, khoảng thời gian, sắp xếp ngày tạo, tìm kiếm đa năng theo mã đơn/tên/SĐT khách,
     * phân trang tùy chỉnh và phản hồi dạng AJAX (cập nhật bảng & thống kê không cần tải lại trang).
     * Tự động dọn dẹp các đơn thanh toán MoMo bị quá hạn (stale pending) mỗi lần tải danh sách.
     *
     * @param  Request  $request
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Dọn đơn MoMo "chờ thanh toán" bị treo quá lâu mỗi lần lễ tân mở danh sách — không cần cron
        // để thấy hiệu quả ngay, cron (nếu có cấu hình) chỉ để dọn cả khi không ai mở trang.
        $this->orderWorkflow->cancelStalePendingPayments();

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
                'delivery_type' => $order->delivery_type,
                // Đơn giao hàng đã xác nhận nhưng chưa gán shipper -> cần nổi bật để lễ tân biết mà phân công,
                // tránh đơn "kẹt" âm thầm ở trạng thái đã xác nhận không ai xử lý tiếp.
                'needs_delivery_assignment' => $order->delivery_type === 'delivery'
                    && $order->status === 'confirmed'
                    && !$order->delivery_staff_id,
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
                'table_html' => view('backend.staff.reception.orders.partials.table', compact('orders', 'paginator', 'status'))->with('currentStatus', $status)->render(),
                'stats_html' => view('backend.staff.reception.orders.partials.stats', compact('stats'))->render(),
            ]);
        }

        return view('backend.staff.reception.orders.index', compact('stats', 'orders', 'paginator'))->with('currentStatus', $status);
    }

    /**
     * Xem chi tiết 1 đơn hàng cụ thể.
     *
     * Nạp thông tin danh sách sản phẩm (kèm tên/ảnh fallback từ bảng products), thông tin nhân viên giao hàng,
     * danh sách Shipper khả dụng (để phân công nếu đơn cần giao) và thông tin cửa hàng để chuẩn bị in hóa đơn.
     *
     * @param  Order  $order
     * @return \Illuminate\View\View
     */
    public function show(Order $order)
    {
        $items = OrderItem::query()->leftJoin('products', 'order_items.product_id', '=', 'products.id')
            ->where('order_items.order_id', $order->id)->select('order_items.*')
            ->selectRaw('COALESCE(order_items.product_name, products.name) as product_name')
            ->selectRaw('COALESCE(order_items.product_image, products.image) as product_image')->get();

        $order->loadMissing('deliveryStaff');

        // Chỉ cần danh sách nhân viên vận chuyển khi đơn giao hàng đã xác nhận và chưa được phân công.
        $availableDeliveryStaff = ($order->delivery_type === 'delivery' && $order->status === 'confirmed' && !$order->delivery_staff_id)
            ? \App\Models\User::where('role', 'staff')->where('staff_type', 'delivery')->where('is_active', true)->orderBy('name')->get()
            : collect();

        // Thông tin cửa hàng để in trên hóa đơn khách hàng.
        $storeInfo = [
            'name' => \App\Models\Setting::getValue('store_name', 'Happy Tea'),
            'phone' => \App\Models\Setting::getValue('store_phone', ''),
            'address' => \App\Models\Setting::getValue('store_address', ''),
        ];

        return view('backend.staff.reception.orders.show', compact('order', 'items', 'availableDeliveryStaff', 'storeInfo'));
    }

    /**
     * Cập nhật trạng thái đơn hàng (Xác nhận, Hủy đơn,...).
     *
     * Ràng buộc nghiệp vụ: Đối với đơn giao hàng (delivery), Lễ tân chỉ được chuyển trạng thái TRƯỚC khi đơn sang
     * bước giao hàng ('shipping'). Khi đơn đã ở trạng thái 'shipping', quyền cập nhật (hoàn thành/giao thất bại)
     * thuộc về Nhân viên vận chuyển để đảm bảo đúng quy trình kiểm soát.
     *
     * @param  Request  $request
     * @param  Order  $order
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,confirmed,shipping,completed,cancelled'],
            'cancel_reason' => ['nullable', 'string', 'max:500'],
        ]);

        // Đơn giao hàng (không phải khách tại quầy): lễ tân chỉ được xác nhận/hủy đơn TRƯỚC khi đơn
        // vào tay nhân viên vận chuyển. Một khi đơn đã "đang giao", MỌI thay đổi (hoàn thành/hủy/giao
        // thất bại) đều thuộc về nhân viên vận chuyển qua StaffDeliveryOrderController — kể cả hủy,
        // để tránh lễ tân bấm tắt qua đúng quy trình markDeliveryFailed (có audit lý do/thời điểm).
        $isDeliveryOrder = $order->delivery_type !== 'pickup';
        if ($isDeliveryOrder && ($order->status === 'shipping' || in_array($validated['status'], ['shipping', 'completed'], true))) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'status' => 'Đơn giao hàng đang trên đường đi (hoặc cần chuyển sang bước giao hàng) — chỉ nhân viên giao hàng được xử lý sau khi được phân công.',
            ]);
        }

        $this->orderWorkflow->transition($order, $validated['status'], $validated['cancel_reason'] ?? null);

        return back()->with('success', 'Đã cập nhật trạng thái đơn hàng!');
    }

    /**
     * Phân công Nhân viên vận chuyển (Shipper) cho đơn giao hàng đã xác nhận.
     *
     * @param  Request  $request
     * @param  Order  $order
     * @return \Illuminate\Http\RedirectResponse
     */
    public function assignDelivery(Request $request, Order $order)
    {
        $validated = $request->validate([
            'delivery_staff_id' => ['required', 'integer'],
        ], [
            'delivery_staff_id.required' => 'Vui lòng chọn nhân viên giao hàng.',
        ]);

        $this->orderWorkflow->assignDeliveryStaff(
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
        $products = Product::with([
            'category',
            'sizes',
            'toppings' => function ($query) {
                $query->where('is_available', true);
            }
        ])->where('is_active', true)->orderBy('name')->get();

        $categories = \App\Models\Category::query()->where('is_active', true)->orderBy('name')->get();
        // POS chỉ nên cho chọn MoMo nếu admin đã bật kênh này trong Cài đặt (tiền mặt luôn khả dụng
        // vì không phụ thuộc cổng thanh toán ngoài).
        $momoEnabled = (bool) \App\Models\Setting::getValue('momo_enabled', false);

        return view('backend.staff.reception.orders.create', compact('products', 'categories', 'momoEnabled'));
    }

    /**
     * Xem trước tổng tiền đơn hàng tại quầy (POS) qua AJAX trước khi tạo đơn.
     *
     * Tính toán tạm tính, kiểm tra áp dụng mã giảm giá nhập tay hoặc tự động chọn ưu đãi tốt nhất,
     * tính số tiền được giảm và tổng thanh toán cuối cùng. Vì là đơn tại quầy (pickup) nên phí giao hàng = 0.
     *
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

        $pricedItems = $this->cartPricing->pricedItems($cart);
        $subtotal = $this->cartPricing->subtotal($pricedItems);
        $totalQuantity = (int) $pricedItems->sum('quantity');

        $customerId = $request->query('customer_id');
        $orderOwner = $customerId ? User::where('role', 'customer')->find($customerId) : null;

        $couponCode = trim((string) $request->query('coupon_code', ''));
        $couponError = null;

        if ($couponCode !== '') {
            try {
                $preview = $this->orderService->previewManualCoupon($couponCode, $pricedItems, $orderOwner, $subtotal, 'pickup', $totalQuantity);
                $promotion = $preview['promotion'];
                $discount = $preview['discount'];
            } catch (\Illuminate\Validation\ValidationException $e) {
                $promotion = null;
                $discount = 0;
                $couponError = collect($e->errors())->flatten()->first();
            }
        } else {
            $preview = $this->orderService->previewAutoPromotion($pricedItems, $subtotal, $totalQuantity);
            $promotion = $preview['promotion'];
            $discount = $preview['discount'];
        }

        // Xem trước giảm giá theo hạng thành viên — dùng ĐÚNG hàm OrderService::create() dùng thật,
        // để tổng tiền hiển thị trước khi tạo đơn luôn khớp với đơn thật sau khi tạo.
        $membershipDiscount = $this->orderService->membershipDiscount($orderOwner, $subtotal);

        // Xem trước số tiền giảm từ điểm tích lũy (nếu lễ tân đã chọn khách hàng và nhập số điểm) —
        // dùng chung logic/hạn mức với lúc tạo đơn thật (OrderService::previewPointsDiscount()) để
        // không bao giờ lệch giữa preview và kết quả tạo đơn.
        $pointsToRedeem = (int) $request->query('points_to_redeem', 0);
        $pointsPreview = $this->orderService->previewPointsDiscount($pointsToRedeem, $orderOwner, $subtotal);
        $pointsDiscount = $pointsPreview['discount'];
        $pointsError = $pointsPreview['error'];

        // Xem trước quà tặng Mua X tặng Y (độc lập với mã giảm giá ở trên) để lễ tân biết trước khi tạo đơn.
        $gifts = app(\App\Services\PromotionService::class)->resolveGifts($pricedItems, 'pickup');

        $totalDiscount = min($subtotal, $discount + $membershipDiscount + $pointsDiscount);

        return response()->json([
            'subtotal' => $subtotal,
            'discount' => $discount,
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
            'gifts' => collect($gifts)->map(fn ($g) => [
                'gift_product_name' => $g['gift_product']->name,
                'quantity' => $g['granted_quantity'],
                'stock_limited' => $g['stock_limited'],
            ])->values(),
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    /**
     * Lưu và tạo Đơn hàng mới tại quầy (POS).
     *
     * Tiếp nhận phương thức thanh toán (tiền mặt / MoMo), loại nhận hàng (uống tại chỗ / mang về),
     * thông tin khách hàng (khách thành viên hoặc vãng lai), điểm thưởng áp dụng và mã giảm giá.
     * Nếu thanh toán MoMo -> chuyển hướng sang cổng MoMo; Nếu tiền mặt -> chuyển sang trang chi tiết đơn để chờ thu tiền.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function storeOrder(Request $request)
    {
        // POS chỉ tạo đơn TẠI QUẦY/MANG ĐI — khách uống/nhận trực tiếp, không cần địa chỉ giao hàng
        // và không có COD (khái niệm "trả khi nhận" chỉ áp dụng cho đơn giao hàng, không áp dụng khi
        // khách đang đứng ngay tại quầy). Đơn giao hàng vẫn tồn tại trong hệ thống (khách tự đặt qua
        // trang khách hàng) nhưng KHÔNG được tạo từ màn hình POS này.
        $validated = $request->validate([
            'payment_method' => ['required', 'in:cash,momo'],
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
            // Ném ValidationException (thay vì back()->withErrors() thủ công) để Laravel tự thương
            // lượng định dạng phản hồi đúng theo Accept header: JSON 422 cho request AJAX (giao diện
            // POS gửi lên qua fetch — không mất trạng thái khách hàng/điểm đã nhập vì trang không tải
            // lại), redirect-back cho request thường (vd. test cũ dùng $this->post() không phải JSON).
            throw ValidationException::withMessages(['points_to_redeem' => 'Không thể dùng điểm tích lũy cho khách vãng lai.']);
        }

        $payload = [
            'idempotency_key' => (string) Str::uuid(),
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

        $order = $this->orderService->create(Auth::user(), $payload, $validated['payment_method']);

        if ($validated['payment_method'] === 'momo') {
            // Chuyển sang cổng MoMo để khách quét QR thanh toán ngay tại quầy.
            $response = app(\App\Http\Controllers\Frontend\MomoController::class)->payExistingOrder($request, $order);
        } else {
            // Tiền mặt: KHÔNG tự động đánh dấu đã thanh toán ngay ở đây nữa — lễ tân phải nhập số
            // tiền khách đưa và bấm xác nhận rõ ràng ở trang chi tiết đơn (confirmCashPayment()) sau
            // khi thực sự đã cầm tiền, tránh in hóa đơn/phiếu pha chế cho đơn chưa thu tiền thật.
            $response = redirect()->route('staff.reception.orders.show', $order->id)
                ->with('success', "Đã tạo đơn {$order->order_code}. Vui lòng xác nhận đã thu tiền mặt để hoàn tất.");
        }

        // Giao diện POS tạo đơn qua fetch (Accept: application/json) — trả URL đích để JS tự điều
        // hướng bằng window.location.href, thay vì để trình duyệt tự redirect như request thường.
        // Cờ flash 'success'/'error' (được gắn ở trên hoặc trong payExistingOrder()) vẫn còn nguyên
        // trong session và sẽ hiển thị đúng khi trang đích tải lại bình thường.
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'redirect_url' => $response->getTargetUrl()]);
        }

        return $response;
    }

    /**
     * Xác nhận đã thu tiền mặt tại quầy từ khách hàng.
     *
     * Kiểm tra số tiền khách đưa (`amount_tendered`) phải lớn hơn hoặc bằng tổng giá trị đơn hàng (`final_amount`).
     * Cập nhật số tiền nhận, chuyển trạng thái đơn sang Đã thanh toán (`paid`) và sẵn sàng cho việc in hóa đơn/phiếu pha chế.
     *
     * @param  Request  $request
     * @param  Order  $order
     * @return \Illuminate\Http\RedirectResponse
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
        $this->orderWorkflow->markPaid($order, 'CASH-' . $order->order_code, (float) $order->final_amount);

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
