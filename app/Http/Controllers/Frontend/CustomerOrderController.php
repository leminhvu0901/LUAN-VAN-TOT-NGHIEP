<?php

namespace App\Http\Controllers\Frontend;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CartItemTopping;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\Review;
use App\Models\Setting;
use App\Models\Topping;
use App\Services\NotificationService;
use App\Services\OrderService;
use App\Services\OrderWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CustomerOrderController
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly NotificationService $notifications,
    ) {
    }

    // DANH SACH ĐƠN HANG
    public function index(Request $request)
    {
        $status = $request->query('status');
        $query = Order::query()->with('items.product')->where('user_id', Auth::id())
            ->where(fn($builder) => $builder->where('payment_method', '!=', 'vnpay')
                ->orWhereNull('payment_method')->orWhere('payment_status', '!=', 'unpaid'));

        if (in_array($status, ['pending', 'confirmed', 'shipping', 'completed', 'cancelled'], true)) {
            $query->where('status', $status);
        }
        $orders = $query->latest()->paginate(10);
        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $item->product_name = $item->product_name ?: $item->product?->name;
                $item->product_image = $item->product_image ?: $item->product?->image;
                $item->product_slug = $item->product?->slug;
            }
        }
        $reviewedItems = Review::query()->where('user_id', Auth::id())->whereNotNull('order_id')
            ->select('order_id', 'product_id')->get();
        return view('frontend.orders.index', compact('orders', 'status', 'reviewedItems'));
    }

    // XỬ LÝ TẠO ĐƠN VỚI HÌNH THỨC THANH TOÁN COD
    public function store(Request $request)
    {
        $validated = $request->validate([
            'address_id' => ['required', 'integer'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'note' => ['nullable', 'string', 'max:500'],
            'idempotency_key' => ['required', 'uuid'],
            'points_to_redeem' => ['nullable', 'integer', 'min:0'],
            'selected_item_ids' => ['nullable', 'array'],
            'selected_item_ids.*' => ['integer', 'min:1'],
        ]);
        $this->assertCheckoutToken($validated['idempotency_key']);
        $this->assertStoreOpen();
        $codEnabled = Setting::getValue('cod_enabled', '1');
        if ($codEnabled != '1') {
            throw ValidationException::withMessages(['checkout' => 'Phương thức thanh toán COD hiện đang tạm khóa.']);
        }
        if ((!isset($validated['selected_item_ids']) || empty($validated['selected_item_ids'])) && session()->has('selected_cart_item_ids')) {
            $validated['selected_item_ids'] = session('selected_cart_item_ids');
        }
        $order = $this->orders->create(Auth::user(), $validated, 'cod');
        $this->notifications->orderPlaced($order);
        session()->forget('checkout_token');
        session()->forget('selected_cart_item_ids');
        session()->forget('reorder_cart_item_ids');
        return redirect()->route('orders')->with('success', "Đơn hàng {$order->order_code} đã được đặt thành công!");
    }

    // MUA LẠI
    public function reorder(Order $order)
    {
        // Đảm bảo đơn hàng này đúng là của người dùng hiện tại
        abort_unless($order->user_id === Auth::id(), 404);
        // Nạp thông tin chi tiết các món nước trong đơn cũ
        $order->load('items');

        // Dọn dẹp phiên mua lại cũ nếu có
        $prevReorderIds = session('reorder_cart_item_ids');
        if (!empty($prevReorderIds) && is_array($prevReorderIds)) {
            CartItemTopping::query()->whereIn('cart_item_id', $prevReorderIds)->delete();
            CartItem::query()->whereIn('id', $prevReorderIds)->delete();
            session()->forget('reorder_cart_item_ids');
        }

        $addedItemIds = [];
        // Sử dụng Transaction để đảm bảo tính toàn vẹn dữ liệu
        DB::transaction(function () use ($order, &$addedItemIds) {
            // Lấy giỏ hàng hiện tại hoặc tự tạo mới nếu người dùng
            $cart = Cart::query()->firstOrCreate(['user_id' => Auth::id()], ['session_id' => null]);
            $added = 0;

            // Duyệt qua từng món nước trong đơn hàng cũ
            foreach ($order->items as $oldItem) {
                // Kiểm tra xem sản phẩm này có còn kinh doanh và hoạt
                $product = Product::query()->whereKey($oldItem->product_id)->where('is_active', true)->first();
                if (!$product)
                    continue;

                $price = (float) $product->base_price;
                $sizeName = $oldItem->size_name;

                // Kiểm tra và lấy thông tin chênh lệch giá kích cỡ, Size cũ
                if ($sizeName) {
                    $size = ProductSize::query()->where('product_id', $product->id)->where('size_name', $sizeName)->first();
                    if (!$size)
                        $sizeName = null; // Bỏ size nếu kích cỡ đó không còn tồn tại
                    else
                        $price += (float) $size->price_adjustment;
                }

                // Kiểm tra và lấy thông tin các Topping cũ đi kèm nếu
                $optionNames = is_array($oldItem->options) ? $oldItem->options : [];
                $toppings = Topping::query()->join('product_toppings', 'product_toppings.topping_id', '=', 'toppings.id')
                    ->where('product_toppings.product_id', $product->id)->where('toppings.is_available', true)
                    ->whereIn('toppings.name', $optionNames)->select('toppings.*')->get();
                $price += (float) $toppings->sum('price');

                // Tạo mới sản phẩm trong giỏ hàng hiện tại
                $item = CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $product->id,
                    'size_name' => $sizeName,
                    'quantity' => min(99, max(1, (int) $oldItem->quantity)), // Giới hạn số lượng từ 1 đến 99
                    'sugar_level' => $oldItem->sugar_level,
                    'ice_level' => $oldItem->ice_level,
                    'unit_price' => $price,
                ]);

                // Thêm các topping đi kèm của sản phẩm vào giỏ hàng
                foreach ($toppings as $topping) {
                    CartItemTopping::create(['cart_item_id' => $item->id, 'topping_id' => $topping->id, 'price' => $topping->price]);
                }
                $addedItemIds[] = $item->id;
                $added++;
            }

            // Nếu không có bất kỳ sản phẩm nào được thêm lại
            if ($added === 0)
                throw ValidationException::withMessages(['order' => 'Các sản phẩm trong đơn này hiện không còn kinh doanh.']);
        });

        // Đánh dấu các ID sản phẩm được tạo từ "Mua lại" để chỉ thanh toán những món này
        session(['reorder_cart_item_ids' => $addedItemIds]);
        session(['selected_cart_item_ids' => $addedItemIds]);

        // Chuyển hướng người dùng sang trang thanh toán
        return redirect()->route('checkout')->with('success', 'Đã thêm lại các sản phẩm còn bán vào đơn thanh toán.');
    }

    // HỦY ĐƠN HÀNG
    public function cancel(Order $order, Request $request, OrderWorkflowService $workflow)
    {
        // Đảm bảo đây đúng là đơn hàng của khách hàng hiện tại
        abort_unless($order->user_id === Auth::id(), 404);

        // Chỉ cho phép hủy khi đơn hàng đang ở trạng thái Chờ
        if ($order->status !== 'pending') {
            return $this->cancelError('Chỉ có thể hủy đơn hàng khi đang ở trạng thái Chờ xác nhận.');
        }
        // Lấy lý do hủy đơn từ người dùng nhập vào
        $reason = $request->input('cancel_reason', 'Khách hàng tự hủy đơn hàng.');
        if (mb_strlen(trim($reason)) < 5) {
            $reason = 'Khách hàng tự hủy đơn hàng.';
        }
        // HOÀN TIỀN
        if ($order->payment_status === 'paid' && $order->payment_method === 'vnpay') {
            return $this->refundAndCancelForCustomer($order, $request, $reason);// TỰ ĐỘNG HOÀN TIỆN
        }

        try {
            // Chuyển trạng thái đơn sang 'cancelled' qua workflow
            $workflow->transition($order, 'cancelled', $reason);

            return redirect()->back()->with('success', "Đơn hàng #{$order->order_code} đã được hủy thành công!");
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        } catch (\Exception $e) {
            return $this->cancelError('Có lỗi xảy ra khi hủy đơn hàng: ' . $e->getMessage());
        }
    }

    // TỰ ĐỘNG HOÀN TIỆN
    private function refundAndCancelForCustomer(Order $order, Request $request, string $reason)
    {
        // Đảm bảo đơn có chứa mã giao dịch gốc để hoàn tiền
        if (!$order->payment_transaction_id) {
            Log::error('Customer self-cancel refund skipped: missing payment_transaction_id', [
                'orderId' => $order->order_code,
            ]);
            return $this->cancelError('Không tìm thấy mã giao dịch gốc để hoàn tiền. Vui lòng liên hệ cửa hàng để được hỗ trợ.');
        }

        $gatewayLabel = 'VNPay';

        // Gọi hàm API hoàn tiền của đúng cổng thanh toán đã đặt mua
        $result = app(VnpayController::class)->requestRefund($order);

        // Kiểm tra kết quả gọi API hoàn tiền từ cổng thanh toán
        if (!$result['success']) {
            Log::error('Customer self-cancel refund failed', [
                'orderId' => $order->order_code,
                'message' => $result['message'],
            ]);
            return $this->cancelError("Hoàn tiền {$gatewayLabel} thất bại: {$result['message']}. Đơn hàng chưa bị hủy, vui lòng thử lại hoặc liên hệ cửa hàng.");
        }

        try {
            // Khi cổng thanh toán xác nhận hoàn tiền thành công ->
            app(OrderWorkflowService::class)->refundAndCancel($order, $result['transId'], $reason);
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        }

        return redirect()->back()->with('success', "Đơn hàng #{$order->order_code} đã được hủy và hoàn tiền {$gatewayLabel} thành công!");
    }

    // Trả về thông báo lỗi hủy đơn dạng redirect back kèm flash message.
    private function cancelError(string $message)
    {
        return redirect()->back()->with('error', $message);
    }

    // Xác minh Token thanh toán chống spam/gửi trùng lặp
    private function assertCheckoutToken(string $token): void
    {
        if (!hash_equals((string) session('checkout_token'), $token)) {
            throw ValidationException::withMessages(['checkout' => 'Phiên thanh toán đã hết hạn, vui lòng tải lại trang.']);
        }
    }

    // Kiểm tra trạng thái đóng mở cửa và quyền nhận đơn của quán
    private function assertStoreOpen(): void
    {
        // Kiểm tra cấu hình bật/tắt nhận đơn hàng của quán
        $receiveEnabled = (bool) Setting::getValue('orders_enabled', true);
        if (!$receiveEnabled) {
            throw ValidationException::withMessages(['checkout' => 'Cửa hàng hiện đang tạm ngưng nhận đơn hàng.']);
        }

        // Kiểm tra xem giờ hiện hành có nằm trong khung giờ
        $open = Setting::getValue('store_open_time', '08:00');
        $close = Setting::getValue('store_close_time', '22:00');
        $nowStr = now('Asia/Ho_Chi_Minh')->format('H:i');

        $isOpen = false;
        if ($open < $close) {
            $isOpen = ($nowStr >= $open && $nowStr <= $close);
        } else { // Khung giờ hoạt động qua đêm, Ví dụ: mở 20:00 tối và đóng 02:00 sáng hôm sau
            $isOpen = ($nowStr >= $open || $nowStr <= $close);
        }
        if (!$isOpen) {
            throw ValidationException::withMessages(['checkout' => "Cửa hàng chỉ nhận đơn từ {$open} đến {$close}."]);
        }
    }
}
