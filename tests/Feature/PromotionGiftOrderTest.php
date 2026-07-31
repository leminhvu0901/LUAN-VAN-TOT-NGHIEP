<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Material;
use App\Models\MaterialImport;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\PromotionCombo;
use App\Models\PromotionComboItem;
use App\Models\User;
use App\Models\UserAddress;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
        $categoryId = Category::create(['name' => 'Đồ uống', 'slug' => 'cat-' . uniqid(), 'is_active' => true])->id;

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

    /**
     * @param array $items [[Product, quantity], ...] - danh sách sản phẩm BẮT BUỘC phải mua đủ.
     */
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

    public function test_gift_is_materialized_as_free_order_item_and_deducts_stock(): void
    {
        $user = User::factory()->create(['role' => 'customer', 'membership_level' => 'new']);
        $coffee = $this->makeProduct(['base_price' => 30000]);
        $tea = $this->makeProduct(['base_price' => 20000, 'name' => 'Trà tắc']);

        // Nguyên liệu cho món quà — đủ tồn kho để tặng.
        $material = Material::create(['name' => 'Trà', 'unit' => 'g', 'unit_price' => 100, 'current_stock' => 50, 'is_active' => true]);
        MaterialImport::create(['material_id' => $material->id, 'quantity' => 50, 'remaining_quantity' => 50, 'total_price' => 5000, 'expiration_date' => today()->addMonth()]);
        DB::table('product_materials')->insert(['product_id' => $tea->id, 'material_id' => $material->id, 'quantity_used' => 2, 'created_at' => now(), 'updated_at' => now()]);

        $promotion = $this->makeCombo([[$coffee, 2]], gift: ['product' => $tea, 'quantity' => 1]);

        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $coffee->id, 'quantity' => 2, 'unit_price' => 30000]);

        $order = app(OrderService::class)->create($user, [
            'idempotency_key' => (string) Str::uuid(),
            'address_id' => $this->makeAddress($user)->id,
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

        // Kho vẫn bị trừ đúng cho món quà (1 ly × 2g = 2g).
        $this->assertEquals(48, (float) $material->fresh()->current_stock);
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

    // Mã giảm giá tiền và quà tặng combo là 2 nghiệp vụ độc lập (không trùng sản phẩm) -> 1 đơn có
    // thể vừa giảm giá vừa có quà.
    public function test_money_discount_and_gift_can_apply_together(): void
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
        $this->assertSame(1, $order->items()->where('is_gift', true)->count());
        $this->assertSame(1, $giftPromotion->fresh()->used_count);
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
        ], 'cod');

        $this->assertEquals(5000, (float) $order->discount_amount);
        $this->assertSame(1, $order->items()->where('is_gift', true)->count());
        $this->assertSame(1, $promotion->fresh()->used_count);
    }
}
