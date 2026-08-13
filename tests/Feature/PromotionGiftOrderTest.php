<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\PromotionCombo;
use App\Models\PromotionComboItem;
use App\Models\User;
use App\Models\UserAddress;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

// Test ĐẦU-CUỐI: quà tặng combo phải được vật chất hóa thành OrderItem thật (giá 0, is_gift) và vẫn
// trừ kho đúng khi tạo đơn hàng thật qua OrderService::create().
class PromotionGiftOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Cố định giờ trong khung mở cửa mặc định (08:00-22:00) để OrderService không chặn vì đóng cửa.
        $this->travelTo(\Illuminate\Support\Carbon::parse('14:00:00'));
    }

    private function makeProduct(array $overrides = []): Product
    {
        $categoryId = Category::create(['name' => 'Đồ uống', 'is_active' => true])->id;

        return Product::create(array_merge([
            'name' => 'Cà phê sữa đá',
            'slug' => 'sp-' . uniqid(),
            'sku' => 'SKU-' . strtoupper(uniqid()),
            'base_price' => 30000,
            'category_id' => $categoryId,
            'is_active' => true,
        ], $overrides));
    }

    private function makeAddress(User $user): UserAddress
    {
        return UserAddress::create([
            'user_id' => $user->id,
            'fullname' => 'Nguyễn Văn A',
            'phone' => '0911222333',
            'province' => 'Thành phố Hồ Chí Minh',
            'district' => 'Phường Chánh Hưng',
            'ward' => 'Phường Chánh Hưng',
            'specific_address' => '180 Cao Lỗ',
            'type' => 'home',
            'is_default' => true,
            'latitude' => 10.7383043,
            'longitude' => 106.6788227,
        ]);
    }

    // @param array $items [[Product, quantity], ...] - danh sách sản phẩm BẮT BUỘC phải mua đủ.
    private function makeCombo(array $items, ?array $discount = null, ?array $gift = null): Promotion
    {
        $promotion = Promotion::create([
            'code' => 'COMBO' . strtoupper(uniqid()), 'scope' => 'combo', 'type' => 'fixed', 'value' => 0,
            'apply_for' => 'all', 'applies_to' => 'all', 'is_active' => true, 'is_recurring' => false,
        ]);

        PromotionCombo::create([
            'promotion_id' => $promotion->id,
            'discount_type' => $discount['type'] ?? null,
            'discount_value' => $discount['value'] ?? null,
            'max_discount_amount' => $discount['max'] ?? null,
            'gift_product_id' => $gift['product']->id ?? null,
            'gift_quantity' => $gift['quantity'] ?? null,
            'auto_add_gift' => true,
        ]);

        foreach ($items as [$product, $qty]) {
            PromotionComboItem::create(['promotion_id' => $promotion->id, 'product_id' => $product->id, 'quantity' => $qty]);
        }

        return $promotion;
    }

    public function test_gift_is_materialized_as_free_order_item(): void
    {
        $user = User::factory()->create(['role' => 'customer', 'membership_level' => 'new']);
        $coffee = $this->makeProduct(['base_price' => 30000]);
        $tea = $this->makeProduct(['base_price' => 20000, 'name' => 'Trà tắc']);

        $promotion = $this->makeCombo([[$coffee, 2]], gift: ['product' => $tea, 'quantity' => 1]);

        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $coffee->id, 'quantity' => 2, 'unit_price' => 30000]);

        $order = app(OrderService::class)->create($user, [
            'idempotency_key' => (string) Str::uuid(),
            'address_id' => $this->makeAddress($user)->id,
            // Combo chỉ ăn khi khách tự chọn mã của nó, không còn tự động áp.
            'coupon_code' => $promotion->code,
        ], 'cod');

        // Dòng quà tặng tồn tại, giá 0, đánh dấu is_gift + gắn đúng khuyến mãi nguồn.
        $giftItem = $order->items()->where('is_gift', true)->first();
        $this->assertNotNull($giftItem);
        $this->assertSame($tea->id, (int) $giftItem->product_id);
        $this->assertSame(1, (int) $giftItem->quantity);
        $this->assertEquals(0, (float) $giftItem->unit_price);
        $this->assertSame($promotion->id, (int) $giftItem->source_promotion_id);

        // Quà KHÔNG được tính vào tiền hàng (chỉ 2 ly cà phê = 60.000đ).
        $this->assertEquals(60000, (float) $order->total_amount);
    }

    // Không chọn mã combo -> dù giỏ đủ món vẫn KHÔNG có quà (combo không tự áp nữa).
    public function test_no_gift_item_when_combo_code_not_chosen(): void
    {
        $user = User::factory()->create(['role' => 'customer', 'membership_level' => 'new']);
        $coffee = $this->makeProduct(['base_price' => 30000]);
        $tea = $this->makeProduct(['base_price' => 20000, 'name' => 'Trà tắc']);
        $this->makeCombo([[$coffee, 2]], gift: ['product' => $tea, 'quantity' => 1]);

        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $coffee->id, 'quantity' => 2, 'unit_price' => 30000]);

        $order = app(OrderService::class)->create($user, [
            'idempotency_key' => (string) Str::uuid(),
            'address_id' => $this->makeAddress($user)->id,
        ], 'cod');

        $this->assertSame(0, $order->items()->where('is_gift', true)->count());
        $this->assertNull($order->promotion_id);
        $this->assertEquals(0, (float) $order->discount_amount);
    }

    // Không đủ điều kiện (mua 1 khi cần 2) -> đơn tạo bình thường, KHÔNG có dòng quà nào.
    public function test_no_gift_item_when_condition_not_met(): void
    {
        $user = User::factory()->create(['role' => 'customer', 'membership_level' => 'new']);
        $coffee = $this->makeProduct(['base_price' => 30000]);
        $tea = $this->makeProduct(['base_price' => 20000, 'name' => 'Trà tắc']);
        $this->makeCombo([[$coffee, 2]], gift: ['product' => $tea, 'quantity' => 1]);

        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $coffee->id, 'quantity' => 1, 'unit_price' => 30000]);

        $order = app(OrderService::class)->create($user, [
            'idempotency_key' => (string) Str::uuid(),
            'address_id' => $this->makeAddress($user)->id,
        ], 'cod');

        $this->assertSame(0, $order->items()->where('is_gift', true)->count());
        $this->assertSame(1, $order->items()->count());
    }

    // Khách sửa request để đòi quà sai điều kiện: hệ thống tính lại hoàn toàn ở server từ giỏ hàng đã
    // khóa, mọi field lạ trong payload đều bị bỏ qua -> vẫn không có quà.
    public function test_tampered_request_cannot_grant_unearned_gift(): void
    {
        $user = User::factory()->create(['role' => 'customer', 'membership_level' => 'new']);
        $coffee = $this->makeProduct(['base_price' => 30000]);
        $tea = $this->makeProduct(['base_price' => 20000, 'name' => 'Trà tắc']);
        $this->makeCombo([[$coffee, 2]], gift: ['product' => $tea, 'quantity' => 1]);

        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $coffee->id, 'quantity' => 1, 'unit_price' => 30000]);

        $order = app(OrderService::class)->create($user, [
            'idempotency_key' => (string) Str::uuid(),
            'address_id' => $this->makeAddress($user)->id,
            // Các field bịa thêm mô phỏng người dùng sửa request để đòi quà.
            'gifts' => [['product_id' => $tea->id, 'quantity' => 99]],
            'gift_product_id' => $tea->id,
            'gift_quantity' => 99,
        ], 'cod');

        $this->assertSame(0, $order->items()->where('is_gift', true)->count());
    }

    // Mỗi đơn chỉ giữ 1 mã: chọn mã giảm giá thường thì KHÔNG kèm quà của combo, và ngược lại.
    public function test_only_the_chosen_code_applies_never_both(): void
    {
        $user = User::factory()->create(['role' => 'customer', 'membership_level' => 'new']);
        $coffee = $this->makeProduct(['base_price' => 50000]);
        $tea = $this->makeProduct(['base_price' => 20000, 'name' => 'Trà tắc']);

        Promotion::create([
            'code' => 'GIAM10', 'scope' => 'order', 'type' => 'percent', 'value' => 10,
            'apply_for' => 'all', 'applies_to' => 'all', 'is_active' => true, 'is_recurring' => false,
        ]);

        $giftPromotion = $this->makeCombo([[$coffee, 2]], gift: ['product' => $tea, 'quantity' => 1]);

        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $coffee->id, 'quantity' => 2, 'unit_price' => 50000]);

        $order = app(OrderService::class)->create($user, [
            'idempotency_key' => (string) Str::uuid(),
            'address_id' => $this->makeAddress($user)->id,
            'coupon_code' => 'GIAM10',
        ], 'cod');

        $this->assertEquals(10000, (float) $order->discount_amount);     // 10% của 100.000đ
        $this->assertSame('GIAM10', $order->coupon_code);
        // Chọn mã thường -> combo không được tính, không có quà và không bị trừ lượt.
        $this->assertSame(0, $order->items()->where('is_gift', true)->count());
        $this->assertSame(0, $giftPromotion->fresh()->used_count);
    }

    // Combo bật CẢ giảm giá lẫn tặng quà, KHÔNG trùng sản phẩm với mã coupon khác -> cả 2 thành phần
    // đều cộng thêm vào đơn thật, used_count của combo chỉ tăng đúng 1 lần (không phải 2).
    public function test_combo_discount_and_gift_both_materialize_in_real_order(): void
    {
        $user = User::factory()->create(['role' => 'customer', 'membership_level' => 'new']);
        $coffee = $this->makeProduct(['base_price' => 50000]);
        $tea = $this->makeProduct(['base_price' => 20000, 'name' => 'Trà tắc']);
        $promotion = $this->makeCombo(
            [[$coffee, 1]],
            discount: ['type' => 'fixed', 'value' => 5000],
            gift: ['product' => $tea, 'quantity' => 1]
        );

        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $coffee->id, 'quantity' => 1, 'unit_price' => 50000]);

        $order = app(OrderService::class)->create($user, [
            'idempotency_key' => (string) Str::uuid(),
            'address_id' => $this->makeAddress($user)->id,
            'coupon_code' => $promotion->code,
        ], 'cod');

        $this->assertEquals(5000, (float) $order->discount_amount);
        $this->assertSame(1, $order->items()->where('is_gift', true)->count());
        $this->assertSame(1, $promotion->fresh()->used_count);
    }

    // Đơn VNPay phải áp mã combo GIỐNG HỆT đơn COD (cùng số tiền giảm + cùng dòng quà tặng).
    public function test_vnpay_order_applies_combo_discount_and_gift_like_cod(): void
    {
        config([
            'services.vnpay.sandbox.tmn_code' => 'TESTTMN',
            'services.vnpay.sandbox.hash_secret' => 'TESTSECRET',
        ]);

        $user = User::factory()->create(['role' => 'customer', 'membership_level' => 'new']);
        $coffee = $this->makeProduct(['base_price' => 50000]);
        $tea = $this->makeProduct(['base_price' => 20000, 'name' => 'Trà tắc']);
        $promotion = $this->makeCombo(
            [[$coffee, 1]],
            discount: ['type' => 'fixed', 'value' => 5000],
            gift: ['product' => $tea, 'quantity' => 1]
        );

        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $coffee->id, 'quantity' => 1, 'unit_price' => 50000]);
        $address = $this->makeAddress($user);

        $this->actingAs($user)->post('/checkout/vnpay', [
            'address_id' => $address->id,
            'payment_method' => 'vnpay',
            'coupon_code' => $promotion->code,
        ])->assertRedirect();

        $order = \App\Models\Order::query()->where('user_id', $user->id)->firstOrFail();

        $this->assertEquals(5000, (float) $order->discount_amount);
        $this->assertSame(1, $order->items()->where('is_gift', true)->count());
        $this->assertSame(1, $promotion->fresh()->used_count);
    }

    // Mã combo hiện trong danh sách "Mã khả dụng" để khách bấm chọn, nhưng CHỈ khi giỏ đã đủ tổ hợp
    // món — chưa đủ thì không hiện, tránh khách bấm vào chỉ để nhận lỗi.
    public function test_combo_code_is_listed_as_a_chip_only_when_cart_is_eligible(): void
    {
        $user = User::factory()->create(['role' => 'customer', 'membership_level' => 'new']);
        $coffee = $this->makeProduct(['base_price' => 50000, 'name' => 'Cà phê đen đá']);
        $tea = $this->makeProduct(['base_price' => 20000, 'name' => 'Trà tắc']);
        $promotion = $this->makeCombo([[$coffee, 2]], gift: ['product' => $tea, 'quantity' => 1]);
        $this->makeAddress($user);

        // Combo cần 2 ly, giỏ mới có 1 -> chưa đủ, không hiện chip.
        $cart = Cart::create(['user_id' => $user->id]);
        $line = CartItem::create(['cart_id' => $cart->id, 'product_id' => $coffee->id, 'quantity' => 1, 'unit_price' => 50000]);

        $this->actingAs($user)->get('/checkout')
            ->assertOk()
            ->assertDontSee($promotion->code, false);

        // Mua đủ 2 ly -> chip hiện ra kèm mô tả phần thưởng.
        $line->update(['quantity' => 2]);

        $this->actingAs($user)->get('/checkout')
            ->assertOk()
            ->assertSee($promotion->code, false)
            ->assertSee('tặng 1 Trà tắc', false);
    }

    // Bấm chip = gọi endpoint áp mã. Endpoint phải trả về quà tặng để trang vẽ dòng quà 0đ.
    public function test_apply_coupon_endpoint_returns_combo_gifts(): void
    {
        $user = User::factory()->create(['role' => 'customer', 'membership_level' => 'new']);
        $coffee = $this->makeProduct(['base_price' => 50000, 'name' => 'Cà phê đen đá']);
        $tea = $this->makeProduct(['base_price' => 20000, 'name' => 'Trà tắc']);
        $promotion = $this->makeCombo(
            [[$coffee, 2]],
            discount: ['type' => 'fixed', 'value' => 7000],
            gift: ['product' => $tea, 'quantity' => 1]
        );

        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $coffee->id, 'quantity' => 2, 'unit_price' => 50000]);

        $this->actingAs($user)->postJson('/checkout/validate-coupon', ['coupon_code' => $promotion->code])
            ->assertOk()
            ->assertJson([
                'valid' => true,
                'discount_amount' => 7000,
                'scope' => 'combo',
                'gifts' => [['name' => 'Trà tắc', 'quantity' => 1]],
            ]);
    }

    // Chưa mua đủ tổ hợp mà cố gõ mã combo -> báo lỗi nói rõ còn thiếu gì, không âm thầm bỏ qua.
    public function test_apply_coupon_endpoint_explains_what_is_missing_for_combo(): void
    {
        $user = User::factory()->create(['role' => 'customer', 'membership_level' => 'new']);
        $coffee = $this->makeProduct(['base_price' => 50000, 'name' => 'Cà phê đen đá']);
        $tea = $this->makeProduct(['base_price' => 20000, 'name' => 'Trà tắc']);
        $promotion = $this->makeCombo([[$coffee, 2]], gift: ['product' => $tea, 'quantity' => 1]);

        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $coffee->id, 'quantity' => 1, 'unit_price' => 50000]);

        $this->actingAs($user)->postJson('/checkout/validate-coupon', ['coupon_code' => $promotion->code])
            ->assertOk()
            ->assertJson(['valid' => false])
            ->assertJsonFragment(['message' => 'Mã này yêu cầu mua 2 Cà phê đen đá. Giỏ hàng của bạn chưa đủ.']);
    }

    // Lễ tân nhập mã combo trên POS: bảng xem trước phải ra đúng tiền giảm + quà, giống trang khách.
    public function test_pos_preview_applies_combo_code_with_gifts(): void
    {
        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $coffee = $this->makeProduct(['base_price' => 50000, 'name' => 'Cà phê đen đá']);
        $tea = $this->makeProduct(['base_price' => 20000, 'name' => 'Trà tắc']);
        $promotion = $this->makeCombo(
            [[$coffee, 2]],
            discount: ['type' => 'fixed', 'value' => 7000],
            gift: ['product' => $tea, 'quantity' => 1]
        );

        $cart = Cart::create(['user_id' => $receptionist->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $coffee->id, 'quantity' => 2, 'unit_price' => 50000]);

        $this->actingAs($receptionist)
            ->getJson('/staff/reception/orders/preview-total?coupon_code=' . $promotion->code)
            ->assertOk()
            ->assertJson([
                'subtotal' => 100000,
                'discount' => 7000,
                'final_amount' => 93000,
                'gifts' => [['gift_product_name' => 'Trà tắc', 'quantity' => 1]],
            ]);
    }

    // Mã đã dùng hết lượt (per-user) thì chip "Mã khả dụng" phải BIẾN MẤT ở lần tải trang tiếp theo —
    // không được tiếp tục hiện ra rồi báo lỗi khi bấm, gây hiểu lầm là hệ thống bug.
    public function test_used_up_code_chip_disappears_from_checkout_page_on_next_load(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $product = $this->makeProduct(['base_price' => 100000]);
        $address = $this->makeAddress($user);

        $promotion = Promotion::create([
            'code' => 'ONCECHIP', 'scope' => 'order', 'type' => 'fixed', 'value' => 10000,
            'apply_for' => 'all', 'applies_to' => 'all', 'is_active' => true, 'is_recurring' => false,
            'usage_limit_per_user' => 1,
        ]);

        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1, 'unit_price' => 100000]);

        // Lần tải trang đầu -> mã còn dùng được -> chip hiện ra.
        $this->actingAs($user)->get('/checkout')
            ->assertOk()
            ->assertSee('ONCECHIP', false);

        // idempotency_key CHÍNH LÀ checkout token — cả 2 phải cùng 1 giá trị (xem
        // CustomerOrderController::assertCheckoutToken()).
        $token = (string) Str::uuid();
        $response = $this->actingAs($user)->withSession(['checkout_token' => $token])->post('/checkout', [
            'address_id' => $address->id,
            'idempotency_key' => $token,
            'coupon_code' => 'ONCECHIP',
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('orders', ['user_id' => $user->id, 'coupon_code' => 'ONCECHIP', 'promotion_id' => $promotion->id]);

        // Đơn vừa đặt đã dọn sạch giỏ (xóa cart_items, giữ nguyên bản ghi Cart) -> thêm lại món cho lượt sau.
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1, 'unit_price' => 100000]);

        // Tải lại trang -> đã dùng hết lượt -> chip KHÔNG còn hiện ra nữa.
        $this->actingAs($user)->get('/checkout')
            ->assertOk()
            ->assertDontSee('ONCECHIP', false);
    }
}
