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
    // Sử dụng Dependency Injection để nạp các dịch vụ xử lý
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

        // Áp dụng bộ lọc trạng thái nếu hợp lệ
        if (in_array($status, ['pending', 'confirmed', 'shipping', 'completed', 'cancelled'], true)) {
            $query->where('status', $status);
        }

        // Lấy danh sách đơn hàng mới nhất và thực hiện phân
        $orders = $query->latest()->paginate(10);

        // Chuẩn hóa lại thông tin sản phẩm trong từng chi tiết
        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $item->product_name = $item->product_name ?: $item->product?->name;
                $item->product_image = $item->product_image ?: $item->product?->image;
                $item->product_slug = $item->product?->slug;
            }
        }

        // Lấy danh sách các sản phẩm mà người dùng hiện tại đã
        $reviewedItems = Review::query()->where('user_id', Auth::id())->whereNotNull('order_id')
            ->select('order_id', 'product_id')->get();

        // Trả về view lịch sử mua hàng kèm theo các dữ liệu đã xử lý
        return view('frontend.orders.index', compact('orders', 'status', 'reviewedItems'));
    }

   
    // XỬ LÝ TẠO ĐƠN VỚI HÌNH THỨC THANH TOÁN COD
    public function store(Request $request)
    {
        // Kiểm tra tính hợp lệ của dữ liệu đầu vào khi gửi
        $validated = $request->validate([
            'address_id' => ['required', 'integer'], // Địa chỉ giao nhận hàng
            'coupon_code' => ['nullable', 'string', 'max:50'], // Mã giảm giá, nếu có
            'note' => ['nullable', 'string', 'max:500'], // Ghi chú thêm
            'idempotency_key' => ['required', 'uuid'], // Token chống gửi trùng lặp đơn hàng
            'points_to_redeem' => ['nullable', 'integer', 'min:0'], // Điểm thưởng quy đổi
            'selected_item_ids' => ['nullable', 'array'], // Danh sách các sản phẩm chọn thanh toán
            'selected_item_ids.*' => ['integer', 'min:1'],
        ]);

        // Kiểm tra xem Token thanh toán có khớp với Session hiện
        $this->assertCheckoutToken($validated['idempotency_key']);

        // Kiểm tra xem cửa hàng hiện tại có đang mở cửa và nhận
        $this->assertStoreOpen();

        // Kiểm tra cấu hình hệ thống xem phương thức COD có đang
        $codEnabled = Setting::getValue('cod_enabled', '1');
        if ($codEnabled != '1') {
            throw ValidationException::withMessages(['checkout' => 'Phương thức thanh toán COD hiện đang tạm khóa.']);
        }

        // Thực hiện tạo đơn hàng mới trong cơ sở dữ liệu qua
        $order = $this->orders->create(Auth::user(), $validated, 'cod');

        // Gửi thông báo đặt hàng thành công đến hệ thống
        $this->notifications->orderPlaced($order);

        // Xóa sạch token thanh toán và danh sách sản phẩm đã
        session()->forget('checkout_token');
        session()->forget('selected_cart_item_ids');

        // Chuyển hướng về trang lịch sử đơn hàng kèm theo thông
        return redirect()->route('orders')->with('success', "Đơn hàng {$order->order_code} đã được đặt thành công!");
    }


    // MUA LẠI
    public function reorder(Order $order)
    {
        // Đảm bảo đơn hàng này đúng là của người dùng hiện tại
        abort_unless($order->user_id === Auth::id(), 404);
        // Nạp thông tin chi tiết các món nước trong đơn cũ
        $order->load('items');
        // Sử dụng Transaction để đảm bảo tính toàn vẹn dữ liệu
        DB::transaction(function () use ($order) {
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
                $added++;
            }

            // Nếu không có bất kỳ sản phẩm nào được thêm lại
            if ($added === 0)
                throw ValidationException::withMessages(['order' => 'Các sản phẩm trong đơn này hiện không còn kinh doanh.']);
        });

        // Chuyển hướng người dùng sang trang thanh toán luôn để
        return redirect()->route('checkout')->with('success', 'Đã thêm lại các sản phẩm còn bán vào giỏ hàng.');
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

    /**
     * Trả về thông báo lỗi hủy đơn dạng redirect back kèm flash message.
     */
    private function cancelError(string $message)
    {
        return redirect()->back()->with('error', $message);
    }

    /**
     * Xác minh Token thanh toán chống spam/gửi trùng lặp
     */
    private function assertCheckoutToken(string $token): void
    {
        if (!hash_equals((string) session('checkout_token'), $token)) {
            throw ValidationException::withMessages(['checkout' => 'Phiên thanh toán đã hết hạn, vui lòng tải lại trang.']);
        }
    }

    /**
     * Kiểm tra trạng thái đóng/mở cửa và quyền nhận đơn của quán
     */
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
