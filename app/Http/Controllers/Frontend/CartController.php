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
    // Nạp các dịch vụ hỗ trợ tính giá giỏ hàng, tính phí vận chuyển và áp dụng chương trình khuyến mãi
    public function __construct(
        private readonly CartPricingService $sv_cartPricing,
        private readonly ShippingQuoteService $sv_shippingQuote,
        private readonly PromotionService $sv_promotions,
    ) {}

    /**
     * Xác định xem giỏ hàng thuộc về người dùng đã đăng nhập (lấy theo user_id)
     * hay khách vãng lai chưa đăng nhập (lấy theo session_id hiện tại của trình duyệt).
     */
    private function getCartIdentifier()
    {
        if (Auth::check()) {
            return ['user_id' => Auth::id()];
        }

        $sessionId = Session::getId();
        return ['session_id' => $sessionId];
    }

    /**
     * Truy vấn tìm kiếm giỏ hàng hiện tại. Nếu chưa có giỏ hàng tương ứng
     * với định danh (user_id hoặc session_id), hệ thống sẽ tạo mới một giỏ hàng trống trong DB.
     */
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

        // Tạo giỏ hàng mới nếu chưa từng có
        if (!$cart) {
            $cartId = \App\Models\Cart::query()->insertGetId(array_merge($identifier, [
                'created_at' => now(),
                'updated_at' => now()
            ]));
            return \App\Models\Cart::query()->where('id', $cartId)->first();
        }

        return $cart;
    }

    /**
     * Tìm kiếm giỏ hàng hiện tại mà không tự động tạo mới (trả về null nếu không tìm thấy)
     */
    private function findCart(): ?Cart
    {
        $identifier = $this->getCartIdentifier();
        return Cart::query()->where(key($identifier), current($identifier))->first();
    }

    /**
     * Lấy toàn bộ thông tin chi tiết của giỏ hàng hiện tại để render ra giao diện (ví dụ: giỏ hàng trượt Sidebar).
     * Hàm này đồng thời tính toán tổng tiền tạm tính, nạp kèm danh sách topping, và kiểm tra xem giỏ hàng 
     * có đủ điều kiện nhận quà tặng hoặc giảm giá từ các chương trình khuyến mãi combo hay không.
     */
    public function getCartData()
    {
        $cart = $this->findCart();// Tìm kiếm giỏ hàng hiện tại

        // Nếu người dùng chưa có giỏ hàng, trả về mảng trống
        if (!$cart) {
            return response()->json([
                'success' => true,
                'items' => [],
                'count' => 0,
                'total' => 0,
                'formatted_total' => '0đ',
            ]);
        }

        // Lấy danh sách các món nước trong giỏ hàng và kết nối với bảng sản phẩm để lấy thông tin Tên, Ảnh
        $items = \App\Models\CartItem::query()
            ->join('products', 'cart_items.product_id', '=', 'products.id')
            ->where('cart_items.cart_id', $cart->id)
            ->select('cart_items.*', 'products.name', 'products.image')
            ->orderBy('cart_items.created_at', 'desc')
            ->get();

        $itemIds = $items->pluck('id');

        // Lấy toàn bộ topping đi kèm của các món nước trong giỏ hàng bằng một câu truy vấn duy nhất (Eager Loading thủ công)
        $itemToppings = \App\Models\CartItemTopping::query()
            ->join('toppings', 'cart_item_toppings.topping_id', '=', 'toppings.id')
            ->whereIn('cart_item_toppings.cart_item_id', $itemIds)
            ->select('cart_item_toppings.cart_item_id', 'toppings.name')
            ->get();

        $total = 0;
        foreach ($items as $item) {
            // Lọc ra các topping tương ứng với từng dòng sản phẩm
            $item->toppings = $itemToppings->where('cart_item_id', $item->id)->values();
            // Cộng dồn tổng tiền tạm tính (Giá đã gồm size + topping * Số lượng)
            $total += $item->unit_price * $item->quantity;
        }

        // Giải quyết các phần thưởng combo khuyến mãi (quà tặng đính kèm, tiền chiết khấu combo)
        $comboEntries = $this->sv_promotions->resolveComboRewards($items, 'delivery')['entries']; //// Xử lý và tính toán tất cả các phần thưởng Combo (giảm giá & tặng quà) cho giỏ hàng
        $gifts = collect($comboEntries)->where('type', 'gift');
        $comboDiscount = collect($comboEntries)->where('type', 'discount')->sum('discount_amount');

        return response()->json([
            'success' => true,
            'items' => $items,
            'count' => count($items),
            'total' => $total,
            'formatted_total' => number_format($total, 0, ',', '.') . 'đ',
            // Trả về danh sách quà tặng combo kèm số lượng để JS render ra giỏ hàng
            'gifts' => $gifts->map(fn($g) => [
                'gift_product_name' => $g['gift_product']->name,
                'quantity' => $g['granted_quantity'],
            ])->values(),
            'combo_discount' => $comboDiscount,
            'formatted_combo_discount' => $comboDiscount > 0 ? number_format($comboDiscount, 0, ',', '.') . 'đ' : null,
        ]); // main.js - updateCartUI(), POS create.js
    }

    /**
     * Thêm một sản phẩm với các tuỳ chọn tuỳ biến vào giỏ hàng.
     * Thuật toán so khớp: Nếu ly nước mới thêm có cùng ID sản phẩm, cùng Size, cùng Mức đường, Mức đá
     * và đặc biệt là cùng chính xác bộ Topping đã chọn -> Hệ thống sẽ gộp số lượng lại. 
     * Nếu lệch dù chỉ 1 tuỳ chọn hoặc 1 topping -> Hệ thống sẽ tạo một dòng mới trong giỏ hàng.
     */
    public function add(Request $request)
    {
        // 1. Kiểm tra định dạng dữ liệu đầu vào gửi lên
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

        // Xác định Size: Nếu khách không chọn, tự động lấy Size có giá phụ thu thấp nhất
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

        // Xác định mức đường (Mặc định là 100% đường nếu trống)
        $sugarLevel = $request->input('sugar_level');
        if ($sugarLevel === null) {
            $sugarLevel = '100';
        }

        // Xác định mức đá (Mặc định là đá bình thường nếu trống)
        $iceLevel = $request->input('ice_level');
        if ($iceLevel === null) {
            $iceLevel = 'normal';
        }

        // Loại bỏ các ID topping trùng lặp trong mảng yêu cầu
        $toppingIds = array_values(array_unique($validated['toppings'] ?? []));

        $product = \App\Models\Product::query()->where('id', $productId)->first();
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found']);
        }

        // Ngăn chặn thêm sản phẩm vào giỏ nếu sản phẩm đó đã bị tắt hoạt động (hết hàng/ngừng bán)
        if (!$product->is_active) {
            return response()->json(['success' => false, 'message' => 'Sản phẩm này đã hết hàng, không thể thêm vào giỏ hàng.']);
        }

        // Tính đơn giá bắt đầu bằng giá cơ bản của sản phẩm
        $unitPrice = $product->base_price;
        if ($sizeName) {
            $sizeRecord = \App\Models\ProductSize::query()
                ->where('product_id', $productId)
                ->where('size_name', $sizeName)
                ->first();
            if (!$sizeRecord) {
                return response()->json(['success' => false, 'message' => 'Kích thước không hợp lệ cho sản phẩm này.'], 422);
            }
            // Cộng thêm tiền chênh lệch kích cỡ
            $unitPrice += $sizeRecord->price_adjustment;
        }

        // Tính toán phụ thu từ danh sách các Topping được chọn thêm
        $tops = [];
        if (!empty($toppingIds)) {
            $tops = \App\Models\Topping::query()
                ->join('product_toppings', 'product_toppings.topping_id', '=', 'toppings.id')
                ->where('product_toppings.product_id', $productId)
                ->where('toppings.is_available', 1)
                ->whereIn('toppings.id', $toppingIds)
                ->select('toppings.*')->get();

            // Đảm bảo số lượng topping hợp lệ khớp với số lượng gửi lên
            if ($tops->count() !== count($toppingIds)) {
                return response()->json(['success' => false, 'message' => 'Topping không hợp lệ cho sản phẩm này.'], 422);
            }
            foreach ($tops as $t) {
                // Cộng dồn tiền topping vào đơn giá món nước
                $unitPrice += $t->price;
            }
        }

        $cart = $this->getOrCreateCart();

        // =========================================================================
        // THUẬT TOÁN SO KHỚP SẢN PHẨM TRÙNG TRONG GIỎ HÀNG
        // =========================================================================

        // Bước 1: Tìm tất cả các món nước trong giỏ có chung Product, Size, Đường và Đá
        $potentialItems = \App\Models\CartItem::query()
            ->where('cart_id', $cart->id)
            ->where('product_id', $productId)
            ->where('size_name', $sizeName)
            ->where('sugar_level', $sugarLevel)
            ->where('ice_level', $iceLevel)
            ->get();

        $existingItem = null;
        $reqTops = $toppingIds;
        sort($reqTops); // Sắp xếp mảng ID topping yêu cầu tăng dần để chuẩn bị so sánh

        // Bước 2: Duyệt qua các món nước tìm được, so sánh danh sách Topping đi kèm của chúng
        foreach ($potentialItems as $pi) {
            $piToppings = \App\Models\CartItemTopping::query()
                ->where('cart_item_id', $pi->id)
                ->pluck('topping_id')
                ->toArray();

            sort($piToppings); // Sắp xếp mảng ID topping của món trong giỏ tăng dần

            // So sánh 2 mảng đã được sắp xếp. Nếu trùng khít -> Đã tìm thấy ly nước y hệt cấu hình
            if ($piToppings == $reqTops) {
                $existingItem = $pi;
                break;
            }
        }

        // Bước 3: Nếu tìm thấy ly nước trùng cấu hình -> Cập nhật cộng dồn số lượng (tối đa 99 ly)
        if ($existingItem) {
            \App\Models\CartItem::query()
                ->where('id', $existingItem->id)
                ->update([
                    'quantity' => min(99, $existingItem->quantity + $quantity),
                    'updated_at' => now()
                ]);
        } else {
            // Nếu không trùng khớp -> Tạo mới một dòng sản phẩm trong giỏ hàng
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

            // Lưu liên kết các topping đi kèm của ly nước mới tạo này vào cơ sở dữ liệu
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

        // Trả về dữ liệu giỏ hàng mới nhất sau khi thêm
        return $this->getCartData();
    }

    /**
     * Xóa một sản phẩm cụ thể khỏi giỏ hàng của khách hàng
     */
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

    /**
     * Cập nhật số lượng mua của một sản phẩm trong giỏ hàng (khi nhấn tăng/giảm nút cộng trừ)
     */
    public function  update(Request $request)
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

    /**
     * Xóa hàng loạt các sản phẩm được tích chọn trong giỏ hàng (tính năng "Xóa đã chọn")
     */
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

    /**
     * Xóa toàn bộ sản phẩm có trong giỏ hàng của khách hàng (làm trống giỏ hàng)
     */
    public function clear(Request $request)
    {
        $cart = $this->findCart();

        if ($cart) {
            \App\Models\CartItem::query()->where('cart_id', $cart->id)->delete();
        }

        return $this->getCartData();
    }

    /**
     * Thêm toàn bộ các sản phẩm còn đang bán trong mục "Yêu thích" vào lại giỏ hàng.
     * Mặc định các món này sẽ lấy kích cỡ chuẩn, 100% đường và đá bình thường.
     */
    public function addAll(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Bạn cần đăng nhập để sử dụng tính năng này']);
        }

        // Lấy danh sách sản phẩm yêu thích còn kinh doanh
        $favorites = \App\Models\Favorite::query()
            ->join('products', 'favorites.product_id', '=', 'products.id')
            ->where('favorites.user_id', Auth::id())
            ->where('products.is_active', 1)
            ->select('products.id', 'products.base_price')
            ->get();

        if ($favorites->isEmpty()) {
            return $this->getCartData();
        }

        $cart = $this->getOrCreateCart();

        foreach ($favorites as $product) {
            // Xác định size mặc định rẻ nhất
            $defaultSize = \App\Models\ProductSize::query()
                ->where('product_id', $product->id)
                ->orderBy('price_adjustment', 'asc')
                ->first();

            $sizeName = $defaultSize ? $defaultSize->size_name : null;
            $unitPrice = $product->base_price + ($defaultSize ? $defaultSize->price_adjustment : 0);
            $sugarLevel = '100';
            $iceLevel = 'normal';

            // Kiểm tra xem sản phẩm mặc định này đã có trong giỏ chưa
            $existingItem = \App\Models\CartItem::query()
                ->where('cart_id', $cart->id)
                ->where('product_id', $product->id)
                ->where('size_name', $sizeName)
                ->where('sugar_level', $sugarLevel)
                ->where('ice_level', $iceLevel)
                ->first();

            // Chỉ gộp số lượng nếu ly nước trong giỏ đó không chứa topping (vì ly yêu thích thêm nhanh không có topping)
            $hasNoToppings = true;
            if ($existingItem) {
                $hasToppings = \App\Models\CartItemTopping::query()->where('cart_item_id', $existingItem->id)->exists();
                if ($hasToppings) {
                    $hasNoToppings = false;
                    $existingItem = null; // Tạo mới dòng nếu ly cũ trong giỏ có topping
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

    /**
     * Ghi nhận danh sách ID các sản phẩm được người dùng tích chọn thanh toán vào Session.
     * Điều này đảm bảo khi chuyển hướng sang trang thanh toán (Checkout), hệ thống chỉ tính tiền
     * cho những món nước được chọn, thay vì thanh toán toàn bộ giỏ hàng.
     * 
     * Phản hồi trả về:
     * - Trả dữ liệu JSON về cho hàm cartProceedToCheckout() trong file JS: [public/js/frontend/layout/main.js]
     *   để điều hướng sang trang /checkout khi thành công.
     */
    public function setSelected(Request $request)
    {
        $validated = $request->validate([
            'selected_item_ids'   => ['required', 'array', 'min:1', 'max:50'],
            'selected_item_ids.*' => ['integer', 'min:1'],
        ]);

        $cart = $this->findCart();
        if (!$cart) {
            return response()->json(['success' => false, 'message' => 'Giỏ hàng không tồn tại.'], 400);
        }

        // Xác thực chặt chẽ phía Server: Đảm bảo các ID sản phẩm gửi lên thực sự thuộc giỏ hàng của user này
        $validIds = CartItem::query()
            ->where('cart_id', $cart->id)
            ->whereIn('id', $validated['selected_item_ids'])
            ->pluck('id')
            ->toArray();

        if (empty($validIds)) {
            return response()->json(['success' => false, 'message' => 'Không có sản phẩm hợp lệ được chọn.'], 422);
        }

        // Lưu danh sách ID hợp lệ vào Session
        session(['selected_cart_item_ids' => $validIds]);

        return response()->json([
            'success'  => true,
            'selected' => $validIds,
            'count'    => count($validIds),
        ]);
    }

    /**
     * Mở trang hiển thị thông tin thanh toán (Checkout).
     * Hàm này kiểm tra trạng thái hoạt động của cửa hàng (giờ đóng mở cửa), nạp danh sách địa chỉ nhận hàng,
     * tính toán giá tạm tính cho các sản phẩm đã chọn, và áp dụng trước các chương trình chiết khấu combo/quà tặng.
     */
    public function checkout()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $userId = Auth::id();

        // Lấy danh sách địa chỉ của khách hàng (ưu tiên địa chỉ mặc định lên đầu)
        $addresses = \App\Models\UserAddress::query()
            ->where('user_id', $userId)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $cart = \App\Models\Cart::query()
            ->where('user_id', $userId)
            ->first();

        $items = collect();
        $subtotal = 0;
        if ($cart) {
            try {
                // Đọc danh sách ID sản phẩm khách đã tick chọn thanh toán lưu trong Session
                $selectedIds = session('selected_cart_item_ids');

                if (!empty($selectedIds)) {
                    // Xác thực lại tính hợp lệ của danh sách ID sản phẩm
                    $validSelectedIds = CartItem::query()
                        ->where('cart_id', $cart->id)
                        ->whereIn('id', $selectedIds)
                        ->pluck('id')
                        ->toArray();
                    $selectedIds = !empty($validSelectedIds) ? $validSelectedIds : null;
                } else {
                    $selectedIds = null; // Mặc định thanh toán toàn bộ giỏ nếu session trống
                }

                // Gọi CartPricingService để tính toán giá tiền (áp dụng các chiết khấu thành viên nếu có)
                $items = $this->sv_cartPricing->pricedItems($cart, selectedIds: $selectedIds);
                foreach ($items as $item) {
                    $item->name = $item->product->name;
                    $item->image = $item->product->image;
                    $item->toppings = $item->calculated_toppings;
                }
                $subtotal = $this->sv_cartPricing->subtotal($items);
            } catch (ValidationException $exception) {
                return redirect('/')->withErrors($exception->errors());
            }
        }

        // Trả về trang chủ kèm cảnh báo nếu giỏ thanh toán trống
        if ($items->isEmpty()) {
            return redirect('/')->with('warning', 'Giỏ hàng của bạn đang trống.');
        }

        // Tính toán hiển thị quà tặng combo và mức chiết khấu combo trực quan tại trang checkout
        $comboEntries = $this->sv_promotions->resolveComboRewards($items, 'delivery')['entries'];
        $gifts = collect($comboEntries)->where('type', 'gift')->values()->all();
        $comboDiscount = collect($comboEntries)->where('type', 'discount')->sum('discount_amount');

        // Xác định ngưỡng miễn phí vận chuyển theo hạng thành viên (Diamond được miễn phí hoàn toàn)
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

        // 1. Kiểm tra trạng thái tắt nhận đơn hàng từ trang quản trị của Admin
        $receiveEnabled = (bool) \App\Models\Setting::getValue('orders_enabled', true);
        $isClosed = !$receiveEnabled;
        $closedReason = null;

        if ($isClosed) {
            $closedReason = 'Cửa hàng hiện đang tạm ngưng tiếp nhận đơn hàng mới. Quý khách vui lòng quay lại sau!';
        } else {
            // 2. Kiểm tra giờ hoạt động của cửa hàng (Hỗ trợ cấu hình chạy qua đêm)
            $open = \App\Models\Setting::getValue('store_open_time', '08:00');
            $close = \App\Models\Setting::getValue('store_close_time', '22:00');
            $nowStr = now()->format('H:i');

            $isOpen = false;
            if ($open < $close) {
                $isOpen = ($nowStr >= $open && $nowStr <= $close);
            } else { // Khung giờ mở qua đêm
                $isOpen = ($nowStr >= $open || $nowStr <= $close);
            }
            if (!$isOpen) {
                $isClosed = true;
                $closedReason = "Cửa hàng hiện đã đóng cửa! Giờ hoạt động của chúng tôi là từ {$open} đến {$close} hàng ngày. Quý khách hiện tại có thể tham khảo giỏ hàng nhưng không thể đặt hàng mới vào lúc này.";
            }
        }

        // Khởi tạo token chống đặt lặp đơn (Idempotency token) và lưu vào Session
        $checkoutToken = (string) Str::uuid();
        session(['checkout_token' => $checkoutToken]);

        if (\App\Models\Setting::getValue('loyalty_point_value') != 1) {
            \App\Models\Setting::setValue('loyalty_point_value', '1', 'loyalty', 'decimal');
        }

        return view('frontend.orders.checkout', compact('items', 'subtotal', 'addresses', 'isClosed', 'closedReason', 'freeShipThreshold', 'checkoutToken', 'gifts', 'comboDiscount'));
    }

    /**
     * Tính toán khoảng cách vận chuyển thực tế qua API bản đồ Geoapify.
     * Nếu địa chỉ của người dùng chưa có toạ độ (vĩ độ, kinh độ), hệ thống sẽ geocode ngầm 
     * địa chỉ dạng chữ qua Geoapify Geocoding API để xác định tọa độ và lưu trữ lại sử dụng lâu dài.
     */
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

        // Tự động Geocode nếu địa chỉ thiếu tọa độ GPS và lưu
        if (empty($address->latitude) || empty($address->longitude)) {
            $destAddress = $address->specific_address . ', ' . $address->district . ', ' . $address->province . ', Việt Nam';
            $geocoded = $geoapify->geocodeAddress($destAddress);// Chuyển đổi địa chỉ dạng chữ thành tọa độ địa lý (vĩ độ - kinh độ)
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

        // Sử dụng ShippingQuoteService để tính toán khoảng cách thực tế (theo tuyến đường đi)
        $result = $this->sv_shippingQuote->distanceForWithSource($address);

        return response()->json([
            'success' => true,
            'distance_km' => $result['distance_km'],
            'is_mock' => $result['is_mock'],
            'message' => $result['is_mock'] ? 'Sử dụng khoảng cách mô phỏng dự phòng.' : null,
        ]); // checkout.js - updateDistanceForAddress()
    }

    /**
     * Lấy danh sách sản phẩm đã được tính giá cụ thể của người dùng để chuẩn bị validate Coupon giảm giá
     */
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
            return $this->sv_cartPricing->pricedItems($cart, selectedIds: $selectedIds);
        } catch (ValidationException) {
            return collect();
        }
    }

    /**
     * Xác minh tính hợp lệ và tính mức tiền được khấu trừ khi áp dụng Mã giảm giá (Coupon)
     */
    public function validateCoupon(Request $request)
    {
        $code = strtoupper(trim($request->input('coupon_code')));
        $items = $this->pricedSelectedItems();
        if ($items->isEmpty()) {
            return response()->json(['valid' => false, 'message' => 'Giỏ hàng của bạn đang trống.']);
        }
        $subtotal = $this->sv_cartPricing->subtotal($items);//tính tổng số tiền của giỏ hàng trước khi áp dụng mã giảm giá.
        $totalQuantity = (int) $items->sum('quantity');
        
        if (Auth::check()) {
            $user = Auth::user();
        } else {
            $user = null;
        }

        try {
            // Chọn mã giảm giá tốt nhất cho giỏ hàng, kiểm tra hợp lệ
            $result = $this->sv_promotions->resolveBestDiscount($items, $subtotal, $user, 'delivery', $totalQuantity, $code);
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
            'scope' => $coupon->scope,
            // Mô tả phạm vi áp dụng cho sản phẩm hoặc danh mục cụ thể để hiển thị trực quan lên giao diện
            'scope_label' => match ($coupon->scope) {
                'product' => 'Áp dụng cho: ' . $coupon->products->pluck('name')->implode(', '),
                'category' => 'Áp dụng cho danh mục: ' . $coupon->categories->pluck('name')->implode(', '),
                default => null,
            },
        ]); // checkout.js - applyCouponBtn click listener - 1182
    }

    /**
     * Tính toán khoản phụ thu thời tiết xấu (ví dụ mưa bão, ngập lụt) dựa theo tọa độ giao hàng.
     * Hệ thống tự động liên kết với API thời tiết hoặc cấu hình thủ công trong trang quản trị.
     */
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

        // Lấy ngưỡng được miễn phí vận chuyển theo hạng thành viên
        $threshold = match (Auth::user()?->membership_level) {
            'silver' => 120000.0,
            'gold' => 90000.0,
            'diamond' => 0.0,
            default => (float) \App\Models\Setting::getValue('free_shipping_minimum', 150000),
        };

        // Tính phí vận chuyển cơ bản dựa theo khoảng cách km
        $baseFee = (float) \App\Models\Setting::getValue('shipping_base_fee', 15000);
        $feePerKm = (float) \App\Models\Setting::getValue('shipping_fee_per_km', 5000);
        $shippingFee = $distanceKm <= 2 ? $baseFee : $baseFee + ($distanceKm - 2) * $feePerKm;
        $shippingFee = $subtotal >= $threshold ? 0 : round($shippingFee); // Miễn ship nếu đạt ngưỡng

        // Tính toán mức phụ thu thời tiết xấu dựa trên phí vận chuyển chuẩn thông qua ShippingQuoteService
        $result = $this->sv_shippingQuote->weatherSurcharge(
            $shippingFee,
            $address->latitude ? (float) $address->latitude : null,
            $address->longitude ? (float) $address->longitude : null,
        );

        return response()->json([
            'success' => true,
            'fee' => $result['fee'],
            'condition' => $result['label'],
        ]); // checkout.js - updateWeatherFeeForAddress()
    }
}
