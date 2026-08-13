<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Promotion;
use App\Models\User;
use App\Models\UserAddress;
// Nạp công cụ tương tác Database (Chạy transaction,
use Illuminate\Support\Facades\DB;
// Nạp ngoại lệ ValidationException để ném lỗi dữ liệu
use Illuminate\Validation\ValidationException;

// "Dịch vụ xử lý đơn hàng".
class OrderService
{
 
    public function __construct(
        private readonly CartPricingService $cartPricing,
        private readonly ShippingQuoteService $shipping,
        private readonly PromotionService $promotions,
    ) {
    }

  // tao mới đơn hàng trong hệ thống (bao gồm kiểm tra cửa
    public function create(User $user, array $payload, string $paymentMethod): Order
    {
        // Kiểm tra Idempotency Key: Đề phòng mạng lag khách bấm
        $existing = Order::withTrashed()->where('user_id', $user->id) // Tìm đơn trùng lặp bằng idempotency_key
            ->where('idempotency_key', $payload['idempotency_key'])->first();
        if ($existing) {
            return $existing;
        }

        // Đơn tự đến lấy tại quầy (pickup) thì không cần tính
        $isPickup = ($payload['delivery_type'] ?? 'delivery') === 'pickup';

        // Xác định "khách hàng đứng tên đơn hàng" (orderOwner):
        // Nếu khách tự đặt hàng online, 'customer_id' không có và hệ thống mặc định lấy tài khoản của chính $user hiện tại.
        $hasExplicitOrderOwner = array_key_exists('customer_id', $payload);
        if ($hasExplicitOrderOwner) {
            $orderOwnerId = $payload['customer_id'];
        } else {
            $orderOwnerId = $user->id;
        }
        if ($orderOwnerId === $user->id) {
            $orderOwner = $user;
        } elseif ($orderOwnerId) {
            // Role='customer' đã được validate ở nơi gọi (vd Reception/OrderController::storeOrder()
            // dùng
            $orderOwner = User::query()->find($orderOwnerId); // Tìm tài khoản khách hàng trong Database
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
            $address = UserAddress::query()->where('user_id', $orderOwner->id)->find($payload['address_id'] ?? null); // Lấy thông tin địa chỉ giao hàng của khách
            if (!$address) {
                throw ValidationException::withMessages(['address_id' => 'Địa chỉ giao hàng không hợp lệ.']);
            }
        }

        // 1. Kiểm tra trạng thái hoạt động: Cửa hàng có đang mở
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
        $cart = Cart::query()->where('user_id', $user->id)->first(); // Tìm giỏ hàng hiện có của tài khoản đang đăng nhập
        if (!$cart) {
            throw ValidationException::withMessages(['cart' => 'Giỏ hàng của bạn đang trống.']);
        }

        if (isset($payload['selected_item_ids']) && is_array($payload['selected_item_ids']) && count($payload['selected_item_ids']) > 0) {
            $selectedIds = array_map('intval', $payload['selected_item_ids']);
        } else {
            $selectedIds = null;
        }

        // Tính toán trước giá tiền giỏ hàng (giá trị thô) để
        $previewItems = $this->cartPricing->pricedItems($cart, selectedIds: $selectedIds); // Gọi CartPricingService tính giá sản phẩm
        $previewSubtotal = $this->cartPricing->subtotal($previewItems); // Gọi CartPricingService tính tổng tiền tạm tính thô

        if ($isPickup) {
            $quote = ['shipping_fee' => 0, 'weather_fee' => 0, 'distance_km' => null];
        } else {
            // Tính toán khoảng cách và phí ship dựa vào địa chỉ của
            $quote = $this->shipping->quote($address, $previewSubtotal, $orderOwner); // Gọi ShippingQuoteService tính toán khoảng cách và chi phí ship thực tế

            // Kiểm tra xem khoảng cách giao hàng có vượt quá giới
            $maxDistance = (float) \App\Models\Setting::getValue('shipping_max_distance_km', ShippingQuoteService::MAX_DELIVERY_KM);
            if ($quote['distance_km'] > $maxDistance) {
                throw ValidationException::withMessages(['address_id' => "Địa chỉ vượt quá phạm vi giao hàng {$maxDistance} km."]);
            }
        }

        // Bắt đầu một DB Transaction để đảm bảo tính an toàn dữ
        return DB::transaction(function () use ($user, $payload, $paymentMethod, $address, $cart, $previewSubtotal, $quote, $isPickup, $selectedIds, $orderOwner, $orderOwnerId) {
            // Khóa giỏ hàng và nạp giá chính thức bên trong Transaction
            $lockedCart = Cart::query()->whereKey($cart->id)->where('user_id', $user->id)->lockForUpdate()->firstOrFail(); // Khóa dòng giỏ hàng trong DB
            $items = $this->cartPricing->pricedItems($lockedCart, true, $selectedIds); // Gọi CartPricingService lấy giá chi tiết chính xác của từng món
            $subtotal = $this->cartPricing->subtotal($items); // Gọi CartPricingService tính tổng giá hàng chính thức
            $totalQuantity = (int) $items->sum('quantity');

            // Đề phòng trường hợp giá sản phẩm/topping bị Admin thay
            if (abs($subtotal - $previewSubtotal) > 0.01) {
                throw ValidationException::withMessages(['cart' => 'Giá giỏ hàng vừa thay đổi, vui lòng kiểm tra lại.']);
            }

            // Tính toán giảm giá từ điểm tích lũy quy đổi
            $pointsToRedeem = (int) ($payload['points_to_redeem'] ?? 0);
            $pointsDiscount = $this->resolvePointsDiscount($pointsToRedeem, $orderOwner, $subtotal); // Gọi hàm nội bộ để tính số tiền giảm khi đổi điểm tích lũy

            // Xác định kênh bán hàng (Pickup tại quầy hoặc Delivery
            if ($isPickup) {
                $channel = 'pickup';
            } else {
                $channel = 'delivery';
            }

            $tempCoupon = $payload['coupon_code'] ?? null;
            if (filled($tempCoupon)) {
                $manualCode = $payload['coupon_code'];
            } else {
                $manualCode = null;
            }
            // Không nhập mã thì đơn KHÔNG có khuyến mãi — kể cả đơn
            if ($manualCode === null) {
                $promotion = null;
                $autoResult = ['promotion' => null, 'discount' => 0.0, 'gifts' => []];
            } else {
                $autoResult = $this->promotions->resolveBestDiscount( // Gọi PromotionService để tìm mã giảm giá tốt nhất phù hợp cho đơn
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

            // Mỗi đơn chỉ giữ đúng 1 mã. Nếu mã đó là mã combo thì
            // kết quả trả về; các loại mã khác luôn có 'gifts' rỗng.
            $couponDiscount = $autoResult['discount'];
            $giftEntries = $autoResult['gifts'] ?? [];

            // Tính toán giảm giá theo hạng thành viên (Silver, Gold,
            $membershipDiscount = $this->membershipDiscount($orderOwner, $subtotal); // Gọi hàm nội bộ để tính mức giảm giá tri ân theo thứ hạng thành viên

            // Tổng hợp các khoản giảm giá (đảm bảo số tiền giảm tối
            $discount = min($subtotal, $couponDiscount + $membershipDiscount + $pointsDiscount);
            // Số tiền thanh toán cuối cùng = Tổng tiền sản phẩm + Phí ship + Phí thời tiết - Số tiền giảm giá
            $finalAmount = max(0, $subtotal + $quote['shipping_fee'] + $quote['weather_fee'] - $discount);

            // Sinh mã đơn hàng ngẫu nhiên không trùng lặp (dạng
            do {
                $orderCode = 'HPY-' . strtoupper(bin2hex(random_bytes(4)));
            } while (Order::withTrashed()->where('order_code', $orderCode)->exists());

            // Tách các giá trị cần truyền vào câu lệnh tạo đơn hàng thành các khối if-else dễ hiểu:

            // Xác định ai là người tạo đơn (Lễ tân/Admin tạo hộ hoặc
            if (in_array($user->role, ['staff', 'admin'], true)) {
                $createdBy = $user->id;
            } else {
                $createdBy = null;
            }

            // Xác định tên khách nhận hàng
            if ($isPickup) {
                if (array_key_exists('customer_name', $payload)) {
                    $customerName = $payload['customer_name'];
                } else {
                    $customerName = $user->name;
                }
            } else {
                $customerName = $address->fullname;
            }

            // Xác định số điện thoại khách nhận hàng
            if ($isPickup) {
                if (array_key_exists('customer_phone', $payload)) {
                    $customerPhone = $payload['customer_phone'];
                } else {
                    $customerPhone = $user->phone;
                }
            } else {
                $customerPhone = $address->phone;
            }

            // Xác định địa chỉ giao hàng, kinh độ, vĩ độ
            if ($isPickup) {
                $deliveryAddress = null;
                $deliveryLatitude = null;
                $deliveryLongitude = null;
            } else {
                $deliveryAddress = implode(', ', array_filter([$address->specific_address, $address->ward, $address->district, $address->province]));
                $deliveryLatitude = $address->latitude;
                $deliveryLongitude = $address->longitude;
            }

            // Xác định mã giảm giá
            if ($promotion) {
                $couponCode = $promotion->code;
                $promotionId = $promotion->id;
            } else {
                $couponCode = null;
                $promotionId = null;
            }

            // Xác định hình thức đơn hàng và cách lấy hàng
            if ($isPickup) {
                $deliveryType = 'pickup';
                $pickupMode = $payload['pickup_mode'] ?? 'dine_in';
                $estimatedTime = now()->addMinutes(15); // Đơn tại quầy hoàn thành trong 15 phút
            } else {
                $deliveryType = 'delivery';
                $pickupMode = null;
                $estimatedTime = now()->addMinutes(45); // Đơn ship hoàn thành trong 45 phút
            }

            // 1. Lưu bản ghi đơn hàng mới (Order) vào CSDL
            $order = Order::create([ // Lưu thông tin chung đơn hàng vào Database
                'order_code' => $orderCode,
                'idempotency_key' => $payload['idempotency_key'],
                'user_id' => $orderOwnerId,
                'created_by' => $createdBy,
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'delivery_address' => $deliveryAddress,
                'delivery_latitude' => $deliveryLatitude,
                'delivery_longitude' => $deliveryLongitude,
                'total_amount' => $subtotal,
                'discount_amount' => $discount,
                'final_amount' => $finalAmount,
                'payment_status' => 'unpaid',
                'payment_method' => $paymentMethod,
                'status' => 'pending',
                'coupon_code' => $couponCode,
                'promotion_id' => $promotionId,
                'delivery_type' => $deliveryType,
                'pickup_mode' => $pickupMode,
                'estimated_time' => $estimatedTime,
                'distance_km' => $quote['distance_km'],
                'weather_fee' => $quote['weather_fee'],
                'peak_hour_fee' => 0,
                'shipping_fee' => $quote['shipping_fee'],
                'customer_note' => $payload['note'] ?? null,
                'points_redeemed' => $pointsToRedeem,
            ]);

            // 2. Tạo chi tiết đơn hàng (OrderItem) cho các món nước
            foreach ($items as $item) {
                OrderItem::create([ // Lưu thông tin từng món nước mua của đơn hàng vào bảng order_items
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

            // 3. Tạo chi tiết đơn hàng cho các sản phẩm quà tặng của mã combo khách đã áp (giá bán = 0đ)
            foreach ($giftEntries as $entry) {
                $comboItemNames = $entry['combo_items']->map(fn($ci) => $ci->quantity . ' ' . $ci->product->name)->implode(' + ');
                OrderItem::create([ // Lưu thông tin quà tặng combo với đơn giá 0đ
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

            // 4. Dọn dẹp giỏ hàng: Xóa các sản phẩm đã được đưa vào
            $itemIds = $items->pluck('id');
            DB::table('cart_item_toppings')->whereIn('cart_item_id', $itemIds)->delete();
            DB::table('cart_items')->whereIn('id', $itemIds)->delete();

            // Tăng số lượt đã dùng của mã khách áp (mã combo cũng
            if ($promotion) {
                $promotion->increment('used_count');
            }

            // 5. Trừ điểm tích lũy của khách hàng thành viên nếu
            if ($pointsToRedeem > 0) {
                $lockedOwner = User::query()->lockForUpdate()->findOrFail($orderOwner->id); // Khóa dòng user trong DB
                $lockedOwner->points = max(0, $lockedOwner->points - $pointsToRedeem);
                $lockedOwner->save(); // Cập nhật lại số dư điểm của khách
            }

            return $order->fresh('items');
        }, 3); // Thử lại tối đa 3 lần nếu có tranh chấp khóa (deadlock)
    }

   // Xem trước số tiền giảm khi lễ tân nhập thủ công một mã
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

   // Xem trước số tiền giảm giá khi quy đổi điểm tích lũy
    public function previewPointsDiscount(int $pointsToRedeem, ?User $orderOwner, float $subtotal): array
    {
        try {
            // Thực hiện tính toán và kiểm tra thông qua hàm
            return ['discount' => $this->resolvePointsDiscount($pointsToRedeem, $orderOwner, $subtotal), 'error' => null];
        } catch (ValidationException $e) {
            // Nếu không đủ điều kiện (ví dụ: vượt hạn mức, chưa đủ
            return ['discount' => 0, 'error' => collect($e->errors())->flatten()->first()];
        }
    }

    // Kiểm tra tính hợp lệ và quy đổi điểm tích lũy sang
    private function resolvePointsDiscount(int $pointsToRedeem, ?User $orderOwner, float $subtotal): float
    {
        if ($pointsToRedeem <= 0) {
            return 0;
        }

        if (!$orderOwner) {
            throw ValidationException::withMessages(['points_to_redeem' => 'Không thể dùng điểm tích lũy cho khách vãng lai.']);
        }

        // Kiểm tra xem chương trình tích điểm đổi quà có đang
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

    // Tính số tiền giảm giá tri ân theo cấp hạng thành viên
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
