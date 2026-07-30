<?php

namespace App\Http\Controllers\Frontend;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CartItemTopping;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\Topping;
use App\Models\UserAddress;
use App\Services\CartPricingService;
use App\Services\PromotionService;
use App\Services\ShippingQuoteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CartController
{
    public function __construct(
        private readonly CartPricingService $cartPricing,
        private readonly ShippingQuoteService $shippingQuote,
        private readonly PromotionService $promotions,
    ) {}

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

    private function findCart(): ?Cart
    {
        $identifier = $this->getCartIdentifier();
        return Cart::query()->where(key($identifier), current($identifier))->first();
    }

    public function getCartData()
    {
        $cart = $this->findCart();

        if (!$cart) {
            return response()->json([
                'success' => true,
                'items' => [],
                'count' => 0,
                'total' => 0,
                'formatted_total' => '0đ',
            ]);
        }

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

        // Xem trước quà tặng Mua X tặng Y ngay ở ngăn kéo giỏ hàng — chỉ để HIỂN THỊ (không phải số
        // tiền/lưu DB), nên dùng thẳng $items thô (đã có product_id+quantity) không cần CartPricingService.
        // Kênh 'delivery' vì ngăn kéo giỏ hàng chỉ phục vụ khách tự mua trên website (không phải POS).
        $gifts = $this->promotions->resolveGifts($items, 'delivery');

        return response()->json([
            'success' => true,
            'items' => $items,
            'count' => count($items),
            'total' => $total,
            'formatted_total' => number_format($total, 0, ',', '.') . 'đ',
            'gifts' => collect($gifts)->map(fn ($g) => [
                'gift_product_name' => $g['gift_product']->name,
                'quantity' => $g['granted_quantity'],
            ])->values(),
        ]);
    }

    public function add(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['sometimes', 'integer', 'min:1', 'max:99'],
            'size_name' => ['nullable', 'string', 'max:50'],
            'sugar_level' => ['nullable', 'string', 'max:20'],
            'ice_level' => ['nullable', 'string', 'max:20'],
            'toppings' => ['sometimes', 'array', 'max:20'],
            'toppings.*' => ['integer', 'distinct', 'exists:toppings,id'],
        ]);

        $productId = (int) $validated['product_id'];
        $quantity = (int) ($validated['quantity'] ?? 1);
        $sizeName = $validated['size_name'] ?? null;
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

        $toppingIds = array_values(array_unique($validated['toppings'] ?? []));

        $product = \App\Models\Product::query()->where('id', $productId)->first();
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found']);
        }

        // Kiểm tra sản phẩm còn hàng không
        if (!$product->is_active) {
            return response()->json(['success' => false, 'message' => 'Sản phẩm này đã hết hàng, không thể thêm vào giỏ hàng.']);
        }
        if (!$product->hasSufficientMaterials($quantity)) {
            return response()->json(['success' => false, 'message' => 'Sản phẩm tạm hết nguyên liệu còn hạn sử dụng.'], 422);
        }

        // Tính giá dựa theo size
        $unitPrice = $product->base_price;
        if ($sizeName) {
            $sizeRecord = \App\Models\ProductSize::query()
                ->where('product_id', $productId)
                ->where('size_name', $sizeName)
                ->first();
            if (!$sizeRecord) {
                return response()->json(['success' => false, 'message' => 'Kích thước không hợp lệ cho sản phẩm này.'], 422);
            }
            $unitPrice += $sizeRecord->price_adjustment;
        }

        // Tính giá dựa theo topping
        $tops = [];
        if (!empty($toppingIds)) {
            $tops = \App\Models\Topping::query()
                ->join('product_toppings', 'product_toppings.topping_id', '=', 'toppings.id')
                ->where('product_toppings.product_id', $productId)
                ->where('toppings.is_available', 1)
                ->whereIn('toppings.id', $toppingIds)
                ->select('toppings.*')->get();
            if ($tops->count() !== count($toppingIds)) {
                return response()->json(['success' => false, 'message' => 'Topping không hợp lệ cho sản phẩm này.'], 422);
            }
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
                    'quantity' => min(99, $existingItem->quantity + $quantity),
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
        $validated = $request->validate(['item_id' => ['required', 'integer']]);
        $cart = $this->findCart();

        if ($cart) {
            \App\Models\CartItem::query()
                ->where('id', $validated['item_id'])
                ->where('cart_id', $cart->id)
                ->delete();
        }

        return $this->getCartData();
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'item_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);
        $cart = $this->findCart();

        if ($cart) {
            \App\Models\CartItem::query()
                ->where('id', $validated['item_id'])
                ->where('cart_id', $cart->id)
                ->update([
                    'quantity' => $validated['quantity'],
                    'updated_at' => now()
                ]);
        }

        return $this->getCartData();
    }

    // Xóa nhiều sản phẩm cùng lúc ("Xóa đã chọn") - dùng đúng danh sách item_id đang được tick chọn
    // ở ngăn kéo giỏ hàng (cùng checkbox dùng cho "chọn để thanh toán"), scope theo cart_id của
    // CHÍNH người dùng đang đăng nhập giống remove()/update() ở trên, không tin item_id gửi lên thuộc
    // về giỏ hàng nào.
    public function removeMany(Request $request)
    {
        $validated = $request->validate([
            'item_ids' => ['required', 'array', 'min:1'],
            'item_ids.*' => ['integer'],
        ]);
        $cart = $this->findCart();

        if ($cart) {
            \App\Models\CartItem::query()
                ->where('cart_id', $cart->id)
                ->whereIn('id', $validated['item_ids'])
                ->delete();
        }

        return $this->getCartData();
    }

    // Xóa toàn bộ giỏ hàng ("Xóa tất cả").
    public function clear(Request $request)
    {
        $cart = $this->findCart();

        if ($cart) {
            \App\Models\CartItem::query()->where('cart_id', $cart->id)->delete();
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

    public function setSelected(Request $request)
    {
        $validated = $request->validate([
            'selected_item_ids'   => ['required', 'array', 'min:1', 'max:50'],
            'selected_item_ids.*' => ['integer', 'min:1'],
        ]);

        // Verify the IDs actually belong to this user's cart (server-side security)
        $cart = $this->findCart();
        if (!$cart) {
            return response()->json(['success' => false, 'message' => 'Giỏ hàng không tồn tại.'], 400);
        }

        $validIds = CartItem::query()
            ->where('cart_id', $cart->id)
            ->whereIn('id', $validated['selected_item_ids'])
            ->pluck('id')
            ->toArray();

        if (empty($validIds)) {
            return response()->json(['success' => false, 'message' => 'Không có sản phẩm hợp lệ được chọn.'], 422);
        }

        session(['selected_cart_item_ids' => $validIds]);

        return response()->json([
            'success'  => true,
            'selected' => $validIds,
            'count'    => count($validIds),
        ]);
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
            try {
                // Đọc danh sách id sản phẩm đã chọn từ session (null = lấy toàn bộ giỏ)
                $selectedIds = session('selected_cart_item_ids');

                // Nếu có selectedIds, validate lại phía server rằng chúng thuộc cart của user này
                if (!empty($selectedIds)) {
                    $validSelectedIds = CartItem::query()
                        ->where('cart_id', $cart->id)
                        ->whereIn('id', $selectedIds)
                        ->pluck('id')
                        ->toArray();
                    $selectedIds = !empty($validSelectedIds) ? $validSelectedIds : null;
                } else {
                    $selectedIds = null; // fallback: tất cả giỏ
                }

                $items = $this->cartPricing->pricedItems($cart, selectedIds: $selectedIds);
                foreach ($items as $item) {
                    $item->name = $item->product->name;
                    $item->image = $item->product->image;
                    $item->toppings = $item->calculated_toppings;
                }
                $subtotal = $this->cartPricing->subtotal($items);
            } catch (ValidationException $exception) {
                return redirect('/')->withErrors($exception->errors());
            }
        }

        // If cart is empty, redirect back to products or home with a warning
        if ($items->isEmpty()) {
            return redirect('/')->with('warning', 'Giỏ hàng của bạn đang trống.');
        }

        // Quà tặng Mua X tặng Y đang đủ điều kiện — chỉ để HIỂN THỊ ở trang checkout, số tiền/quà tặng
        // thật sự được vật chất hóa lại từ đầu trong OrderService::create() khi đặt hàng thật.
        $gifts = $this->promotions->resolveGifts($items, 'delivery');

        $freeShipThreshold = (float) \App\Models\Setting::getValue('free_shipping_minimum', 150000);
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

        // 1. Kiểm tra tắt nhận đơn hàng
        $receiveEnabled = (bool) \App\Models\Setting::getValue('orders_enabled', true);
        $isClosed = !$receiveEnabled;
        $closedReason = null;

        if ($isClosed) {
            $closedReason = 'Cửa hàng hiện đang tạm ngưng tiếp nhận đơn hàng mới. Quý khách vui lòng quay lại sau!';
        } else {
            // 2. Kiểm tra giờ đóng/mở cửa
            $open = \App\Models\Setting::getValue('store_open_time', '08:00');
            $close = \App\Models\Setting::getValue('store_close_time', '22:00');
            $nowStr = now()->format('H:i');
            
            $isOpen = false;
            if ($open < $close) {
                $isOpen = ($nowStr >= $open && $nowStr <= $close);
            } else { // Qua đêm
                $isOpen = ($nowStr >= $open || $nowStr <= $close);
            }
            if (!$isOpen) {
                $isClosed = true;
                $closedReason = "Cửa hàng hiện đã đóng cửa! Giờ hoạt động của chúng tôi là từ {$open} đến {$close} hàng ngày. Quý khách hiện tại có thể tham khảo giỏ hàng nhưng không thể đặt hàng mới vào lúc này.";
            }
        }

        $checkoutToken = (string) Str::uuid();
        session(['checkout_token' => $checkoutToken]);

        if (\App\Models\Setting::getValue('loyalty_point_value') != 1) {
            \App\Models\Setting::setValue('loyalty_point_value', '1', 'loyalty', 'decimal');
        }

        return view('frontend.orders.checkout', compact('items', 'subtotal', 'addresses', 'isClosed', 'closedReason', 'freeShipThreshold', 'checkoutToken', 'gifts'));
    }

    public function calculateDistance(Request $request, \App\Services\GeoapifyService $geoapify)
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

        // Thiếu tọa độ (địa chỉ cũ nhập tay, chưa từng chọn qua bản đồ) -> geocode chuỗi địa chỉ qua
        // Geoapify Geocoding API để lấy tọa độ, rồi lưu lại vào DB để dùng ngay từ lần sau.
        if (empty($address->latitude) || empty($address->longitude)) {
            // Đã xác nhận qua test thật với API Geoapify: (1) CỐ Ý bỏ Phường/Xã khỏi chuỗi truy vấn —
            // cụm "Phường 8" (phường đặt tên bằng số) khiến Geoapify hiểu sai địa chỉ hoàn toàn (trả về
            // tọa độ ở Bắc Ninh/Hà Nội thay vì TP.HCM, confidence=0); (2) BẮT BUỘC phải có "Việt Nam" ở
            // cuối — thiếu quốc gia thì Geoapify trả về 0 kết quả hoàn toàn (không parse được). Chỉ dùng
            // "địa chỉ cụ thể + quận/huyện + tỉnh/thành + Việt Nam" cho kết quả đúng ổn định
            // (confidence=1.0, match_type=full_match khi kiểm tra thật).
            $destAddress = $address->specific_address . ', ' . $address->district . ', ' . $address->province . ', Việt Nam';
            $geocoded = $geoapify->geocodeAddress($destAddress);
            if ($geocoded) {
                $address->latitude = $geocoded['lat'];
                $address->longitude = $geocoded['lng'];
                \App\Models\UserAddress::query()->where('id', $addressId)->update([
                    'latitude' => $geocoded['lat'],
                    'longitude' => $geocoded['lng'],
                    'updated_at' => now(),
                ]);
            }
        }

        // Tái dùng ĐÚNG luồng tính khoảng cách thật (Geoapify Routing API -> OpenRouteService dự phòng
        // -> ước lượng cố định theo quận/huyện) đang dùng cho đơn hàng thật ở ShippingQuoteService, đảm
        // bảo số hiển thị ở màn hình checkout luôn khớp với số tính phí thật khi đặt hàng.
        $result = $this->shippingQuote->distanceForWithSource($address);

        return response()->json([
            'success' => true,
            'distance_km' => $result['distance_km'],
            'is_mock' => $result['is_mock'],
            'message' => $result['is_mock'] ? 'Sử dụng khoảng cách mô phỏng dự phòng.' : null,
        ]);
    }

    // Giỏ hàng đã tính giá (calculated_unit_price/product load sẵn) + tổng đã chọn, đúng theo cách
    // checkout() đang dựng danh sách món — dùng CHUNG ở đây để validateCoupon() không còn phải tin số
    // subtotal/quantity do JS gửi lên (trước đây $subtotal lấy thẳng từ request, có thể bị sửa tùy ý
    // trong DevTools). Trả collection rỗng nếu chưa có giỏ hàng.
    private function pricedSelectedItems(): \Illuminate\Support\Collection
    {
        $cart = $this->findCart();
        if (!$cart) {
            return collect();
        }

        $selectedIds = session('selected_cart_item_ids');
        if (!empty($selectedIds)) {
            $validSelectedIds = CartItem::query()->where('cart_id', $cart->id)->whereIn('id', $selectedIds)->pluck('id')->toArray();
            $selectedIds = !empty($validSelectedIds) ? $validSelectedIds : null;
        } else {
            $selectedIds = null;
        }

        try {
            return $this->cartPricing->pricedItems($cart, selectedIds: $selectedIds);
        } catch (ValidationException) {
            return collect();
        }
    }

    public function validateCoupon(Request $request)
    {
        $code = strtoupper(trim($request->input('coupon_code')));
        $items = $this->pricedSelectedItems();
        if ($items->isEmpty()) {
            return response()->json(['valid' => false, 'message' => 'Giỏ hàng của bạn đang trống.']);
        }
        $subtotal = $this->cartPricing->subtotal($items);
        $totalQuantity = (int) $items->sum('quantity');
        $user = Auth::check() ? Auth::user() : null;

        try {
            // Trang checkout của khách hàng luôn là đơn giao hàng (không có tùy chọn nhận tại quầy) ->
            // mã chỉ dành riêng cho "Tại quầy" bị PromotionService từ chối ở đây (channel='delivery').
            $result = $this->promotions->resolveBestDiscount($items, $subtotal, $user, 'delivery', $totalQuantity, $code);
        } catch (ValidationException $e) {
            return response()->json(['valid' => false, 'message' => collect($e->errors())->flatten()->first()]);
        }

        $coupon = $result['promotion'];

        return response()->json([
            'valid' => true,
            'message' => 'Áp dụng thành công mã giảm giá ' . $code . '!',
            'discount_amount' => $result['discount'],
            'coupon_code' => $code,
            'discount_value' => $coupon->value,
            'discount_type' => $coupon->type,
            'max_discount_amount' => $coupon->max_discount_amount,
            // Cho JS hiển thị rõ mã áp dụng cho phạm vi nào (toàn đơn/sản phẩm/danh mục cụ thể).
            'scope' => $coupon->scope,
            'scope_label' => match ($coupon->scope) {
                'product' => 'Áp dụng cho: ' . $coupon->products->pluck('name')->implode(', '),
                'category' => 'Áp dụng cho danh mục: ' . $coupon->categories->pluck('name')->implode(', '),
                default => null,
            },
        ]);
    }

    // Phụ thu thời tiết hiển thị ở trang checkout. TOÀN BỘ phép tính (bật/tắt, mức %, ép thời tiết để
    // demo, quy tắc miễn ship thì miễn phụ thu) nằm trong ShippingQuoteService::weatherSurcharge() —
    // ở đây chỉ dựng lại đúng mức phí ship rồi gọi sang, để số hiện trên màn hình luôn khớp số thật
    // lúc tạo đơn. Trước đây hàm này tự tính riêng (bỏ qua cờ bật/tắt, hard-code 5/10/15% và công thức
    // phí ship 3.000đ/km) nên có thể hiện một đằng, tính tiền một nẻo.
    public function calculateWeatherFee(Request $request)
    {
        $address = \App\Models\UserAddress::query()
            ->where('id', $request->query('address_id'))
            ->where('user_id', Auth::id())
            ->first();

        if (!$address) {
            return response()->json(['success' => false, 'message' => 'Địa chỉ không hợp lệ', 'fee' => 0], 400);
        }

        $subtotal = (float) $request->query('subtotal', 0);
        $distanceKm = (float) $request->query('distance_km', 0);

        $threshold = match (Auth::user()?->membership_level) {
            'silver' => 120000.0,
            'gold' => 90000.0,
            'diamond' => 0.0,
            default => (float) \App\Models\Setting::getValue('free_shipping_minimum', 150000),
        };

        // Dựng lại phí ship theo ĐÚNG công thức của ShippingQuoteService::quote().
        $baseFee = (float) \App\Models\Setting::getValue('shipping_base_fee', 15000);
        $feePerKm = (float) \App\Models\Setting::getValue('shipping_fee_per_km', 5000);
        $shippingFee = $distanceKm <= 2 ? $baseFee : $baseFee + ($distanceKm - 2) * $feePerKm;
        $shippingFee = $subtotal >= $threshold ? 0 : round($shippingFee);

        $result = $this->shippingQuote->weatherSurcharge(
            $shippingFee,
            $address->latitude ? (float) $address->latitude : null,
            $address->longitude ? (float) $address->longitude : null,
        );

        return response()->json([
            'success' => true,
            'fee' => $result['fee'],
            'condition' => $result['label'],
        ]);
    }
}
