<?php

namespace App\Http\Controllers\Frontend;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CartItemTopping;
use App\Models\Favorite;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\Promotion;
use App\Models\Setting;
use App\Models\Topping;
use App\Models\UserAddress;
use App\Services\CartPricingService;
use App\Services\GeoapifyService;
use App\Services\PromotionService;
use App\Services\ShippingQuoteService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

    // LẤY GIỎ HÀNG USER HIỆN TẠI
    private function getOrCreateCart()
    {
        $userId = Auth::id();
        $cart = Cart::query()->where('user_id', $userId)->first();

        // Tạo giỏ hàng mới nếu chưa từng có
        if (!$cart) {
            $cartId = Cart::query()->insertGetId([
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return Cart::query()->where('id', $cartId)->first();
        }

        return $cart;
    }

    // TÌM KIẾM GIỎ HÀNG — trả null nếu chưa đăng nhập (route getCartData() không yêu cầu auth)
    private function findCart(): ?Cart
    {
        if (!Auth::check()) {
            return null;
        }
        return Cart::query()->where('user_id', Auth::id())->first();
    }


    //LẤY THONG TIN CHI TIẾT CỦA GIỎ HÀNG HIỆN RA GIAO DIỆN
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
        $items = CartItem::query()
            ->join('products', 'cart_items.product_id', '=', 'products.id')
            ->where('cart_items.cart_id', $cart->id)
            ->select('cart_items.*', 'products.name', 'products.image')
            ->orderBy('cart_items.created_at', 'desc')
            ->get();

        $itemIds = $items->pluck('id');

        // Lấy toàn bộ topping đi kèm của các món nước trong giỏ hàng bằng một câu truy vấn duy nhất (Eager Loading thủ công)
        $itemToppings = CartItemTopping::query()
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

        // Giỏ hàng không còn hiện quà/giảm giá combo nữa: combo chỉ ăn khi khách tự bấm chọn mã của nó
        // ở trang thanh toán, nên ở đây chưa thể biết khách sẽ chọn mã nào.
        return response()->json([
            'success' => true,
            'items' => $items,
            'count' => count($items),
            'total' => $total,
            'formatted_total' => number_format($total, 0, ',', '.') . 'đ',
        ]);
    }

    // THÊM SP YÊU THÍCH VÀO GIỎ HÀNG
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
            $defaultSize = ProductSize::query()
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

        $product = Product::query()->where('id', $productId)->first();
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
            $sizeRecord = ProductSize::query()
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
            $tops = Topping::query()
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
        $potentialItems = CartItem::query()
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
            $piToppings = CartItemTopping::query()
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
            CartItem::query()
                ->where('id', $existingItem->id)
                ->update([
                    'quantity' => min(99, $existingItem->quantity + $quantity),
                    'updated_at' => now()
                ]);
        } else {
            // Nếu không trùng khớp -> Tạo mới một dòng sản phẩm trong giỏ hàng
            $newItemId = CartItem::query()->insertGetId([
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
                CartItemTopping::query()->insert($inserts);
            }
        }

        // Trả về dữ liệu giỏ hàng mới nhất sau khi thêm
        return $this->getCartData();
    }

    //XÓA TOÀN BỘ
    public function remove(Request $request)
    {
        $validated = $request->validate(['item_id' => ['required', 'integer']]);
        $cart = $this->findCart();

        if ($cart) {
            CartItem::query()
                ->where('id', $validated['item_id'])
                ->where('cart_id', $cart->id)
                ->delete();
        }

        return $this->getCartData();
    }

    //CẬP NHẬT
    public function  update(Request $request)
    {
        $validated = $request->validate([
            'item_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);
        $cart = $this->findCart();

        if ($cart) {
            CartItem::query()
                ->where('id', $validated['item_id'])
                ->where('cart_id', $cart->id)
                ->update([
                    'quantity' => $validated['quantity'],
                    'updated_at' => now()
                ]);
        }

        return $this->getCartData();
    }

    //XÓA ĐÃ CHỌN
    public function removeMany(Request $request)
    {
        $validated = $request->validate([
            'item_ids' => ['required', 'array', 'min:1'],
            'item_ids.*' => ['integer'],
        ]);
        $cart = $this->findCart();

        if ($cart) {
            CartItem::query()
                ->where('cart_id', $cart->id)
                ->whereIn('id', $validated['item_ids'])
                ->delete();
        }

        return $this->getCartData();
    }

   //XÓA TOÀN BỘ
    public function clear(Request $request)
    {
        $cart = $this->findCart();

        if ($cart) {
            CartItem::query()->where('cart_id', $cart->id)->delete();
        }

        return $this->getCartData();
    }


    // THÊM TOÀN BỘ YÊU THÍCH VÀO GIỎ HÀNG
    public function addAll(Request $request)
    {
        // Lấy danh sách sản phẩm yêu thích còn kinh doanh
        $favorites = Favorite::query()
            ->join('products', 'favorites.product_id', '=', 'products.id')
            ->where('favorites.user_id', Auth::id())
            ->where('products.is_active', 1)
            ->select('products.id', 'products.base_price')
            ->get();

        if ($favorites->isEmpty()) {
            return $this->getCartData();
        }

        $cart = $this->getOrCreateCart();//LẤY GIỎ HÀNG USER HIỆN TẠI

        foreach ($favorites as $product) {
            // Xác định size mặc định rẻ nhất
            $defaultSize = ProductSize::query()
                ->where('product_id', $product->id)
                ->orderBy('price_adjustment', 'asc')
                ->first();

            $sizeName = $defaultSize ? $defaultSize->size_name : null;
            $unitPrice = $product->base_price + ($defaultSize ? $defaultSize->price_adjustment : 0);
            $sugarLevel = '100';
            $iceLevel = 'normal';

            // Kiểm tra xem sản phẩm mặc định này đã có trong giỏ chưa
            $existingItem = CartItem::query()
                ->where('cart_id', $cart->id)
                ->where('product_id', $product->id)
                ->where('size_name', $sizeName)
                ->where('sugar_level', $sugarLevel)
                ->where('ice_level', $iceLevel)
                ->first();

            // Chỉ gộp số lượng nếu ly nước trong giỏ đó không chứa topping
            $hasNoToppings = true;
            if ($existingItem) {
                $hasToppings = CartItemTopping::query()->where('cart_item_id', $existingItem->id)->exists();
                if ($hasToppings) {
                    $hasNoToppings = false;
                    $existingItem = null; // Tạo mới dòng nếu ly cũ trong giỏ có topping
                }
            }
            //NẾU CÓ SP GIỐNG + LUÔN
            if ($existingItem) {
                CartItem::query()
                    ->where('id', $existingItem->id)
                    ->update([
                        'quantity' => $existingItem->quantity + 1,
                        'updated_at' => now()
                    ]);
            } else {
                CartItem::query()->insert([
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

    // GHI NHẬN CÁC SP ĐƯỢC TÍCH CHỌN THANH TOÁN
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


    //TRANG THANH TOÁN
    public function checkout()
    {
        $userId = Auth::id();

        // Lấy danh sách địa chỉ của khách hàng (ưu tiên địa chỉ mặc định lên đầu)
        $addresses = UserAddress::query()
            ->where('user_id', $userId)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $cart = Cart::query()
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

                //tính lại giá gốc+size+topping, đồng bộ lại DB nếu giá đổi
                $items = $this->sv_cartPricing->pricedItems($cart, selectedIds: $selectedIds);
                foreach ($items as $item) {
                    $item->name = $item->product->name;
                    $item->image = $item->product->image;
                    $item->toppings = $item->calculated_toppings;
                }
                //cộng dồn giá × số lượng của tất cả item -> 1 số tổng
                $subtotal = $this->sv_cartPricing->subtotal($items);
            } catch (ValidationException $exception) {
                return redirect('/')->withErrors($exception->errors());
            }
        }

        // Trả về trang chủ kèm cảnh báo nếu giỏ thanh toán trống
        if ($items->isEmpty()) {
            return redirect('/')->with('warning', 'Giỏ hàng của bạn đang trống.');
        }

        // Xác định ngưỡng miễn phí vận chuyển theo hạng thành viên (Diamond được miễn phí hoàn toàn)
        $freeShipThreshold = (float) Setting::getValue('free_shipping_minimum', 150000);
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
        $receiveEnabled = (bool) Setting::getValue('orders_enabled', true);
        $isClosed = !$receiveEnabled;
        $closedReason = null;

        if ($isClosed) {
            $closedReason = 'Cửa hàng hiện đang tạm ngưng tiếp nhận đơn hàng mới. Quý khách vui lòng quay lại sau!';
        } else {
            // 2. Kiểm tra giờ hoạt động của cửa hàng (Hỗ trợ cấu hình chạy qua đêm)
            $open = Setting::getValue('store_open_time', '08:00');
            $close = Setting::getValue('store_close_time', '22:00');
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

        if (Setting::getValue('loyalty_point_value') != 1) {
            Setting::setValue('loyalty_point_value', '1', 'loyalty', 'decimal');
        }

        // Lấy danh sách mã khuyến mãi CÓ THỂ ÁP DỤNG cho đơn hàng này:
        // Bước 1 — lọc nhanh ở DB: active, kênh đúng, scope không phải combo
        // Bước 2 — gọi checkValidity() để lọc chính xác: hạng thành viên, đơn tối thiểu,
        //           đã dùng rồi chưa, khung giờ Happy Hour, số lượng tối thiểu...
        // Mã combo được ghép thêm ở bước 3 bên dưới vì còn phải xét giỏ có đủ tổ hợp món hay không.
        $totalQuantity = $items->sum('quantity');
        $now = now();
        $availablePromotions = Promotion::query()
            ->where('is_active', 1)
            ->where('requires_staff_verification', 0)
            ->whereNotIn('scope', ['combo', 'buy_x_get_y'])
            ->where(function ($q) {
                $q->where('applies_to', 'all')->orWhere('applies_to', 'delivery')->orWhereNull('applies_to');
            })
            ->where(function ($q) {
                $q->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit');
            })
            ->orderBy('created_at', 'desc')
            ->get(['id', 'code', 'description', 'type', 'value', 'max_discount_amount', 'scope',
                   'min_order_amount', 'min_quantity', 'apply_for', 'is_recurring', 'recurring_start_time', 'recurring_end_time',
                   'recurring_days', 'end_at', 'is_active', 'used_count', 'usage_limit', 'usage_limit_per_user', 'start_at'])
            ->filter(function ($promo) use ($user, $subtotal, $totalQuantity) {
                // KIỂM TRA MÃ CÓ HỢP LỆ K
                $result = $promo->checkValidity($user, $subtotal, 'delivery', $totalQuantity);
                return $result['valid'] === true;
            })
            ->values();

        // Bước 3 — ghép thêm mã combo mà giỏ hàng đã mua ĐỦ tổ hợp món, để khách bấm chọn như mọi mã
        // khác. Chưa đủ món thì không hiện, tránh khách bấm vào chỉ để nhận lỗi.
        $comboPromotions = $this->sv_promotions->applicableCombos($items, 'delivery')
            ->filter(fn($promo) => $promo->checkValidity($user, $subtotal, 'delivery', $totalQuantity)['valid'] === true);

        $availablePromotions = $availablePromotions->concat($comboPromotions)->values();

        return view('frontend.orders.checkout', compact('items', 'subtotal', 'addresses', 'isClosed', 'closedReason', 'freeShipThreshold', 'checkoutToken', 'availablePromotions'));
    }

   // TÍNH PHÍ KM
    public function calculateDistance(Request $request, GeoapifyService $geoapify)
    {
        $addressId = $request->query('address_id');
        $userId = Auth::id();

        $address = UserAddress::query()
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
                UserAddress::query()->where('id', $addressId)->update([
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

    ///dùng để lấy đúng danh sách sản phẩm (đã tính giá chính xác)
    private function pricedSelectedItems(): Collection
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
            return $this->sv_cartPricing->pricedItems($cart, selectedIds: $selectedIds);//lấy ds sản phâm được tính giá chính xác
        } catch (ValidationException) {
            return collect();
        }
    }


    //XÁC NHẬN TÍNH HỢP LỆ VÀ MỨC TIỀN ĐƯỢC TRỪ KHI ÁP DỤNG
    public function validateCoupon(Request $request)
    {
        $code = strtoupper(trim($request->input('coupon_code')));
        $items = $this->pricedSelectedItems();//dùng để lấy đúng danh sách sản phẩm
        if ($items->isEmpty()) {
            return response()->json(['valid' => false, 'message' => 'Giỏ hàng của bạn đang trống.']);
        }
        $subtotal = $this->sv_cartPricing->subtotal($items);//tính tổng số tiền của giỏ hàng trước khi áp dụng mã giảm giá.
        $totalQuantity = (int) $items->sum('quantity');

        $user = Auth::user();

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
            'min_order_amount' => $coupon->min_order_amount,
            'description' => $coupon->description,
            // end_at là cột dateTime thô, KHÔNG có cast Carbon trong Model -> phải tự parse chứ
            // không được gọi ->format() thẳng lên chuỗi.
            'end_at' => $coupon->end_at ? Carbon::parse($coupon->end_at)->format('d/m/Y H:i') : null,
            'scope' => $coupon->scope,
            // Mô tả phạm vi áp dụng cho sản phẩm hoặc danh mục cụ thể để hiển thị trực quan lên giao diện
            'scope_label' => match ($coupon->scope) {
                'product' => 'Áp dụng cho: ' . $coupon->products->pluck('name')->implode(', '),
                'category' => 'Áp dụng cho danh mục: ' . $coupon->categories->pluck('name')->implode(', '),
                'combo' => 'Combo: mua ' . $coupon->comboItems->map(fn($ci) => $ci->quantity . ' ' . ($ci->product->name ?? ''))->implode(' + '),
                default => null,
            },
            // Quà tặng kèm của mã combo — chỉ mã combo mới có, JS dùng để vẽ/xóa dòng quà khi đổi mã
            'gifts' => collect($result['gifts'] ?? [])->map(fn($g) => [
                'name' => $g['gift_product']->name,
                'quantity' => $g['granted_quantity'],
            ])->values(),
        ]);
    }

   // tính phí thời tiết
    public function calculateWeatherFee(Request $request)
    {
        $address = UserAddress::query()
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
            default => (float) Setting::getValue('free_shipping_minimum', 150000),
        };

        // Tính phí vận chuyển cơ bản dựa theo khoảng cách km
        $baseFee = (float) Setting::getValue('shipping_base_fee', 15000);
        $feePerKm = (float) Setting::getValue('shipping_fee_per_km', 5000);
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
