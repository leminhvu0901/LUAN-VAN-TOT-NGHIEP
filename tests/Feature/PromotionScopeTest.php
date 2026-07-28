<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\PromotionBuyXGetY;
use App\Models\User;
use App\Services\CartPricingService;
use App\Services\PromotionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

// Test 3 nghiệp vụ khuyến mãi mở rộng: giảm theo SẢN PHẨM, giảm theo DANH MỤC, MUA X TẶNG Y.
// Theo đúng quy ước sẵn có của dự án: tạo dữ liệu bằng Model::create() trực tiếp, không dùng factory.
class PromotionScopeTest extends TestCase
{
    use RefreshDatabase;

    private function makeCategory(string $name = 'Trà sữa'): Category
    {
        return Category::create(['name' => $name, 'slug' => 'cat-' . uniqid(), 'is_active' => true]);
    }

    private function makeProduct(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'name' => 'Trà sữa trân châu',
            'slug' => 'sp-' . uniqid(),
            'sku' => 'SKU-' . strtoupper(uniqid()),
            'base_price' => 40000,
            'category_id' => $this->makeCategory()->id,
            'is_active' => true,
        ], $overrides));
    }

    // Dựng giỏ hàng đã tính giá (đúng dạng PromotionService/OrderService nhận vào).
    private function pricedCart(User $user, array $lines): \Illuminate\Support\Collection
    {
        $cart = Cart::create(['user_id' => $user->id]);
        foreach ($lines as [$product, $quantity]) {
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => $product->base_price,
            ]);
        }

        return app(CartPricingService::class)->pricedItems($cart);
    }

    private function service(): PromotionService
    {
        return app(PromotionService::class);
    }

    // ===== 1. GIẢM GIÁ THEO SẢN PHẨM =====

    // Ví dụ trong đặc tả: A 40.000đ×2 + B 30.000đ×1, mã CHỈ áp dụng A giảm 10% -> giảm 8.000đ
    // (10% của 80.000đ), KHÔNG phải 11.000đ (10% của toàn đơn 110.000đ).
    public function test_product_scope_percent_discount_only_counts_selected_products(): void
    {
        $user = User::factory()->create();
        $productA = $this->makeProduct(['base_price' => 40000]);
        $productB = $this->makeProduct(['base_price' => 30000]);
        $items = $this->pricedCart($user, [[$productA, 2], [$productB, 1]]);

        $promotion = Promotion::create([
            'code' => 'SP10', 'scope' => 'product', 'type' => 'percent', 'value' => 10,
            'apply_for' => 'all', 'applies_to' => 'all', 'is_active' => true, 'is_recurring' => false,
        ]);
        $promotion->products()->sync([$productA->id]);

        $result = $this->service()->resolveBestDiscount($items, 110000, $user, 'delivery', 3, 'SP10');

        $this->assertSame(8000.0, $result['discount']);
    }

    public function test_product_scope_fixed_discount_capped_by_eligible_subtotal(): void
    {
        $user = User::factory()->create();
        $productA = $this->makeProduct(['base_price' => 20000]);
        $productB = $this->makeProduct(['base_price' => 90000]);
        $items = $this->pricedCart($user, [[$productA, 1], [$productB, 1]]);

        // Giảm cố định 50.000đ nhưng phần hợp lệ (chỉ sản phẩm A) chỉ có 20.000đ -> giảm tối đa 20.000đ.
        $promotion = Promotion::create([
            'code' => 'SPFIX', 'scope' => 'product', 'type' => 'fixed', 'value' => 50000,
            'apply_for' => 'all', 'applies_to' => 'all', 'is_active' => true, 'is_recurring' => false,
        ]);
        $promotion->products()->sync([$productA->id]);

        $result = $this->service()->resolveBestDiscount($items, 110000, $user, 'delivery', 2, 'SPFIX');

        $this->assertSame(20000.0, $result['discount']);
    }

    // Giỏ hàng không có sản phẩm nào thuộc phạm vi mã -> PHẢI từ chối (422), không được báo "áp dụng
    // thành công" kèm số giảm 0đ — trước đây có bug thật đúng kiểu này: khách nhập mã, hệ thống báo
    // "Áp dụng thành công mã TESTSP! Áp dụng cho: Bạc xỉu cốt dừa, Bạc xỉu đá xay" trong khi giỏ chỉ
    // có Cà phê đen đá, và số tiền không đổi — trông như hệ thống tính sai dù về mặt số học 0đ đúng
    // là 10% của 0đ.
    public function test_product_scope_rejects_when_no_eligible_product_in_cart(): void
    {
        $user = User::factory()->create();
        $inCart = $this->makeProduct(['base_price' => 50000]);
        $notInCart = $this->makeProduct(['base_price' => 50000]);
        $items = $this->pricedCart($user, [[$inCart, 1]]);

        $promotion = Promotion::create([
            'code' => 'SPNONE', 'scope' => 'product', 'type' => 'percent', 'value' => 50,
            'apply_for' => 'all', 'applies_to' => 'all', 'is_active' => true, 'is_recurring' => false,
        ]);
        $promotion->products()->sync([$notInCart->id]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->service()->resolveBestDiscount($items, 50000, $user, 'delivery', 1, 'SPNONE');
    }

    // ===== 2. GIẢM GIÁ THEO DANH MỤC =====

    // Ví dụ đặc tả: danh mục Trà sữa giảm 15%; Trà sữa 80.000đ + Cà phê 40.000đ -> chỉ giảm trên 80.000đ.
    public function test_category_scope_discount_only_counts_products_in_category(): void
    {
        $user = User::factory()->create();
        $milkTeaCategory = $this->makeCategory('Trà sữa');
        $coffeeCategory = $this->makeCategory('Cà phê');
        $milkTea = $this->makeProduct(['base_price' => 80000, 'category_id' => $milkTeaCategory->id]);
        $coffee = $this->makeProduct(['base_price' => 40000, 'category_id' => $coffeeCategory->id]);
        $items = $this->pricedCart($user, [[$milkTea, 1], [$coffee, 1]]);

        $promotion = Promotion::create([
            'code' => 'DM15', 'scope' => 'category', 'type' => 'percent', 'value' => 15,
            'apply_for' => 'all', 'applies_to' => 'all', 'is_active' => true, 'is_recurring' => false,
        ]);
        $promotion->categories()->sync([$milkTeaCategory->id]);

        $result = $this->service()->resolveBestDiscount($items, 120000, $user, 'delivery', 2, 'DM15');

        $this->assertSame(12000.0, $result['discount']); // 15% của 80.000đ
    }

    // Cùng lý do như test_product_scope_rejects_when_no_eligible_product_in_cart — không có sản phẩm
    // nào thuộc danh mục thì phải từ chối, không được báo "áp dụng thành công" kèm 0đ.
    public function test_category_scope_rejects_when_no_product_in_category(): void
    {
        $user = User::factory()->create();
        $targetCategory = $this->makeCategory('Trà sữa');
        $otherCategory = $this->makeCategory('Cà phê');
        $coffee = $this->makeProduct(['base_price' => 60000, 'category_id' => $otherCategory->id]);
        $items = $this->pricedCart($user, [[$coffee, 1]]);

        $promotion = Promotion::create([
            'code' => 'DMNONE', 'scope' => 'category', 'type' => 'percent', 'value' => 50,
            'apply_for' => 'all', 'applies_to' => 'all', 'is_active' => true, 'is_recurring' => false,
        ]);
        $promotion->categories()->sync([$targetCategory->id]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->service()->resolveBestDiscount($items, 60000, $user, 'delivery', 1, 'DMNONE');
    }

    // ===== 3. MUA X TẶNG Y =====

    private function makeBuyXGetY(Product $buy, int $buyQty, Product $gift, int $giftQty, ?int $maxApplications = null): Promotion
    {
        $promotion = Promotion::create([
            'code' => 'BXGY' . strtoupper(uniqid()), 'scope' => 'buy_x_get_y', 'type' => 'fixed', 'value' => 0,
            'apply_for' => 'all', 'applies_to' => 'all', 'is_active' => true, 'is_recurring' => false,
        ]);
        PromotionBuyXGetY::create([
            'promotion_id' => $promotion->id,
            'buy_product_id' => $buy->id,
            'buy_quantity' => $buyQty,
            'gift_product_id' => $gift->id,
            'gift_quantity' => $giftQty,
            'max_applications_per_order' => $maxApplications,
            'auto_add_gift' => true,
        ]);

        return $promotion;
    }

    public function test_buy_one_when_two_required_grants_no_gift(): void
    {
        $user = User::factory()->create();
        $coffee = $this->makeProduct(['base_price' => 30000]);
        $tea = $this->makeProduct(['base_price' => 20000]);
        $this->makeBuyXGetY($coffee, 2, $tea, 1);

        $items = $this->pricedCart($user, [[$coffee, 1]]);

        $this->assertEmpty($this->service()->resolveGifts($items, 'delivery'));
    }

    public function test_buy_two_grants_one_gift(): void
    {
        $user = User::factory()->create();
        $coffee = $this->makeProduct(['base_price' => 30000]);
        $tea = $this->makeProduct(['base_price' => 20000]);
        $this->makeBuyXGetY($coffee, 2, $tea, 1);

        $gifts = $this->service()->resolveGifts($this->pricedCart($user, [[$coffee, 2]]), 'delivery');

        $this->assertCount(1, $gifts);
        $this->assertSame($tea->id, $gifts[0]['gift_product']->id);
        $this->assertSame(1, $gifts[0]['granted_quantity']);
    }

    public function test_buy_four_grants_two_gifts(): void
    {
        $user = User::factory()->create();
        $coffee = $this->makeProduct(['base_price' => 30000]);
        $tea = $this->makeProduct(['base_price' => 20000]);
        $this->makeBuyXGetY($coffee, 2, $tea, 1);

        $gifts = $this->service()->resolveGifts($this->pricedCart($user, [[$coffee, 4]]), 'delivery');

        $this->assertSame(2, $gifts[0]['granted_quantity']);
    }

    // Mua 5 với công thức mua 2 tặng 1 -> floor(5/2)=2 lần, vẫn chỉ tặng 2 (không làm tròn lên).
    public function test_buy_five_still_grants_only_two_gifts(): void
    {
        $user = User::factory()->create();
        $coffee = $this->makeProduct(['base_price' => 30000]);
        $tea = $this->makeProduct(['base_price' => 20000]);
        $this->makeBuyXGetY($coffee, 2, $tea, 1);

        $gifts = $this->service()->resolveGifts($this->pricedCart($user, [[$coffee, 5]]), 'delivery');

        $this->assertSame(2, $gifts[0]['granted_quantity']);
    }

    public function test_max_applications_per_order_limits_gift_quantity(): void
    {
        $user = User::factory()->create();
        $coffee = $this->makeProduct(['base_price' => 30000]);
        $tea = $this->makeProduct(['base_price' => 20000]);
        $this->makeBuyXGetY($coffee, 2, $tea, 1, maxApplications: 1);

        // Mua 4 = đủ 2 lần, nhưng giới hạn 1 lần/đơn -> chỉ tặng 1.
        $gifts = $this->service()->resolveGifts($this->pricedCart($user, [[$coffee, 4]]), 'delivery');

        $this->assertSame(1, $gifts[0]['granted_quantity']);
    }

    // Mua và tặng CÙNG 1 sản phẩm: mua 4 với công thức mua 2 tặng 1 -> tặng 2, không lặp vô hạn.
    public function test_same_product_as_buy_and_gift_counts_correctly(): void
    {
        $user = User::factory()->create();
        $coffee = $this->makeProduct(['base_price' => 30000]);
        $this->makeBuyXGetY($coffee, 2, $coffee, 1);

        $gifts = $this->service()->resolveGifts($this->pricedCart($user, [[$coffee, 4]]), 'delivery');

        $this->assertCount(1, $gifts);
        $this->assertSame($coffee->id, $gifts[0]['gift_product']->id);
        $this->assertSame(2, $gifts[0]['granted_quantity']);
    }

    // Kho quà không đủ -> giảm số lượng tặng xuống mức khả dụng và đánh dấu stock_limited,
    // KHÔNG chặn đơn hàng.
    public function test_gift_quantity_reduced_when_stock_insufficient(): void
    {
        $user = User::factory()->create();
        $coffee = $this->makeProduct(['base_price' => 30000]);
        $tea = $this->makeProduct(['base_price' => 20000]);

        // Nguyên liệu chỉ đủ pha đúng 1 ly trà tặng (mỗi ly cần 1 đơn vị, tồn kho 1).
        $material = \App\Models\Material::create(['name' => 'Trà', 'unit' => 'g', 'unit_price' => 100, 'current_stock' => 1, 'is_active' => true]);
        \App\Models\MaterialImport::create(['material_id' => $material->id, 'quantity' => 1, 'remaining_quantity' => 1, 'total_price' => 100, 'expiration_date' => today()->addMonth()]);
        DB::table('product_materials')->insert(['product_id' => $tea->id, 'material_id' => $material->id, 'quantity_used' => 1, 'created_at' => now(), 'updated_at' => now()]);

        $this->makeBuyXGetY($coffee, 2, $tea, 1);

        // Mua 4 = đủ điều kiện tặng 2, nhưng kho chỉ đủ 1.
        $gifts = $this->service()->resolveGifts($this->pricedCart($user, [[$coffee, 4]]), 'delivery');

        $this->assertSame(1, $gifts[0]['granted_quantity']);
        $this->assertTrue($gifts[0]['stock_limited']);
    }

    // Giảm số lượng món mua xuống dưới điều kiện -> quà tự biến mất (resolveGifts tính lại mỗi lần).
    public function test_reducing_purchased_quantity_removes_gift(): void
    {
        $user = User::factory()->create();
        $coffee = $this->makeProduct(['base_price' => 30000]);
        $tea = $this->makeProduct(['base_price' => 20000]);
        $this->makeBuyXGetY($coffee, 2, $tea, 1);

        $this->assertCount(1, $this->service()->resolveGifts($this->pricedCart($user, [[$coffee, 2]]), 'delivery'));

        // Giỏ mới chỉ còn 1 ly -> không còn quà.
        $otherUser = User::factory()->create();
        $this->assertEmpty($this->service()->resolveGifts($this->pricedCart($otherUser, [[$coffee, 1]]), 'delivery'));
    }

    // ===== 4. TRẠNG THÁI / THỜI GIAN / GIỚI HẠN =====

    public function test_promotion_not_started_yet_is_rejected(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct(['base_price' => 50000]);
        $items = $this->pricedCart($user, [[$product, 1]]);

        Promotion::create([
            'code' => 'FUTURE', 'scope' => 'order', 'type' => 'percent', 'value' => 10,
            'apply_for' => 'all', 'applies_to' => 'all', 'is_active' => true, 'is_recurring' => false,
            'start_at' => now()->addDays(3),
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->service()->resolveBestDiscount($items, 50000, $user, 'delivery', 1, 'FUTURE');
    }

    public function test_expired_promotion_is_rejected(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct(['base_price' => 50000]);
        $items = $this->pricedCart($user, [[$product, 1]]);

        Promotion::create([
            'code' => 'EXPIRED', 'scope' => 'order', 'type' => 'percent', 'value' => 10,
            'apply_for' => 'all', 'applies_to' => 'all', 'is_active' => true, 'is_recurring' => false,
            'end_at' => now()->subDay(),
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->service()->resolveBestDiscount($items, 50000, $user, 'delivery', 1, 'EXPIRED');
    }

    public function test_inactive_promotion_is_rejected(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct(['base_price' => 50000]);
        $items = $this->pricedCart($user, [[$product, 1]]);

        Promotion::create([
            'code' => 'LOCKED', 'scope' => 'order', 'type' => 'percent', 'value' => 10,
            'apply_for' => 'all', 'applies_to' => 'all', 'is_active' => false, 'is_recurring' => false,
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->service()->resolveBestDiscount($items, 50000, $user, 'delivery', 1, 'LOCKED');
    }

    public function test_usage_limit_exceeded_is_rejected(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct(['base_price' => 50000]);
        $items = $this->pricedCart($user, [[$product, 1]]);

        Promotion::create([
            'code' => 'USEDUP', 'scope' => 'order', 'type' => 'percent', 'value' => 10,
            'apply_for' => 'all', 'applies_to' => 'all', 'is_active' => true, 'is_recurring' => false,
            'usage_limit' => 5, 'used_count' => 5,
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->service()->resolveBestDiscount($items, 50000, $user, 'delivery', 1, 'USEDUP');
    }

    // Mã Mua X tặng Y không phải mã giảm tiền -> không nhập tay ở ô mã giảm giá được.
    public function test_buy_x_get_y_code_cannot_be_used_as_discount_code(): void
    {
        $user = User::factory()->create();
        $coffee = $this->makeProduct(['base_price' => 30000]);
        $tea = $this->makeProduct(['base_price' => 20000]);
        $promotion = $this->makeBuyXGetY($coffee, 2, $tea, 1);
        $items = $this->pricedCart($user, [[$coffee, 2]]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->service()->resolveBestDiscount($items, 60000, $user, 'delivery', 2, $promotion->code);
    }

    // ===== 5. KHÔNG ÂM TIỀN + TƯƠNG THÍCH NGƯỢC =====

    public function test_discount_never_exceeds_eligible_subtotal(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct(['base_price' => 30000]);
        $items = $this->pricedCart($user, [[$product, 1]]);

        Promotion::create([
            'code' => 'HUGE', 'scope' => 'order', 'type' => 'fixed', 'value' => 999999,
            'apply_for' => 'all', 'applies_to' => 'all', 'is_active' => true, 'is_recurring' => false,
        ]);

        $result = $this->service()->resolveBestDiscount($items, 30000, $user, 'delivery', 1, 'HUGE');

        $this->assertSame(30000.0, $result['discount']);
        $this->assertGreaterThanOrEqual(0, 30000 - $result['discount']);
    }

    // Dữ liệu cũ (trước khi có cột scope) mặc định scope='order' -> percent/fixed hoạt động y như trước.
    public function test_legacy_order_scope_percent_still_works(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct(['base_price' => 100000]);
        $items = $this->pricedCart($user, [[$product, 1]]);

        // Không truyền 'scope' -> DB tự dùng default 'order' (giống hệt bản ghi cũ).
        Promotion::create([
            'code' => 'OLD10', 'type' => 'percent', 'value' => 10,
            'apply_for' => 'all', 'applies_to' => 'all', 'is_active' => true, 'is_recurring' => false,
        ]);

        $result = $this->service()->resolveBestDiscount($items, 100000, $user, 'delivery', 1, 'OLD10');

        $this->assertSame('order', $result['promotion']->scope);
        $this->assertSame(10000.0, $result['discount']);
    }

    public function test_legacy_order_scope_fixed_still_works(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct(['base_price' => 100000]);
        $items = $this->pricedCart($user, [[$product, 1]]);

        Promotion::create([
            'code' => 'OLDFIX', 'type' => 'fixed', 'value' => 25000,
            'apply_for' => 'all', 'applies_to' => 'all', 'is_active' => true, 'is_recurring' => false,
        ]);

        $result = $this->service()->resolveBestDiscount($items, 100000, $user, 'delivery', 1, 'OLDFIX');

        $this->assertSame(25000.0, $result['discount']);
    }
}
