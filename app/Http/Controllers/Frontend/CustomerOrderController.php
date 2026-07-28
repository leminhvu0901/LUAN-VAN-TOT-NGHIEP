<?php

namespace App\Http\Controllers\Frontend;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CartItemTopping;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\Review;
use App\Models\Topping;
use App\Services\NotificationService;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerOrderController
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly NotificationService $notifications,
    ) {
    }

    public function index(Request $request)
    {
        $status = $request->query('status');
        $query = Order::query()->with('items.product')->where('user_id', Auth::id())
            ->where(fn($builder) => $builder->where('payment_method', '!=', 'momo')
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'address_id'          => ['required', 'integer'],
            'coupon_code'         => ['nullable', 'string', 'max:50'],
            'note'                => ['nullable', 'string', 'max:500'],
            'idempotency_key'     => ['required', 'uuid'],
            'points_to_redeem'    => ['nullable', 'integer', 'min:0'],
            'selected_item_ids'   => ['nullable', 'array'],
            'selected_item_ids.*' => ['integer', 'min:1'],
        ]);
        $this->assertCheckoutToken($validated['idempotency_key']);
        $this->assertStoreOpen();

        $codEnabled = \App\Models\Setting::getValue('cod_enabled', '1');
        if ($codEnabled != '1') {
            throw ValidationException::withMessages(['checkout' => 'Phương thức thanh toán COD hiện đang tạm khóa.']);
        }

        $order = $this->orders->create(Auth::user(), $validated, 'cod');
        $this->notifications->orderPlaced($order);
        session()->forget('checkout_token');
        // Xóa session lọc sản phẩm đã chọn sau khi đặt hàng thành công
        session()->forget('selected_cart_item_ids');

        // Trang checkout submit qua fetch (xem checkout.js) -> trả URL đích để JS tự điều hướng, tránh
        // tải lại cả trang một cách đột ngột ngay ở bước cuối cùng của luồng mua hàng.
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'redirect_url' => route('orders'),
            ]);
        }

        return redirect()->route('orders')->with('success', "Đơn hàng {$order->order_code} đã được đặt thành công!");
    }

    public function reorder(Order $order)
    {
        abort_unless($order->user_id === Auth::id(), 404);
        $order->load('items');

        DB::transaction(function () use ($order) {
            $cart = Cart::query()->firstOrCreate(['user_id' => Auth::id()], ['session_id' => null]);
            $added = 0;
            foreach ($order->items as $oldItem) {
                $product = Product::query()->whereKey($oldItem->product_id)->where('is_active', true)->first();
                if (!$product)
                    continue;
                $price = (float) $product->base_price;
                $sizeName = $oldItem->size_name;
                if ($sizeName) {
                    $size = ProductSize::query()->where('product_id', $product->id)->where('size_name', $sizeName)->first();
                    if (!$size)
                        $sizeName = null;
                    else
                        $price += (float) $size->price_adjustment;
                }
                $optionNames = is_array($oldItem->options) ? $oldItem->options : [];
                $toppings = Topping::query()->join('product_toppings', 'product_toppings.topping_id', '=', 'toppings.id')
                    ->where('product_toppings.product_id', $product->id)->where('toppings.is_available', true)
                    ->whereIn('toppings.name', $optionNames)->select('toppings.*')->get();
                $price += (float) $toppings->sum('price');
                $item = CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $product->id,
                    'size_name' => $sizeName,
                    'quantity' => min(99, max(1, (int) $oldItem->quantity)),
                    'sugar_level' => $oldItem->sugar_level,
                    'ice_level' => $oldItem->ice_level,
                    'unit_price' => $price,
                ]);
                foreach ($toppings as $topping) {
                    CartItemTopping::create(['cart_item_id' => $item->id, 'topping_id' => $topping->id, 'price' => $topping->price]);
                }
                $added++;
            }
            if ($added === 0)
                throw ValidationException::withMessages(['order' => 'Các sản phẩm trong đơn này hiện không còn kinh doanh.']);
        });

        return redirect()->route('checkout')->with('success', 'Đã thêm lại các sản phẩm còn bán vào giỏ hàng.');
    }

    public function cancel(Order $order, Request $request, \App\Services\OrderWorkflowService $workflow)
    {
        abort_unless($order->user_id === Auth::id(), 404);

        if ($order->status !== 'pending') {
            return $this->cancelError($request, 'Chỉ có thể hủy đơn hàng khi đang ở trạng thái Chờ xác nhận.');
        }

        $reason = $request->input('cancel_reason', 'Khách hàng tự hủy đơn hàng.');
        if (mb_strlen(trim($reason)) < 5) {
            $reason = 'Khách hàng tự hủy đơn hàng.';
        }

        try {
            $workflow->transition($order, 'cancelled', $reason);
            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => "Đơn hàng #{$order->order_code} đã được hủy thành công!"]);
            }
            return redirect()->back()->with('success', "Đơn hàng #{$order->order_code} đã được hủy thành công!");
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'errors' => $e->errors()], 422);
            }
            return redirect()->back()->withErrors($e->errors());
        } catch (\Exception $e) {
            return $this->cancelError($request, 'Có lỗi xảy ra khi hủy đơn hàng: ' . $e->getMessage());
        }
    }

    /**
     * Trả lỗi hủy đơn đúng định dạng theo kiểu request: JSON 422 cho fetch (nút "Hủy đơn" gửi qua
     * AJAX, xem orders.js) để JS hiện lỗi tại chỗ không cần tải lại trang; redirect-back cổ điển cho
     * request thường.
     */
    private function cancelError(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message], 422);
        }
        return redirect()->back()->with('error', $message);
    }

    private function assertCheckoutToken(string $token): void
    {
        if (!hash_equals((string) session('checkout_token'), $token)) {
            throw ValidationException::withMessages(['checkout' => 'Phiên thanh toán đã hết hạn, vui lòng tải lại trang.']);
        }
    }

    private function assertStoreOpen(): void
    {
        // 1. Kiểm tra tắt nhận đơn hàng
        $receiveEnabled = (bool) \App\Models\Setting::getValue('orders_enabled', true);
        if (!$receiveEnabled) {
            throw ValidationException::withMessages(['checkout' => 'Cửa hàng hiện đang tạm ngưng nhận đơn hàng.']);
        }

        // 2. Kiểm tra giờ đóng/mở cửa
        $open = \App\Models\Setting::getValue('store_open_time', '08:00');
        $close = \App\Models\Setting::getValue('store_close_time', '22:00');
        $nowStr = now('Asia/Ho_Chi_Minh')->format('H:i');

        $isOpen = false;
        if ($open < $close) {
            $isOpen = ($nowStr >= $open && $nowStr <= $close);
        } else { // Qua đêm
            $isOpen = ($nowStr >= $open || $nowStr <= $close);
        }
        if (!$isOpen) {
            throw ValidationException::withMessages(['checkout' => "Cửa hàng chỉ nhận đơn từ {$open} đến {$close}."]);
        }
    }
}
