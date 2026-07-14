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
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerOrderController
{
    public function __construct(private readonly OrderService $orders) {}

    public function index(Request $request)
    {
        $status = $request->query('status');
        $query = Order::query()->with('items.product')->where('user_id', Auth::id())
            ->where(fn ($builder) => $builder->where('payment_method', '!=', 'momo')
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
            'address_id' => ['required', 'integer'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'note' => ['nullable', 'string', 'max:500'],
            'idempotency_key' => ['required', 'uuid'],
        ]);
        $this->assertCheckoutToken($validated['idempotency_key']);
        $this->assertStoreOpen();
        $order = $this->orders->create(Auth::user(), $validated, 'cod');
        session()->forget('checkout_token');
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
                if (!$product) continue;
                $price = (float) $product->base_price;
                $sizeName = $oldItem->size_name;
                if ($sizeName) {
                    $size = ProductSize::query()->where('product_id', $product->id)->where('size_name', $sizeName)->first();
                    if (!$size) $sizeName = null;
                    else $price += (float) $size->price_adjustment;
                }
                $optionNames = is_array($oldItem->options) ? $oldItem->options : [];
                $toppings = Topping::query()->join('product_toppings', 'product_toppings.topping_id', '=', 'toppings.id')
                    ->where('product_toppings.product_id', $product->id)->where('toppings.is_available', true)
                    ->whereIn('toppings.name', $optionNames)->select('toppings.*')->get();
                $price += (float) $toppings->sum('price');
                $item = CartItem::create([
                    'cart_id' => $cart->id, 'product_id' => $product->id, 'size_name' => $sizeName,
                    'quantity' => min(99, max(1, (int) $oldItem->quantity)),
                    'sugar_level' => $oldItem->sugar_level, 'ice_level' => $oldItem->ice_level, 'unit_price' => $price,
                ]);
                foreach ($toppings as $topping) {
                    CartItemTopping::create(['cart_item_id' => $item->id, 'topping_id' => $topping->id, 'price' => $topping->price]);
                }
                $added++;
            }
            if ($added === 0) throw ValidationException::withMessages(['order' => 'Các sản phẩm trong đơn này hiện không còn kinh doanh.']);
        });

        return redirect()->route('checkout')->with('success', 'Đã thêm lại các sản phẩm còn bán vào giỏ hàng.');
    }

    private function assertCheckoutToken(string $token): void
    {
        if (!hash_equals((string) session('checkout_token'), $token)) {
            throw ValidationException::withMessages(['checkout' => 'Phiên thanh toán đã hết hạn, vui lòng tải lại trang.']);
        }
    }

    private function assertStoreOpen(): void
    {
        $time = now('Asia/Ho_Chi_Minh')->format('H:i:s');
        if ($time < '07:00:00' || $time >= '23:00:00') {
            throw ValidationException::withMessages(['checkout' => 'Cửa hàng chỉ nhận đơn từ 07:00 đến 23:00.']);
        }
    }
}
