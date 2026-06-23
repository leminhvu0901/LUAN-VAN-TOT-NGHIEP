<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController
{
    public function index(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $userId = Auth::id();
        $status = $request->query('status');

        $query = \App\Models\Order::query()
            ->where('user_id', $userId)
            ->where(function ($q) {
                $q->where('payment_method', '!=', 'momo')
                    ->orWhereNull('payment_method')
                    ->orWhere('payment_status', '!=', 'unpaid');
            }); // Chỉ ẩn đơn MoMo chưa thanh toán (bị hủy/lỗi), vẫn hiện đơn COD (luôn là unpaid lúc mới đặt)

        if ($status && in_array($status, ['pending', 'confirmed', 'shipping', 'completed', 'cancelled'])) {
            $query->where('status', $status);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(10);

        // Map items to each order
        foreach ($orders as $order) {
            $order->items = \App\Models\OrderItem::query()
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->where('order_items.order_id', $order->id)
                ->select(
                    'order_items.*',
                    'products.name as product_name',
                    'products.image as product_image',
                    'products.slug as product_slug'
                )
                ->get();
        }

        // Get existing reviews by this user to check which items are already reviewed
        $reviewedItems = \App\Models\Review::query()
            ->where('user_id', $userId)
            ->whereNotNull('order_id')
            ->select('order_id', 'product_id')
            ->get();

        return view('frontend.orders.index', compact('orders', 'status', 'reviewedItems'));
    }

    public function store(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $userId = Auth::id();

        // 0. Enforce store operating hours (07:00 - 19:00)
        $now = now()->timezone('Asia/Ho_Chi_Minh');
        $timeString = $now->format('H:i:s');
        if ($timeString < '07:00:00' || $timeString >= '19:00:00') {
            return redirect()->back()->with('error', 'Cửa hàng chỉ cho phép đặt hàng từ 07:00 đến 19:00. Hiện tại cửa hàng đã đóng cửa.')->withInput();
        }

        // 1. Validate request
        $request->validate([
            'address_id' => 'required',
            'payment_method' => 'required|in:cod,momo',
            'coupon_code' => 'nullable|string',
            'note' => 'nullable|string|max:500',
            'distance_km' => 'required|numeric|min:0|max:10',
            'weather_fee' => 'nullable|numeric|min:0'
        ], [
            'distance_km.max' => 'Rất tiếc, địa chỉ của bạn quá xa (vượt quá 10km) nên cửa hàng không thể giao hàng.'
        ]);

        // 2. Get the address
        $address = \App\Models\UserAddress::query()
            ->where('id', $request->input('address_id'))
            ->where('user_id', $userId)
            ->first();

        if (!$address) {
            return redirect()->back()->with('error', 'Địa chỉ giao hàng không hợp lệ.');
        }

        // 3. Get cart and items
        $cart = \App\Models\Cart::query()
            ->where('user_id', $userId)
            ->first();

        if (!$cart) {
            return redirect()->back()->with('error', 'Giỏ hàng của bạn đang trống.');
        }

        $cartItems = \App\Models\CartItem::query()
            ->where('cart_id', $cart->id)
            ->get();

        if ($cartItems->isEmpty()) {
            return redirect()->back()->with('error', 'Giỏ hàng của bạn đang trống.');
        }

        // Calculate totals
        $subtotal = 0;
        $itemIds = $cartItems->pluck('id');
        $cartToppings = \App\Models\CartItemTopping::query()
            ->join('toppings', 'cart_item_toppings.topping_id', '=', 'toppings.id')
            ->whereIn('cart_item_toppings.cart_item_id', $itemIds)
            ->select('cart_item_toppings.cart_item_id', 'toppings.name', 'toppings.price')
            ->get();

        foreach ($cartItems as $item) {
            $subtotal += $item->unit_price * $item->quantity;
        }

        // Calculate shipping fee: 3000 VND per km
        // Free shipping for orders >= 150,000 VND
        $distanceKm = floatval($request->input('distance_km'));
        $shippingFee = $subtotal >= 150000 ? 0 : round($distanceKm * 3000);

        // Weather fee from request (only applied when shipping is not free)
        $weatherFee = $subtotal >= 150000 ? 0 : floatval($request->input('weather_fee', 0));
        $peakHourFee = 0;

        $estimatedTime = now()->addMinutes(45);

        // Coupon discount
        $discountAmount = 0;
        $couponCode = null;
        $promotionId = null;

        $inputCoupon = trim($request->input('coupon_code'));
        if (!empty($inputCoupon)) {
            $coupon = \App\Models\Promotion::query()->where('code', strtoupper($inputCoupon))->first();
            if ($coupon && $coupon->is_active && (!$coupon->usage_limit || $coupon->used_count < $coupon->usage_limit)) {
                // Check if not expired
                $isValidDate = true;
                if ($coupon->start_at && now() < $coupon->start_at)
                    $isValidDate = false;
                if ($coupon->end_at && now() > $coupon->end_at)
                    $isValidDate = false;

                if ($isValidDate && (!$coupon->min_order_amount || $subtotal >= $coupon->min_order_amount)) {
                    // Check if user already used
                    $hasUsed = \App\Models\Order::query()
                        ->where('promotion_id', $coupon->id)
                        ->where('user_id', $userId)
                        ->where('status', '!=', 'cancelled')
                        ->exists();

                    if (!$hasUsed) {
                        $couponCode = $coupon->code;
                        $promotionId = $coupon->id;
                        if ($coupon->type === 'percent') {
                            $discountAmount = round($subtotal * ($coupon->value / 100));
                            if ($coupon->max_discount_amount && $discountAmount > $coupon->max_discount_amount) {
                                $discountAmount = $coupon->max_discount_amount;
                            }
                        } else {
                            $discountAmount = $coupon->value;
                        }

                        if ($discountAmount > $subtotal) {
                            $discountAmount = $subtotal;
                        }
                    }
                }
            }
        }

        $finalAmount = max(0, $subtotal + $shippingFee + $weatherFee + $peakHourFee - $discountAmount);

        // Order code generation
        $orderCode = 'HPY-' . strtoupper(bin2hex(random_bytes(4)));

        // Assemble address string
        $fullAddress = $address->specific_address . ', ' . $address->ward . ', ' . $address->district . ', ' . $address->province;

        DB::beginTransaction();
        try {
            // Insert into orders table
            $orderId = \App\Models\Order::query()->insertGetId([
                'order_code' => $orderCode,
                'user_id' => $userId,
                'customer_name' => $address->fullname,
                'customer_phone' => $address->phone,
                'delivery_address' => $fullAddress,
                'total_amount' => $subtotal,
                'discount_amount' => $discountAmount,
                'final_amount' => $finalAmount,
                'payment_status' => 'unpaid',
                'payment_method' => 'cod',
                'status' => 'pending',
                'coupon_code' => $couponCode,
                'promotion_id' => $promotionId,
                'delivery_type' => 'delivery',
                'estimated_time' => $estimatedTime,
                'distance_km' => $distanceKm,
                'weather_fee' => $weatherFee,
                'peak_hour_fee' => $peakHourFee,
                'shipping_fee' => $shippingFee,
                'customer_note' => $request->input('note'),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Insert into order_items table
            foreach ($cartItems as $item) {
                // Get toppings for this item
                $toppings = $cartToppings->where('cart_item_id', $item->id)->pluck('name')->toArray();
                $toppingsList = json_encode($toppings, JSON_UNESCAPED_UNICODE);

                \App\Models\OrderItem::query()->insert([
                    'order_id' => $orderId,
                    'product_id' => $item->product_id,
                    'size_name' => $item->size_name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'sugar_level' => $item->sugar_level,
                    'ice_level' => $item->ice_level,
                    'options' => $toppingsList,
                    'note' => null
                ]);
            }

            // Clear cart items and toppings
            \App\Models\CartItemTopping::query()->whereIn('cart_item_id', $itemIds)->delete();
            \App\Models\CartItem::query()->where('cart_id', $cart->id)->delete();

            // Update promotion used count
            if ($promotionId) {
                \App\Models\Promotion::query()->where('id', $promotionId)->increment('used_count');
            }

            DB::commit();

            // Redirect to orders route with success message
            return redirect()->route('orders')->with('success', 'Đơn hàng ' . $orderCode . ' đã được đặt thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Có lỗi xảy ra khi tạo đơn hàng: ' . $e->getMessage())->withInput();
        }
    }
}
