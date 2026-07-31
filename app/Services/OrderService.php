<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Promotion;
use App\Models\User;
use App\Models\UserAddress;
// Nạp công cụ tương tác Database (Chạy transaction, query trực tiếp)
use Illuminate\Support\Facades\DB;
// Nạp ngoại lệ ValidationException để ném lỗi dữ liệu đầu vào không hợp lệ về phía Client
use Illuminate\Validation\ValidationException;

//"Dịch vụ xử lý đơn hàng".
class OrderService
{
    /**
     * Hàm khởi tạo (Constructor) nạp các Service liên quan:
     * - CartPricingService: Dịch vụ tính giá sản phẩm trong giỏ hàng.
     * - ShippingQuoteService: Dịch vụ tính khoảng cách và phí vận chuyển.
     * - PromotionService: Dịch vụ xử lý khuyến mãi và combo quà tặng.
     */
    public function __construct(
        private readonly CartPricingService $cartPricing,
        private readonly ShippingQuoteService $shipping,
        private readonly PromotionService $promotions,
    ) {
    }

    /**
     * public: Cho phép gọi từ bên ngoài (ví dụ: các Controller thanh toán).
     * create(...): Tạo mới đơn hàng trong hệ thống (bao gồm kiểm tra cửa hàng, giỏ hàng, phí ship, khuyến mãi, điểm tích lũy, trừ giỏ hàng và lưu DB).
     * 
     * Các tham số:
     * - User $user: Người thực hiện thao tác checkout (nhân viên lễ tân tại quầy hoặc khách mua hàng trực tuyến).
     * - array $payload: Mảng chứa dữ liệu khách gửi lên (loại đơn, địa chỉ, mã giảm giá, mã idempotency_key, điểm quy đổi...).
     * - string $paymentMethod: Phương thức thanh toán (COD, MoMo, VNPay, tiền mặt...).
     */
    public function create(User $user, array $payload, string $paymentMethod): Order
    {
        // Kiểm tra Idempotency Key: Đề phòng mạng lag khách bấm nút "Đặt hàng" 2 lần liên tục,
        // hệ thống kiểm tra nếu đã tạo đơn với Key này rồi thì trả về luôn đơn đó, tránh tạo đơn trùng lặp.
        $existing = Order::withTrashed()->where('user_id', $user->id)
            ->where('idempotency_key', $payload['idempotency_key'])->first();
        if ($existing) {
            return $existing;
        }

        // Đơn tự đến lấy tại quầy (pickup) thì không cần tính phí ship và không cần địa chỉ giao hàng
        $isPickup = ($payload['delivery_type'] ?? 'delivery') === 'pickup';

        // Xác định "khách hàng đứng tên đơn hàng" (orderOwner):
        // Nếu nhân viên lễ tân tạo đơn tại quầy POS hộ khách, payload sẽ gửi 'customer_id' của khách.
        // Nếu khách tự đặt hàng online, 'customer_id' không có và hệ thống mặc định lấy tài khoản của chính $user hiện tại.
        $hasExplicitOrderOwner = array_key_exists('customer_id', $payload);
        $orderOwnerId = $hasExplicitOrderOwner ? $payload['customer_id'] : $user->id;
        if ($orderOwnerId === $user->id) {
            $orderOwner = $user;
        } elseif ($orderOwnerId) {
            // Role='customer' đã được validate ở nơi gọi (vd Reception/OrderController::storeOrder()
            // dùng Rule::exists('users','id')->where('role','customer')) — ở đây chỉ cần xác nhận ID tồn tại.
            $orderOwner = User::query()->find($orderOwnerId);
            if (!$orderOwner) {
                throw ValidationException::withMessages(['customer_id' => 'Khách hàng không hợp lệ.']);
            }
        } else {
            $orderOwner = null; // Khách vãng lai mua tại quầy không có tài khoản thành viên
        }

        // Xác định địa chỉ giao hàng (nếu là đơn vận chuyển)
        $address = null;
        if (!$isPickup) {
            if (!$orderOwner) {
                throw ValidationException::withMessages(['address_id' => 'Đơn giao hàng cần gắn với một khách hàng có tài khoản để lấy địa chỉ.']);
            }
            $address = UserAddress::query()->where('user_id', $orderOwner->id)->find($payload['address_id'] ?? null);
            if (!$address) {
                throw ValidationException::withMessages(['address_id' => 'Địa chỉ giao hàng không hợp lệ.']);
            }
        }

        // 1. Kiểm tra trạng thái hoạt động: Cửa hàng có đang mở cửa nhận đơn không
        $receiveEnabled = (bool) \App\Models\Setting::getValue('orders_enabled', true);
        if (!$receiveEnabled) {
            throw ValidationException::withMessages(['cart' => 'Cửa hàng hiện đang tạm ngưng nhận đơn hàng.']);
        }

        // 2. Kiểm tra giờ đóng/mở cửa của quán
        $open = \App\Models\Setting::getValue('store_open_time', '08:00');
        $close = \App\Models\Setting::getValue('store_close_time', '22:00');
        $now = now()->format('H:i');

        $isOpen = false;
        if ($open < $close) {
            $isOpen = ($now >= $open && $now <= $close);
        } else { // Khung giờ hoạt động xuyên đêm (Ví dụ: từ 22:00 đến 03:00 sáng hôm sau)
            $isOpen = ($now >= $open || $now <= $close);
        }

        if (!$isOpen) {
            throw ValidationException::withMessages(['cart' => "Cửa hàng đã đóng cửa. Thời gian hoạt động: từ {$open} đến {$close}."]);
        }

        // Lấy thông tin giỏ hàng hiện tại của người dùng
        $cart = Cart::query()->where('user_id', $user->id)->first();
        if (!$cart) {
            throw ValidationException::withMessages(['cart' => 'Giỏ hàng của bạn đang trống.']);
        }

        // Lấy danh sách ID sản phẩm đã chọn từ payload (nếu có, dùng để thanh toán từng phần giỏ hàng)
        $selectedIds = isset($payload['selected_item_ids']) && is_array($payload['selected_item_ids']) && count($payload['selected_item_ids']) > 0
            ? array_map('intval', $payload['selected_item_ids'])
            : null;

        // Tính toán trước giá tiền giỏ hàng (giá trị thô) để phục vụ tính toán phí ship và kiểm tra khoảng cách
        $previewItems = $this->cartPricing->pricedItems($cart, selectedIds: $selectedIds);
        $previewSubtotal = $this->cartPricing->subtotal($previewItems);

        if ($isPickup) {
            $quote = ['shipping_fee' => 0, 'weather_fee' => 0, 'distance_km' => null];
        } else {
            // Tính toán khoảng cách và phí ship dựa vào địa chỉ của khách và tổng tiền (hạng thành viên có thể được freeship)
            $quote = $this->shipping->quote($address, $previewSubtotal, $orderOwner);

            // Kiểm tra xem khoảng cách giao hàng có vượt quá giới hạn cấu hình cho phép hay không
            $maxDistance = (float) \App\Models\Setting::getValue('shipping_max_distance_km', ShippingQuoteService::MAX_DELIVERY_KM);
            if ($quote['distance_km'] > $maxDistance) {
                throw ValidationException::withMessages(['address_id' => "Địa chỉ vượt quá phạm vi giao hàng {$maxDistance} km."]);
            }
        }

        // Bắt đầu một DB Transaction để đảm bảo tính an toàn dữ liệu khi tạo đơn hàng (cho phép thử lại tối đa 3 lần nếu có lỗi nghẽn deadlock)
        return DB::transaction(function () use ($user, $payload, $paymentMethod, $address, $cart, $previewSubtotal, $quote, $isPickup, $selectedIds, $orderOwner, $orderOwnerId) {
            // Khóa giỏ hàng và nạp giá chính thức bên trong Transaction
            $lockedCart = Cart::query()->whereKey($cart->id)->where('user_id', $user->id)->lockForUpdate()->firstOrFail();
            $items = $this->cartPricing->pricedItems($lockedCart, true, $selectedIds);
            $subtotal = $this->cartPricing->subtotal($items);
            $totalQuantity = (int) $items->sum('quantity');

            // Đề phòng trường hợp giá sản phẩm/topping bị Admin thay đổi ngay lúc khách đang xem trang thanh toán
            if (abs($subtotal - $previewSubtotal) > 0.01) {
                throw ValidationException::withMessages(['cart' => 'Giá giỏ hàng vừa thay đổi, vui lòng kiểm tra lại.']);
            }

            // Tính toán giảm giá từ điểm tích lũy quy đổi
            $pointsToRedeem = (int) ($payload['points_to_redeem'] ?? 0);
            $pointsDiscount = $this->resolvePointsDiscount($pointsToRedeem, $orderOwner, $subtotal);

            // Tính toán giảm giá từ mã Khuyến mãi/Mã giảm giá (Coupon)
            $channel = $isPickup ? 'pickup' : 'delivery';
            $manualCode = filled($payload['coupon_code'] ?? null) ? $payload['coupon_code'] : null;
            if ($manualCode === null && !$isPickup) {
                $promotion = null;
                $autoResult = ['promotion' => null, 'discount' => 0.0];
            } else {
                $autoResult = $this->promotions->resolveBestDiscount(
                    $items,
                    $subtotal,
                    $orderOwner,
                    $channel,
                    $totalQuantity,
                    $manualCode,
                    lock: true
                );
                $promotion = $autoResult['promotion'];
            }

            // Tính toán quà tặng và giảm giá đi kèm từ chương trình Combo
            $comboResult = $this->promotions->resolveComboRewards($items, $channel, $autoResult);
            $comboEntries = $comboResult['entries'];
            $couponDiscount = $comboResult['auto_discount'];
            $comboDiscountTotal = collect($comboEntries)->where('type', 'discount')->sum('discount_amount');

            // Tính toán giảm giá theo hạng thành viên (Silver, Gold, Diamond)
            $membershipDiscount = $this->membershipDiscount($orderOwner, $subtotal);

            // Tổng hợp các khoản giảm giá (đảm bảo số tiền giảm tối đa không vượt quá giá trị tạm tính của đơn)
            $discount = min($subtotal, $couponDiscount + $comboDiscountTotal + $membershipDiscount + $pointsDiscount);
            // Số tiền thanh toán cuối cùng = Tổng tiền sản phẩm + Phí ship + Phí thời tiết - Số tiền giảm giá
            $finalAmount = max(0, $subtotal + $quote['shipping_fee'] + $quote['weather_fee'] - $discount);

            // Sinh mã đơn hàng ngẫu nhiên không trùng lặp (dạng HPY-XXXXXXXX)
            do {
                $orderCode = 'HPY-' . strtoupper(bin2hex(random_bytes(4)));
            } while (Order::withTrashed()->where('order_code', $orderCode)->exists());

            // 1. Lưu bản ghi đơn hàng mới (Order) vào CSDL
            $order = Order::create([
                'order_code' => $orderCode,
                'idempotency_key' => $payload['idempotency_key'],
                'user_id' => $orderOwnerId,
                'created_by' => in_array($user->role, ['staff', 'admin'], true) ? $user->id : null, // Ghi nhận nhân viên tạo đơn hộ nếu dùng POS tại quầy
                'customer_name' => $isPickup ? (array_key_exists('customer_name', $payload) ? $payload['customer_name'] : $user->name) : $address->fullname,
                'customer_phone' => $isPickup ? (array_key_exists('customer_phone', $payload) ? $payload['customer_phone'] : $user->phone) : $address->phone,
                'delivery_address' => $isPickup ? null : implode(', ', array_filter([$address->specific_address, $address->ward, $address->district, $address->province])),
                'delivery_latitude' => $isPickup ? null : $address->latitude,
                'delivery_longitude' => $isPickup ? null : $address->longitude,
                'total_amount' => $subtotal,
                'discount_amount' => $discount,
                'final_amount' => $finalAmount,
                'payment_status' => 'unpaid',
                'payment_method' => $paymentMethod,
                'status' => 'pending',
                'coupon_code' => $promotion?->code,
                'promotion_id' => $promotion?->id,
                'delivery_type' => $isPickup ? 'pickup' : 'delivery',
                'pickup_mode' => $isPickup ? ($payload['pickup_mode'] ?? 'dine_in') : null,
                'estimated_time' => now()->addMinutes($isPickup ? 15 : 45),
                'distance_km' => $quote['distance_km'],
                'weather_fee' => $quote['weather_fee'],
                'peak_hour_fee' => 0,
                'shipping_fee' => $quote['shipping_fee'],
                'customer_note' => $payload['note'] ?? null,
                'points_redeemed' => $pointsToRedeem,
            ]);

            // 2. Tạo chi tiết đơn hàng (OrderItem) cho các món nước khách mua
            foreach ($items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'product_sku' => $item->product->sku,
                    'product_image' => $item->product->image,
                    'size_name' => $item->size_name,
                    'quantity' => (int) $item->quantity,
                    'unit_price' => $item->calculated_unit_price,
                    'sugar_level' => $item->sugar_level,
                    'ice_level' => $item->ice_level,
                    'options' => $item->calculated_toppings->pluck('name')->values()->all(),
                    'note' => null,
                ]);
            }

            // 3. Tạo chi tiết đơn hàng cho các sản phẩm quà tặng được tặng kèm theo chương trình Combo (giá bán = 0đ)
            foreach ($comboEntries as $entry) {
                if ($entry['type'] !== 'gift') {
                    continue;
                }
                $comboItemNames = $entry['combo_items']->map(fn($ci) => $ci->quantity . ' ' . $ci->product->name)->implode(' + ');
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $entry['gift_product']->id,
                    'product_name' => $entry['gift_product']->name,
                    'product_sku' => $entry['gift_product']->sku,
                    'product_image' => $entry['gift_product']->image,
                    'size_name' => null,
                    'quantity' => $entry['granted_quantity'],
                    'unit_price' => 0, // Hàng tặng đơn giá 0đ
                    'sugar_level' => null,
                    'ice_level' => null,
                    'options' => [],
                    'note' => 'Quà tặng combo: Mua ' . $comboItemNames . ' tặng ' . $entry['granted_quantity'] . ' ' . $entry['gift_product']->name,
                    'is_gift' => true,
                    'source_promotion_id' => $entry['promotion']->id,
                ]);
            }

            // 4. Dọn dẹp giỏ hàng: Xóa các sản phẩm đã được đưa vào hóa đơn khỏi giỏ hàng của người dùng
            $itemIds = $items->pluck('id');
            DB::table('cart_item_toppings')->whereIn('cart_item_id', $itemIds)->delete();
            DB::table('cart_items')->whereIn('id', $itemIds)->delete();

            // Tăng số lượng đã dùng của mã giảm giá và chương trình combo
            if ($promotion) {
                $promotion->increment('used_count');
            }
            foreach (collect($comboEntries)->pluck('promotion')->unique('id') as $comboPromotion) {
                $comboPromotion->increment('used_count');
            }

            // 5. Trừ điểm tích lũy của khách hàng thành viên nếu khách chọn quy đổi điểm sang tiền giảm giá
            if ($pointsToRedeem > 0) {
                $lockedOwner = User::query()->lockForUpdate()->findOrFail($orderOwner->id);
                $lockedOwner->points = max(0, $lockedOwner->points - $pointsToRedeem);
                $lockedOwner->save();
            }

            return $order->fresh('items');
        }, 3); // Thử lại tối đa 3 lần nếu có tranh chấp khóa (deadlock)
    }

    /**
     * public: Cho phép gọi từ bên ngoài.
     * previewAutoPromotion(...): Xem trước khuyến mãi tự động sẽ áp dụng cho một đơn tại quầy (dùng cho giao diện POS hiển thị trước tổng tiền).
     * 
     * Các tham số:
     * - Collection $items: Danh sách sản phẩm trong giỏ đã định giá.
     * - float $subtotal: Tổng tiền tạm tính thô.
     * - int $totalQuantity: Tổng số lượng ly nước trong đơn.
     * 
     * Trả về kiểu dữ liệu: array (Mảng thông tin khuyến mãi tự động tối ưu nhất và số tiền giảm giá).
     */
    public function previewAutoPromotion(\Illuminate\Support\Collection $items, float $subtotal, int $totalQuantity = 0): array
    {
        return $this->promotions->resolveBestDiscount($items, $subtotal, null, 'pickup', $totalQuantity);
    }

    /**
     * public: Cho phép gọi từ bên ngoài.
     * previewManualCoupon(...): Xem trước số tiền giảm khi lễ tân nhập thủ công một mã coupon giảm giá trên POS.
     * 
     * Các tham số:
     * - string $code: Mã giảm giá do khách cung cấp.
     * - Collection $items: Danh sách sản phẩm.
     * - ?User $orderOwner: Khách đứng tên đơn.
     * - float $subtotal: Tổng tiền thô.
     * - ?string $deliveryType: Kiểu giao hàng (tại quầy 'pickup' hoặc giao đi 'delivery').
     * - int $totalQuantity: Tổng số lượng ly nước.
     */
    public function previewManualCoupon(
        string $code,
        \Illuminate\Support\Collection $items,
        ?User $orderOwner,
        float $subtotal,
        ?string $deliveryType = null,
        int $totalQuantity = 0
    ): array {
        return $this->promotions->resolveBestDiscount(
            $items,
            $subtotal,
            $orderOwner,
            $deliveryType ?? 'pickup',
            $totalQuantity,
            $code,
            lock: true
        );
    }

    /**
     * public: Cho phép gọi từ bên ngoài.
     * previewPointsDiscount(...): Xem trước số tiền giảm giá khi quy đổi điểm tích lũy của khách hàng thành viên.
     * 
     * Các tham số:
     * - int $pointsToRedeem: Số điểm muốn quy đổi.
     * - ?User $orderOwner: Tài khoản khách hàng thành viên.
     * - float $subtotal: Tổng tiền tạm tính.
     */
    public function previewPointsDiscount(int $pointsToRedeem, ?User $orderOwner, float $subtotal): array
    {
        try {
            // Thực hiện tính toán và kiểm tra thông qua hàm resolvePointsDiscount
            return ['discount' => $this->resolvePointsDiscount($pointsToRedeem, $orderOwner, $subtotal), 'error' => null];
        } catch (ValidationException $e) {
            // Nếu không đủ điều kiện (ví dụ: vượt hạn mức, chưa đủ điểm tối thiểu), bắt lỗi và trả về lỗi dạng chuỗi để hiển thị lên POS
            return ['discount' => 0, 'error' => collect($e->errors())->flatten()->first()];
        }
    }

    /**
     * private: Chỉ dùng nội bộ trong class này.
     * resolvePointsDiscount(...): Kiểm tra tính hợp lệ và quy đổi điểm tích lũy sang tiền giảm giá.
     * 
     * Quy tắc quy đổi:
     * - Phải có tài khoản thành viên (không áp dụng cho khách vãng lai).
     * - Chương trình quy đổi phải đang hoạt động (`loyalty_enabled = 1`).
     * - Số điểm muốn quy đổi phải lớn hơn hoặc bằng mức tối thiểu (`loyalty_min_points_to_redeem`).
     * - Số điểm muốn đổi không được lớn hơn số dư tài khoản của khách.
     * - Số tiền giảm quy đổi không được vượt quá % trần giá trị hóa đơn cho phép (`loyalty_max_redeem_percent`).
     */
    private function resolvePointsDiscount(int $pointsToRedeem, ?User $orderOwner, float $subtotal): float
    {
        if ($pointsToRedeem <= 0) {
            return 0;
        }

        if (!$orderOwner) {
            throw ValidationException::withMessages(['points_to_redeem' => 'Không thể dùng điểm tích lũy cho khách vãng lai.']);
        }

        // Kiểm tra xem chương trình tích điểm đổi quà có đang bật hoạt động không
        $loyaltyEnabled = \App\Models\Setting::getValue('loyalty_enabled', '1') == '1';
        if (!$loyaltyEnabled) {
            throw ValidationException::withMessages(['points_to_redeem' => 'Chương trình tích điểm hiện đang tạm đóng.']);
        }

        // Kiểm tra số điểm tối thiểu mỗi lần quy đổi
        $minPointsToRedeem = (int) \App\Models\Setting::getValue('loyalty_min_points_to_redeem', 10);
        if ($pointsToRedeem < $minPointsToRedeem) {
            throw ValidationException::withMessages(['points_to_redeem' => "Số điểm tối thiểu để được quy đổi là {$minPointsToRedeem}."]);
        }

        // Kiểm tra số dư điểm của khách hàng
        $pointsBalance = (int) $orderOwner->points;
        if ($pointsToRedeem > $pointsBalance) {
            throw ValidationException::withMessages(['points_to_redeem' => 'Số điểm quy đổi vượt quá số dư hiện có.']);
        }

        // Tính số tiền giảm giá tối đa cho phép dựa trên tỷ lệ trần
        $maxRedeemPercent = (float) \App\Models\Setting::getValue('loyalty_max_redeem_percent', 100);
        $maxDiscountMoney = $subtotal * ($maxRedeemPercent / 100);

        // Quy đổi điểm thành tiền: số tiền giảm = số điểm * tỷ giá quy đổi
        $pointValue = (float) \App\Models\Setting::getValue('loyalty_point_value', 1);
        $pointsDiscount = $pointsToRedeem * $pointValue;

        // Nếu số tiền giảm vượt mức quy định, chặn lại báo lỗi
        if ($pointsDiscount > $maxDiscountMoney) {
            throw ValidationException::withMessages(['points_to_redeem' => "Số điểm quy đổi vượt quá giới hạn tối đa ({$maxRedeemPercent}%) giá trị đơn hàng."]);
        }

        return $pointsDiscount;
    }

    /**
     * public: Cho phép gọi từ bên ngoài.
     * membershipDiscount(?User $orderOwner, float $subtotal): Tính số tiền giảm giá tri ân theo cấp hạng thành viên của khách.
     * 
     * Các hạng thành viên:
     * - Hạng Bạc (silver): Giảm 2% tổng tiền sản phẩm.
     * - Hạng Vàng (gold): Giảm 5% tổng tiền sản phẩm.
     * - Hạng Kim Cương (diamond): Giảm 10% tổng tiền sản phẩm.
     */
    public function membershipDiscount(?User $orderOwner, float $subtotal): float
    {
        return match ($orderOwner?->membership_level) {
            'silver' => round($subtotal * 0.02),
            'gold' => round($subtotal * 0.05),
            'diamond' => round($subtotal * 0.10),
            default => 0,
        };
    }
}
