<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class CartController
{
    private function getCartIdentifier()
    {
        if (Auth::check()) {
            return ['user_id' => Auth::id()];
        }

        $sessionId = Session::getId();
        return ['session_id' => $sessionId];
    }

    private function getOrCreateCart()
    {
        $identifier = $this->getCartIdentifier();

        $cart = \App\Models\Cart::query();
        if (isset($identifier['user_id'])) {
            $cart->where('user_id', $identifier['user_id']);
        } else {
            $cart->where('session_id', $identifier['session_id']);
        }
        $cart = $cart->first();

        if (!$cart) {
            $cartId = \App\Models\Cart::query()->insertGetId(array_merge($identifier, [
                'created_at' => now(),
                'updated_at' => now()
            ]));
            return \App\Models\Cart::query()->where('id', $cartId)->first();
        }

        return $cart;
    }

    public function getCartData()
    {
        $cart = $this->getOrCreateCart();

        $items = \App\Models\CartItem::query()
            ->join('products', 'cart_items.product_id', '=', 'products.id')
            ->where('cart_items.cart_id', $cart->id)
            ->select('cart_items.*', 'products.name', 'products.image')
            ->orderBy('cart_items.created_at', 'desc')
            ->get();

        $itemIds = $items->pluck('id');
        $itemToppings = \App\Models\CartItemTopping::query()
            ->join('toppings', 'cart_item_toppings.topping_id', '=', 'toppings.id')
            ->whereIn('cart_item_toppings.cart_item_id', $itemIds)
            ->select('cart_item_toppings.cart_item_id', 'toppings.name')
            ->get();

        $total = 0;
        foreach ($items as $item) {
            $item->toppings = $itemToppings->where('cart_item_id', $item->id)->values();
            $total += $item->unit_price * $item->quantity;
        }

        return response()->json([
            'success' => true,
            'items' => $items,
            'count' => count($items),
            'total' => $total,
            'formatted_total' => number_format($total, 0, ',', '.') . 'đ'
        ]);
    }

    public function add(Request $request)
    {
        $productId = $request->input('product_id');
        $quantity = $request->input('quantity', 1);
        $sizeName = $request->input('size_name');
        if (empty($sizeName)) {
            $defaultSize = \App\Models\ProductSize::query()
                ->where('product_id', $productId)
                ->orderBy('price_adjustment', 'asc')
                ->first();
            if ($defaultSize) {
                $sizeName = $defaultSize->size_name;
            }
        }

        $sugarLevel = $request->input('sugar_level');
        if ($sugarLevel === null) {
            $sugarLevel = '100';
        }

        $iceLevel = $request->input('ice_level');
        if ($iceLevel === null) {
            $iceLevel = 'normal';
        }

        $toppingIds = $request->input('toppings', []);

        if (!$productId) {
            return response()->json(['success' => false, 'message' => 'Product ID is missing']);
        }

        $product = \App\Models\Product::query()->where('id', $productId)->first();
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found']);
        }

        // Kiểm tra sản phẩm còn hàng không
        if (!$product->is_active) {
            return response()->json(['success' => false, 'message' => 'Sản phẩm này đã hết hàng, không thể thêm vào giỏ hàng.']);
        }

        // Tính giá dựa theo size
        $unitPrice = $product->base_price;
        if ($sizeName) {
            $sizeRecord = \App\Models\ProductSize::query()
                ->where('product_id', $productId)
                ->where('size_name', $sizeName)
                ->first();
            if ($sizeRecord) {
                $unitPrice += $sizeRecord->price_adjustment;
            }
        }

        // Tính giá dựa theo topping
        $tops = [];
        if (!empty($toppingIds)) {
            $tops = \App\Models\Topping::query()->whereIn('id', $toppingIds)->get();
            foreach ($tops as $t) {
                $unitPrice += $t->price;
            }
        }

        $cart = $this->getOrCreateCart();

        // Tìm các items cùng product_id, size, sugar, ice
        $potentialItems = \App\Models\CartItem::query()
            ->where('cart_id', $cart->id)
            ->where('product_id', $productId)
            ->where('size_name', $sizeName)
            ->where('sugar_level', $sugarLevel)
            ->where('ice_level', $iceLevel)
            ->get();

        $existingItem = null;
        $reqTops = $toppingIds;
        sort($reqTops);

        foreach ($potentialItems as $pi) {
            $piToppings = \App\Models\CartItemTopping::query()
                ->where('cart_item_id', $pi->id)
                ->pluck('topping_id')
                ->toArray();

            sort($piToppings);

            if ($piToppings == $reqTops) {
                $existingItem = $pi;
                break;
            }
        }

        if ($existingItem) {
            \App\Models\CartItem::query()
                ->where('id', $existingItem->id)
                ->update([
                    'quantity' => $existingItem->quantity + $quantity,
                    'updated_at' => now()
                ]);
        } else {
            $newItemId = \App\Models\CartItem::query()->insertGetId([
                'cart_id' => $cart->id,
                'product_id' => $productId,
                'size_name' => $sizeName,
                'sugar_level' => $sugarLevel,
                'ice_level' => $iceLevel,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            if (!empty($tops)) {
                $inserts = [];
                foreach ($tops as $t) {
                    $inserts[] = [
                        'cart_item_id' => $newItemId,
                        'topping_id' => $t->id,
                        'price' => $t->price
                    ];
                }
                \App\Models\CartItemTopping::query()->insert($inserts);
            }
        }

        return $this->getCartData();
    }

    public function remove(Request $request)
    {
        $itemId = $request->input('item_id');

        if ($itemId) {
            \App\Models\CartItem::query()->where('id', $itemId)->delete();
        }

        return $this->getCartData();
    }

    public function update(Request $request)
    {
        $itemId = $request->input('item_id');
        $quantity = $request->input('quantity');

        if ($itemId && $quantity > 0) {
            \App\Models\CartItem::query()
                ->where('id', $itemId)
                ->update([
                    'quantity' => $quantity,
                    'updated_at' => now()
                ]);
        }

        return $this->getCartData();
    }

    public function addAll(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Bạn cần đăng nhập để sử dụng tính năng này']);
        }

        $favorites = \App\Models\Favorite::query()
            ->join('products', 'favorites.product_id', '=', 'products.id')
            ->where('favorites.user_id', Auth::id())
            ->where('products.is_active', 1) // Chỉ thêm sản phẩm còn hàng
            ->select('products.id', 'products.base_price')
            ->get();

        if ($favorites->isEmpty()) {
            return $this->getCartData();
        }

        $cart = $this->getOrCreateCart();

        foreach ($favorites as $product) {
            $defaultSize = \App\Models\ProductSize::query()
                ->where('product_id', $product->id)
                ->orderBy('price_adjustment', 'asc')
                ->first();

            $sizeName = $defaultSize ? $defaultSize->size_name : null;
            $unitPrice = $product->base_price + ($defaultSize ? $defaultSize->price_adjustment : 0);
            $sugarLevel = '100';
            $iceLevel = 'normal';

            $existingItem = \App\Models\CartItem::query()
                ->where('cart_id', $cart->id)
                ->where('product_id', $product->id)
                ->where('size_name', $sizeName)
                ->where('sugar_level', $sugarLevel)
                ->where('ice_level', $iceLevel)
                ->first();

            // We also need to make sure this existing item doesn't have toppings,
            // but for simplicity we'll just check the basic fields.
            $hasNoToppings = true;
            if ($existingItem) {
                $hasToppings = \App\Models\CartItemTopping::query()->where('cart_item_id', $existingItem->id)->exists();
                if ($hasToppings) {
                    $hasNoToppings = false;
                    $existingItem = null; // force create new item
                }
            }

            if ($existingItem) {
                \App\Models\CartItem::query()
                    ->where('id', $existingItem->id)
                    ->update([
                        'quantity' => $existingItem->quantity + 1,
                        'updated_at' => now()
                    ]);
            } else {
                \App\Models\CartItem::query()->insert([
                    'cart_id' => $cart->id,
                    'product_id' => $product->id,
                    'size_name' => $sizeName,
                    'sugar_level' => $sugarLevel,
                    'ice_level' => $iceLevel,
                    'quantity' => 1,
                    'unit_price' => $unitPrice,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        return $this->getCartData();
    }

    public function checkout()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $userId = Auth::id();

        // Fetch user addresses
        $addresses = \App\Models\UserAddress::query()
            ->where('user_id', $userId)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Fetch user's cart
        $cart = \App\Models\Cart::query()
            ->where('user_id', $userId)
            ->first();

        $items = collect();
        $subtotal = 0;

        if ($cart) {
            $items = \App\Models\CartItem::query()
                ->join('products', 'cart_items.product_id', '=', 'products.id')
                ->where('cart_items.cart_id', $cart->id)
                ->select('cart_items.*', 'products.name', 'products.image')
                ->orderBy('cart_items.created_at', 'desc')
                ->get();

            if ($items->isNotEmpty()) {
                $itemIds = $items->pluck('id');
                $itemToppings = \App\Models\CartItemTopping::query()
                    ->join('toppings', 'cart_item_toppings.topping_id', '=', 'toppings.id')
                    ->whereIn('cart_item_toppings.cart_item_id', $itemIds)
                    ->select('cart_item_toppings.cart_item_id', 'toppings.name', 'toppings.price')
                    ->get();

                foreach ($items as $item) {
                    $item->toppings = $itemToppings->where('cart_item_id', $item->id)->values();
                    $subtotal += $item->unit_price * $item->quantity;
                }
            }
        }

        // If cart is empty, redirect back to products or home with a warning
        if ($items->isEmpty()) {
            return redirect('/')->with('warning', 'Giỏ hàng của bạn đang trống.');
        }

        $freeShipThreshold = 150000;
        $user = Auth::user();
        if ($user) {
            switch ($user->membership_level) {
                case 'silver':
                    $freeShipThreshold = 120000;
                    break;
                case 'gold':
                    $freeShipThreshold = 90000;
                    break;
                case 'diamond':
                    $freeShipThreshold = 0;
                    break;
            }
        }

        $now = now()->timezone('Asia/Ho_Chi_Minh');
        $timeString = $now->format('H:i:s');
        $isClosed = ($timeString < '07:00:00' || $timeString >= '23:00:00');

        return view('frontend.orders.checkout', compact('items', 'subtotal', 'addresses', 'isClosed', 'freeShipThreshold'));
    }

    public function calculateDistance(Request $request)
    {
        $addressId = $request->query('address_id');
        $userId = Auth::id();

        $address = \App\Models\UserAddress::query()
            ->where('id', $addressId)
            ->where('user_id', $userId)
            ->first();

        if (!$address) {
            return response()->json(['success' => false, 'message' => 'Địa chỉ không hợp lệ'], 400);
        }

        $destAddress = $address->specific_address . ', ' . $address->ward . ', ' . $address->district . ', ' . $address->province;
        $lat = isset($address->latitude) ? $address->latitude : null;
        $lon = isset($address->longitude) ? $address->longitude : null;

        // Fallback to geocoding address string via Nominatim if coordinates are missing
        if (empty($lat) || empty($lon)) {
            try {
                $client = new \GuzzleHttp\Client();
                $geocodeUrl = "https://nominatim.openstreetmap.org/search?q=" . urlencode($destAddress) . "&format=json&limit=1";
                $geoRes = $client->get($geocodeUrl, [
                    'headers' => [
                        'User-Agent' => 'CoffeeDeliveryApp/1.0 (contact@example.com)'
                    ],
                    'verify' => false
                ]);
                $geoData = json_decode($geoRes->getBody()->getContents(), true);
                if (!empty($geoData) && isset($geoData[0]['lat']) && isset($geoData[0]['lon'])) {
                    $lat = floatval($geoData[0]['lat']);
                    $lon = floatval($geoData[0]['lon']);

                    // Save coordinates back to database to cache it
                    \App\Models\UserAddress::query()
                        ->where('id', $addressId)
                        ->update([
                            'latitude' => $lat,
                            'longitude' => $lon,
                            'updated_at' => now()
                        ]);
                }
            } catch (\Exception $e) {
                // Ignore and keep coordinates empty
            }
        }

        $orsKey = env('OPENROUTE_SERVICE_API_KEY');

        // Check if we have coordinates and ORS API key
        if (!empty($lat) && !empty($lon) && !empty($orsKey)) {
            try {
                // Shop coordinates: 180 Cao Lỗ, Quận 8 is lat 10.73809, lon 106.67812
                $shopCoords = [106.67812, 10.73809]; // [lon, lat]
                $destCoords = [floatval($lon), floatval($lat)];

                $client = new \GuzzleHttp\Client();
                $response = $client->post('https://api.openrouteservice.org/v2/directions/driving-car', [
                    'headers' => [
                        'Authorization' => $orsKey,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'coordinates' => [
                            $shopCoords,
                            $destCoords
                        ]
                    ],
                    'verify' => false
                ]);

                $data = json_decode($response->getBody()->getContents(), true);
                if (isset($data['routes'][0]['summary']['distance'])) {
                    $distanceMeters = $data['routes'][0]['summary']['distance'];
                    $distanceKm = round($distanceMeters / 1000, 1);
                    return response()->json([
                        'success' => true,
                        'distance_km' => $distanceKm,
                        'is_mock' => false
                    ]);
                }
            } catch (\Exception $e) {
                // Fall back below if ORS call fails
            }
        }

        // Fallback mock distance based on District relative to shop at Quận 8
        $district = mb_strtolower($address->district);
        $distance = 3.5; // default
        if (str_contains($district, '8')) {
            $distance = 1.5;
        } elseif (str_contains($district, '5')) {
            $distance = 2.8;
        } elseif (str_contains($district, '10')) {
            $distance = 4.5;
        } elseif (str_contains($district, '1') || str_contains($district, '4')) {
            $distance = 5.2;
        } elseif (str_contains($district, '7')) {
            $distance = 4.8;
        } elseif (str_contains($district, '3')) {
            $distance = 5.8;
        } elseif (str_contains($district, 'bình thạnh') || str_contains($district, 'binh thanh')) {
            $distance = 7.5;
        }

        return response()->json([
            'success' => true,
            'distance_km' => $distance,
            'is_mock' => true,
            'message' => 'Sử dụng khoảng cách mô phỏng dự phòng.'
        ]);
    }

    public function validateCoupon(Request $request)
    {
        $code = strtoupper(trim($request->input('coupon_code')));
        $subtotal = floatval($request->input('subtotal', 0));
        $userId = Auth::id();

        $coupon = \App\Models\Promotion::query()->where('code', $code)->first();

        if (!$coupon) {
            return response()->json(['valid' => false, 'message' => 'Mã giảm giá không tồn tại.']);
        }

        if (!$coupon->is_active) {
            return response()->json(['valid' => false, 'message' => 'Mã giảm giá đã ngừng hoạt động.']);
        }

        if ($coupon->end_at && now() > $coupon->end_at) {
            return response()->json(['valid' => false, 'message' => 'Mã giảm giá đã hết hạn.']);
        }

        if ($coupon->start_at && now() < $coupon->start_at) {
            return response()->json(['valid' => false, 'message' => 'Mã giảm giá chưa đến thời gian áp dụng.']);
        }

        if ($coupon->min_order_amount && $subtotal < $coupon->min_order_amount) {
            return response()->json(['valid' => false, 'message' => 'Đơn hàng chưa đạt giá trị tối thiểu ' . number_format($coupon->min_order_amount, 0, ',', '.') . 'đ để dùng mã này.']);
        }

        if ($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit) {
            return response()->json(['valid' => false, 'message' => 'Mã giảm giá đã hết lượt sử dụng.']);
        }

        // Check if user already used this coupon
        if ($userId) {
            $hasUsed = \App\Models\Order::query()
                ->where('promotion_id', $coupon->id)
                ->where('user_id', $userId)
                ->where('status', '!=', 'cancelled')
                ->exists();

            if ($hasUsed) {
                return response()->json(['valid' => false, 'message' => 'Bạn đã sử dụng mã giảm giá này rồi.']);
            }
        }

        // Calculate discount
        $discountAmount = 0;
        if ($coupon->type === 'percent') {
            $discountAmount = round($subtotal * ($coupon->value / 100));
            // Cap at max discount amount if set
            if ($coupon->max_discount_amount && $discountAmount > $coupon->max_discount_amount) {
                $discountAmount = $coupon->max_discount_amount;
            }
        } else {
            $discountAmount = $coupon->value;
        }

        // Ensure discount doesn't exceed subtotal
        if ($discountAmount > $subtotal) {
            $discountAmount = $subtotal;
        }

        return response()->json([
            'valid' => true,
            'message' => 'Áp dụng thành công mã giảm giá ' . $code . '!',
            'discount_amount' => $discountAmount,
            'coupon_code' => $code,
            'discount_value' => $coupon->value,
            'discount_type' => $coupon->type,
            'max_discount_amount' => $coupon->max_discount_amount
        ]);
    }

    public function calculateWeatherFee(Request $request)
    {
        $addressId = $request->query('address_id');
        $distanceKm = floatval($request->query('distance_km', 0));
        $subtotal = floatval($request->query('subtotal', 0));
        
        $freeShipThreshold = 150000;
        $user = Auth::user();
        if ($user) {
            switch ($user->membership_level) {
                case 'silver':
                    $freeShipThreshold = 120000;
                    break;
                case 'gold':
                    $freeShipThreshold = 90000;
                    break;
                case 'diamond':
                    $freeShipThreshold = 0;
                    break;
            }
        }

        // Nếu đơn hàng >= freeShipThreshold thì miễn phí ship => phụ thu thời tiết cũng = 0
        $baseShipping = $subtotal >= $freeShipThreshold ? 0 : round($distanceKm * 3000);
        $userId = Auth::id();

        $address = \App\Models\UserAddress::query()
            ->where('id', $addressId)
            ->where('user_id', $userId)
            ->first();

        if (!$address) {
            return response()->json(['success' => false, 'message' => 'Địa chỉ không hợp lệ', 'fee' => 0], 400);
        }

        $lat = isset($address->latitude) ? $address->latitude : null;
        $lon = isset($address->longitude) ? $address->longitude : null;

        // Nếu không có tọa độ, không thể tính phí thời tiết, trả về 0
        if (empty($lat) || empty($lon)) {
            return response()->json([
                'success' => true,
                'fee' => 0,
                'condition' => 'Bình thường',
                'message' => 'Không có tọa độ để kiểm tra thời tiết.'
            ]);
        }

        try {
            $client = new \GuzzleHttp\Client();
            $url = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lon}&current=weather_code";

            $response = $client->get($url, [
                'verify' => false,
                'timeout' => 5
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (isset($data['current']['weather_code'])) {
                $code = $data['current']['weather_code'];
                $fee = 0;
                $condition = 'Bình thường';

                // WMO Weather interpretation codes
                // Drizzle (Mưa phùn): 51, 53, 55
                // Slight/Moderate Rain + Showers (Mưa nhỏ/vừa): 51, 53, 55, 61, 63, 80, 81
                // Heavy Rain + Violent Showers (Mưa to): 65, 66, 67, 82
                // Thunderstorm (Giông bão): 95, 96, 99
                // Snow (Tuyết - hiếm gặp VN): 71, 73, 75, 77, 85, 86

                if (in_array($code, [51, 53, 55, 61, 63, 80, 81])) {
                    // Mưa nhỏ / mưa phùn / mưa rào nhẹ -> +5%
                    $fee = round($baseShipping * 0.05);
                    $condition = 'Mưa nhỏ';
                } elseif (in_array($code, [65, 66, 67, 82])) {
                    // Mưa to / mưa rào mạnh -> +10%
                    $fee = round($baseShipping * 0.10);
                    $condition = 'Mưa to';
                } elseif (in_array($code, [95, 96, 99])) {
                    // Giông bão -> +15%
                    $fee = round($baseShipping * 0.15);
                    $condition = 'Giông bão';
                } elseif (in_array($code, [71, 73, 75, 77, 85, 86])) {
                    // Tuyết (ít xảy ra ở VN) -> +15%
                    $fee = round($baseShipping * 0.15);
                    $condition = 'Có tuyết';
                }

                return response()->json([
                    'success' => true,
                    'fee' => $fee,
                    'condition' => $condition,
                    'code' => $code
                ]);
            }
        } catch (\Exception $e) {
            // Lỗi call API, trả về 0 để không chặn quá trình thanh toán
        }

        return response()->json([
            'success' => true,
            'fee' => 0,
            'condition' => 'Bình thường',
            'message' => 'Không thể kết nối dịch vụ thời tiết.'
        ]);
    }
}
