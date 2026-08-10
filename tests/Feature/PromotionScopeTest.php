<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\PromotionCombo;
use App\Models\PromotionComboItem;
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
        return Category::create(['name' => $name, 'is_active' => true]);
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
            'code' => 'SP10',
            'scope' => 'product',
            'type' => 'percent',
            'value' => 10,
            'apply_for' => 'all',
            'applies_to' => 'all',
            'is_active' => true,
            'is_recurring' => false,
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
            'code' => 'SPFIX',
            'scope' => 'product',
            'type' => 'fixed',
            'value' => 50000,
            'apply_for' => 'all',
            'applies_to' => 'all',
            'is_active' => true,
            'is_recurring' => false,
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
            'code' => 'SPNONE',
            'scope' => 'product',
            'type' => 'percent',
            'value' => 50,
            'apply_for' => 'all',
            'applies_to' => 'all',
            'is_active' => true,
            'is_recurring' => false,
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
            'code' => 'DM15',
            'scope' => 'category',
            'type' => 'percent',
            'value' => 15,
            'apply_for' => 'all',
            'applies_to' => 'all',
            'is_active' => true,
            'is_recurring' => false,
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
            'code' => 'DMNONE',
            'scope' => 'category',
            'type' => 'percent',
            'value' => 50,
            'apply_for' => 'all',
            'applies_to' => 'all',
            'is_active' => true,
            'is_recurring' => false,
        ]);
        $promotion->categories()->sync([$targetCategory->id]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->service()->resolveBestDiscount($items, 60000, $user, 'delivery', 1, 'DMNONE');
    }

    // ===== 3. COMBO (trước đây "Mua X tặng Y") =====

    /**
     * @param array $items [[Product, quantity], ...] - danh sách sản phẩm BẮT BUỘC phải mua đủ.
     * @param ?array $discount ['type' => 'percent'|'fixed', 'value' => float, 'max' => ?float]
     * @param ?array $gift ['product' => Product, 'quantity' => int, 'auto_add' => bool]
     */
    private function makeCombo(array $items, ?array $discount = null, ?array $gift = null, ?int $maxApplications = null): Promotion
    {
        $promotion = Promotion::create([
            'code' => 'COMBO' . strtoupper(uniqid()),
            'scope' => 'combo',
            'type' => 'fixed',
            'value' => 0,
            'apply_for' => 'all',
            'applies_to' => 'all',
            'is_active' => true,
            'is_recurring' => false,
        ]);

        PromotionCombo::create([
            'promotion_id' => $promotion->id,
            'discount_type' => $discount['type'] ?? null,
            'discount_value' => $discount['value'] ?? null,
            'max_discount_amount' => $discount['max'] ?? null,
            'gift_product_id' => $gift['product']->id ?? null,
            'gift_quantity' => $gift['quantity'] ?? null,
            'auto_add_gift' => $gift['auto_add'] ?? true,
            'max_applications_per_order' => $maxApplications,
        ]);

        foreach ($items as [$product, $qty]) {
            PromotionComboItem::create([
                'promotion_id' => $promotion->id,
                'product_id' => $product->id,
                'quantity' => $qty,
            ]);
        }

        return $promotion;
    }

    // Áp mã combo đúng như khách thao tác thật: tự bấm chip hoặc gõ mã ở trang thanh toán.
    // Combo KHÔNG còn tự động áp, nên mọi test dưới đây đều phải đi qua đường nhập mã.
    private function applyCombo(Promotion $combo, \Illuminate\Support\Collection $items, ?User $user = null): array
    {
        $subtotal = (float) $items->sum(fn($i) => (float) $i->calculated_unit_price * (int) $i->quantity);

        return $this->service()->resolveBestDiscount(
            $items,
            $subtotal,
            $user,
            'delivery',
            (int) $items->sum('quantity'),
            $combo->code
        );
    }

    public function test_combo_buy_one_when_two_required_is_rejected(): void
    {
        $user = User::factory()->create();
        $coffee = $this->makeProduct(['base_price' => 30000]);
        $tea = $this->makeProduct(['base_price' => 20000]);
        $combo = $this->makeCombo([[$coffee, 2]], gift: ['product' => $tea, 'quantity' => 1]);

        $items = $this->pricedCart($user, [[$coffee, 1]]);

        // Chưa mua đủ tổ hợp -> báo lỗi ngay khi bấm mã, không im lặng bỏ qua như cơ chế tự động cũ.
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->applyCombo($combo, $items, $user);
    }

    public function test_combo_buy_two_grants_one_gift(): void
    {
        $user = User::factory()->create();
        $coffee = $this->makeProduct(['base_price' => 30000]);
        $tea = $this->makeProduct(['base_price' => 20000]);
        $combo = $this->makeCombo([[$coffee, 2]], gift: ['product' => $tea, 'quantity' => 1]);

        $result = $this->applyCombo($combo, $this->pricedCart($user, [[$coffee, 2]]), $user);

        $this->assertCount(1, $result['gifts']);
        $this->assertSame($tea->id, $result['gifts'][0]['gift_product']->id);
        $this->assertSame(1, $result['gifts'][0]['granted_quantity']);
    }

    public function test_combo_buy_four_grants_two_gifts(): void
    {
        $user = User::factory()->create();
        $coffee = $this->makeProduct(['base_price' => 30000]);
        $tea = $this->makeProduct(['base_price' => 20000]);
        $combo = $this->makeCombo([[$coffee, 2]], gift: ['product' => $tea, 'quantity' => 1]);

        $result = $this->applyCombo($combo, $this->pricedCart($user, [[$coffee, 4]]), $user);

        $this->assertSame(2, $result['gifts'][0]['granted_quantity']);
    }

    // Mua 5 với công thức mua 2 tặng 1 -> floor(5/2)=2 lần, vẫn chỉ tặng 2 (không làm tròn lên).
    public function test_combo_buy_five_still_grants_only_two_gifts(): void
    {
        $user = User::factory()->create();
        $coffee = $this->makeProduct(['base_price' => 30000]);
        $tea = $this->makeProduct(['base_price' => 20000]);
        $combo = $this->makeCombo([[$coffee, 2]], gift: ['product' => $tea, 'quantity' => 1]);

        $result = $this->applyCombo($combo, $this->pricedCart($user, [[$coffee, 5]]), $user);

        $this->assertSame(2, $result['gifts'][0]['granted_quantity']);
    }

    public function test_max_applications_per_order_limits_gift_quantity(): void
    {
        $user = User::factory()->create();
        $coffee = $this->makeProduct(['base_price' => 30000]);
        $tea = $this->makeProduct(['base_price' => 20000]);
        $combo = $this->makeCombo([[$coffee, 2]], gift: ['product' => $tea, 'quantity' => 1], maxApplications: 1);

        // Mua 4 = đủ 2 lần, nhưng giới hạn 1 lần/đơn -> chỉ tặng 1.
        $result = $this->applyCombo($combo, $this->pricedCart($user, [[$coffee, 4]]), $user);

        $this->assertSame(1, $result['gifts'][0]['granted_quantity']);
    }

    // Mua và tặng CÙNG 1 sản phẩm: mua 4 với công thức mua 2 tặng 1 -> tặng 2, không lặp vô hạn.
    public function test_same_product_as_buy_and_gift_counts_correctly(): void
    {
        $user = User::factory()->create();
        $coffee = $this->makeProduct(['base_price' => 30000]);
        $combo = $this->makeCombo([[$coffee, 2]], gift: ['product' => $coffee, 'quantity' => 1]);

        $result = $this->applyCombo($combo, $this->pricedCart($user, [[$coffee, 4]]), $user);

        $this->assertCount(1, $result['gifts']);
        $this->assertSame($coffee->id, $result['gifts'][0]['gift_product']->id);
        $this->assertSame(2, $result['gifts'][0]['granted_quantity']);
    }

    // Combo nhiều sản phẩm khác nhau - phải mua ĐỦ TẤT CẢ mới áp được mã.
    public function test_combo_with_multiple_products_requires_all_of_them(): void
    {
        $user = User::factory()->create();
        $coffee = $this->makeProduct(['base_price' => 30000]);
        $tea = $this->makeProduct(['base_price' => 20000]);
        $cake = $this->makeProduct(['base_price' => 15000]);
        $gift = $this->makeProduct(['base_price' => 5000]);
        $combo = $this->makeCombo([[$coffee, 1], [$tea, 1], [$cake, 1]], gift: ['product' => $gift, 'quantity' => 1]);

        // Đủ cả 3 -> được tặng.
        $result = $this->applyCombo($combo, $this->pricedCart($user, [[$coffee, 1], [$tea, 1], [$cake, 1]]), $user);
        $this->assertCount(1, $result['gifts']);
        $this->assertSame(1, $result['gifts'][0]['granted_quantity']);

        // Thiếu bánh -> bấm mã bị từ chối.
        $otherUser = User::factory()->create();
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->applyCombo($combo, $this->pricedCart($otherUser, [[$coffee, 1], [$tea, 1]]), $otherUser);
    }

    // Số lượng yêu cầu khác nhau từng sản phẩm: cần 2xA+1xB, giỏ có 5xA+3xB -> applications=min(2,3)=2, không phải 3.
    public function test_combo_applications_uses_min_across_items_with_different_required_quantities(): void
    {
        $user = User::factory()->create();
        $productA = $this->makeProduct(['base_price' => 10000]);
        $productB = $this->makeProduct(['base_price' => 10000]);
        $gift = $this->makeProduct(['base_price' => 5000]);
        $combo = $this->makeCombo([[$productA, 2], [$productB, 1]], gift: ['product' => $gift, 'quantity' => 1]);

        $result = $this->applyCombo($combo, $this->pricedCart($user, [[$productA, 5], [$productB, 3]]), $user);

        $this->assertCount(1, $result['gifts']);
        $this->assertSame(2, $result['gifts'][0]['applications']);
        $this->assertSame(2, $result['gifts'][0]['granted_quantity']);
    }

    // Combo CHỈ giảm giá % - tính trên đúng giá trị các sản phẩm trong combo, giới hạn bởi max_discount_amount.
    public function test_combo_percent_discount_only(): void
    {
        $user = User::factory()->create();
        $coffee = $this->makeProduct(['base_price' => 40000]);
        $tea = $this->makeProduct(['base_price' => 30000]);
        $other = $this->makeProduct(['base_price' => 100000]);
        $combo = $this->makeCombo([[$coffee, 1], [$tea, 1]], discount: ['type' => 'percent', 'value' => 20, 'max' => 10000]);

        // Giỏ có thêm sản phẩm KHÔNG thuộc combo -> không được tính vào giảm giá combo.
        $result = $this->applyCombo($combo, $this->pricedCart($user, [[$coffee, 1], [$tea, 1], [$other, 1]]), $user);

        $this->assertEmpty($result['gifts']);
        // 20% của (40.000+30.000)=70.000 = 14.000, nhưng bị trần max_discount_amount=10.000.
        $this->assertSame(10000.0, $result['discount']);
    }

    // Combo CHỈ giảm tiền cứng - nhân theo số lần đủ combo (applications), không vượt quá eligible subtotal.
    public function test_combo_fixed_discount_scales_with_applications(): void
    {
        $user = User::factory()->create();
        $coffee = $this->makeProduct(['base_price' => 40000]);
        $combo = $this->makeCombo([[$coffee, 1]], discount: ['type' => 'fixed', 'value' => 5000]);

        // Mua 2 -> applications=2 -> giảm 5.000*2=10.000 (không vượt eligible subtotal 80.000).
        $result = $this->applyCombo($combo, $this->pricedCart($user, [[$coffee, 2]]), $user);

        $this->assertSame(10000.0, $result['discount']);
    }

    // Combo bật CẢ 2 thành phần -> áp 1 mã là nhận cùng lúc cả tiền giảm lẫn quà.
    public function test_combo_with_both_discount_and_gift_gives_both_rewards(): void
    {
        $user = User::factory()->create();
        $coffee = $this->makeProduct(['base_price' => 40000]);
        $gift = $this->makeProduct(['base_price' => 5000]);
        $promotion = $this->makeCombo(
            [[$coffee, 1]],
            discount: ['type' => 'fixed', 'value' => 3000],
            gift: ['product' => $gift, 'quantity' => 1]
        );

        $result = $this->applyCombo($promotion, $this->pricedCart($user, [[$coffee, 1]]), $user);

        $this->assertSame(3000.0, $result['discount']);
        $this->assertCount(1, $result['gifts']);
        $this->assertSame($promotion->id, $result['promotion']->id);
        $this->assertSame($promotion->id, $result['gifts'][0]['promotion']->id);
    }

    // Combo KHÔNG bao giờ tự áp: không nhập mã thì dù giỏ đủ món vẫn không có giảm giá/quà nào.
    public function test_combo_is_never_applied_automatically(): void
    {
        $user = User::factory()->create();
        $coffee = $this->makeProduct(['base_price' => 40000]);
        $gift = $this->makeProduct(['base_price' => 5000]);
        $this->makeCombo(
            [[$coffee, 1]],
            discount: ['type' => 'fixed', 'value' => 3000],
            gift: ['product' => $gift, 'quantity' => 1]
        );

        $items = $this->pricedCart($user, [[$coffee, 1]]);
        $result = $this->service()->resolveBestDiscount($items, 40000, $user, 'delivery', 1);

        $this->assertNull($result['promotion']);
        $this->assertSame(0.0, $result['discount']);
        $this->assertEmpty($result['gifts']);
    }

    // Mỗi đơn chỉ giữ 1 mã: đổi từ mã combo sang mã thường thì quà + giảm giá của combo mất hẳn,
    // thay bằng đúng ưu đãi của mã mới.
    public function test_switching_from_combo_code_to_normal_code_drops_combo_rewards(): void
    {
        $user = User::factory()->create();
        $coffee = $this->makeProduct(['base_price' => 100000]);
        $gift = $this->makeProduct(['base_price' => 5000]);

        $combo = $this->makeCombo(
            [[$coffee, 1]],
            discount: ['type' => 'fixed', 'value' => 5000],
            gift: ['product' => $gift, 'quantity' => 1]
        );
        Promotion::create([
            'code' => 'BIGDEAL', 'scope' => 'order', 'type' => 'percent', 'value' => 50,
            'apply_for' => 'all', 'applies_to' => 'all', 'is_active' => true, 'is_recurring' => false,
        ]);

        $items = $this->pricedCart($user, [[$coffee, 1]]);

        // Chọn mã combo trước: được 5.000đ + 1 quà.
        $comboResult = $this->applyCombo($combo, $items, $user);
        $this->assertSame(5000.0, $comboResult['discount']);
        $this->assertCount(1, $comboResult['gifts']);

        // Đổi sang mã thường: chỉ còn ưu đãi của mã mới, quà combo biến mất.
        $switched = $this->service()->resolveBestDiscount($items, 100000, $user, 'delivery', 1, 'BIGDEAL');
        $this->assertSame('BIGDEAL', $switched['promotion']->code);
        $this->assertSame(50000.0, $switched['discount']);
        $this->assertEmpty($switched['gifts']);
    }

    // ===== 3.5 HẠNG THÀNH VIÊN (apply_for) — mã gắn ĐÚNG hạng, không phải "hạng đó trở lên" =====

    // Hạng cao hơn KHÔNG được dùng ké mã dành riêng cho hạng thấp hơn (VD: Kim cương không dùng được
    // mã "chào mừng thành viên Mới").
    public function test_higher_tier_member_cannot_use_code_restricted_to_lower_tier(): void
    {
        $diamond = User::factory()->create(['membership_level' => 'diamond']);
        $promotion = Promotion::create([
            'code' => 'CHAOMOI', 'scope' => 'order', 'type' => 'fixed', 'value' => 10000,
            'apply_for' => 'new', 'applies_to' => 'all', 'is_active' => true, 'is_recurring' => false,
        ]);

        $result = $promotion->checkValidity($diamond, 100000, 'delivery', 1);

        $this->assertFalse($result['valid']);
    }

    // Hạng thấp hơn cũng không dùng được mã dành cho hạng cao hơn.
    public function test_lower_tier_member_cannot_use_code_restricted_to_higher_tier(): void
    {
        $newMember = User::factory()->create(['membership_level' => 'new']);
        $promotion = Promotion::create([
            'code' => 'VIPGOLD', 'scope' => 'order', 'type' => 'fixed', 'value' => 10000,
            'apply_for' => 'gold', 'applies_to' => 'all', 'is_active' => true, 'is_recurring' => false,
        ]);

        $result = $promotion->checkValidity($newMember, 100000, 'delivery', 1);

        $this->assertFalse($result['valid']);
    }

    // Đúng hạng thì dùng được.
    public function test_exact_tier_match_can_use_the_code(): void
    {
        $gold = User::factory()->create(['membership_level' => 'gold']);
        $promotion = Promotion::create([
            'code' => 'VIPGOLD', 'scope' => 'order', 'type' => 'fixed', 'value' => 10000,
            'apply_for' => 'gold', 'applies_to' => 'all', 'is_active' => true, 'is_recurring' => false,
        ]);

        $result = $promotion->checkValidity($gold, 100000, 'delivery', 1);

        $this->assertTrue($result['valid']);
    }

    // ===== 3.6 GIỚI HẠN LƯỢT DÙNG / 1 TÀI KHOẢN (usage_limit_per_user) =====

    // Mặc định usage_limit_per_user = 1 (migration backfill) -> dùng 1 đơn rồi thì đơn thứ 2 bị chặn.
    public function test_default_usage_limit_per_user_blocks_second_order(): void
    {
        $user = User::factory()->create();
        $promotion = Promotion::create([
            'code' => 'ONCE10', 'scope' => 'order', 'type' => 'fixed', 'value' => 10000,
            'apply_for' => 'all', 'applies_to' => 'all', 'is_active' => true, 'is_recurring' => false,
            'usage_limit_per_user' => 1,
        ]);
        Order::create([
            'order_code' => 'HPY-' . strtoupper(uniqid()), 'user_id' => $user->id, 'promotion_id' => $promotion->id,
            'customer_name' => 'A', 'customer_phone' => '0900000000', 'total_amount' => 100000,
            'discount_amount' => 10000, 'final_amount' => 90000, 'payment_status' => 'unpaid',
            'payment_method' => 'cod', 'status' => 'pending', 'delivery_type' => 'delivery',
        ]);

        $result = $promotion->checkValidity($user, 100000, 'delivery', 1);

        $this->assertFalse($result['valid']);
        $this->assertSame('Bạn đã sử dụng mã giảm giá này rồi.', $result['message']);
    }

    // Admin nới usage_limit_per_user lên 3 -> cùng 1 tài khoản dùng được nhiều đơn, chỉ chặn ở lần thứ 4.
    public function test_admin_can_raise_usage_limit_per_user_to_allow_repeat_use(): void
    {
        $user = User::factory()->create();
        $promotion = Promotion::create([
            'code' => 'REPEAT3', 'scope' => 'order', 'type' => 'fixed', 'value' => 5000,
            'apply_for' => 'all', 'applies_to' => 'all', 'is_active' => true, 'is_recurring' => false,
            'usage_limit_per_user' => 3,
        ]);

        for ($i = 0; $i < 3; $i++) {
            $before = $promotion->checkValidity($user, 100000, 'delivery', 1);
            $this->assertTrue($before['valid'], "Lượt thứ " . ($i + 1) . " phải còn hợp lệ");

            Order::create([
                'order_code' => 'HPY-' . strtoupper(uniqid()), 'user_id' => $user->id, 'promotion_id' => $promotion->id,
                'customer_name' => 'A', 'customer_phone' => '0900000000', 'total_amount' => 100000,
                'discount_amount' => 5000, 'final_amount' => 95000, 'payment_status' => 'unpaid',
                'payment_method' => 'cod', 'status' => 'pending', 'delivery_type' => 'delivery',
            ]);
        }

        // Lượt thứ 4 -> vượt giới hạn 3 -> bị chặn.
        $fourth = $promotion->checkValidity($user, 100000, 'delivery', 1);
        $this->assertFalse($fourth['valid']);
        $this->assertSame('Bạn đã dùng mã này tối đa 3 lần.', $fourth['message']);
    }

    // usage_limit_per_user = NULL -> không giới hạn số lần/tài khoản (chỉ còn usage_limit tổng chặn).
    public function test_null_usage_limit_per_user_means_unlimited(): void
    {
        $user = User::factory()->create();
        $promotion = Promotion::create([
            'code' => 'UNLIMITED', 'scope' => 'order', 'type' => 'fixed', 'value' => 5000,
            'apply_for' => 'all', 'applies_to' => 'all', 'is_active' => true, 'is_recurring' => false,
            'usage_limit_per_user' => null,
        ]);

        for ($i = 0; $i < 5; $i++) {
            Order::create([
                'order_code' => 'HPY-' . strtoupper(uniqid()), 'user_id' => $user->id, 'promotion_id' => $promotion->id,
                'customer_name' => 'A', 'customer_phone' => '0900000000', 'total_amount' => 100000,
                'discount_amount' => 5000, 'final_amount' => 95000, 'payment_status' => 'unpaid',
                'payment_method' => 'cod', 'status' => 'pending', 'delivery_type' => 'delivery',
            ]);
        }

        $result = $promotion->checkValidity($user, 100000, 'delivery', 1);
        $this->assertTrue($result['valid']);
    }

    // Đơn đã HỦY không tính vào số lượt đã dùng của tài khoản.
    public function test_cancelled_orders_do_not_count_toward_per_user_limit(): void
    {
        $user = User::factory()->create();
        $promotion = Promotion::create([
            'code' => 'ONCE10B', 'scope' => 'order', 'type' => 'fixed', 'value' => 10000,
            'apply_for' => 'all', 'applies_to' => 'all', 'is_active' => true, 'is_recurring' => false,
            'usage_limit_per_user' => 1,
        ]);
        Order::create([
            'order_code' => 'HPY-' . strtoupper(uniqid()), 'user_id' => $user->id, 'promotion_id' => $promotion->id,
            'customer_name' => 'A', 'customer_phone' => '0900000000', 'total_amount' => 100000,
            'discount_amount' => 10000, 'final_amount' => 90000, 'payment_status' => 'unpaid',
            'payment_method' => 'cod', 'status' => 'cancelled', 'delivery_type' => 'delivery',
        ]);

        $result = $promotion->checkValidity($user, 100000, 'delivery', 1);
        $this->assertTrue($result['valid']);
    }

    // Đơn vừa đặt xong đang "chờ xác nhận" (status=pending, CHƯA ai duyệt) đã phải tính là "đã dùng"
    // ngay lập tức — cả usage_limit tổng lẫn usage_limit_per_user, không cần chờ đơn được xác nhận.
    public function test_pending_order_counts_as_used_immediately_without_confirmation(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $product = $this->makeProduct(['base_price' => 100000]);
        $address = \App\Models\UserAddress::create([
            'user_id' => $user->id, 'fullname' => 'Nguyễn Văn A', 'phone' => '0911222333',
            'province' => 'Thành phố Hồ Chí Minh', 'district' => 'Quận 8', 'ward' => 'Phường Chánh Hưng',
            'specific_address' => '180 Cao Lỗ', 'latitude' => 10.7383043, 'longitude' => 106.6788227,
        ]);
        $promotion = Promotion::create([
            'code' => 'PENDNOW', 'scope' => 'order', 'type' => 'fixed', 'value' => 10000,
            'apply_for' => 'all', 'applies_to' => 'all', 'is_active' => true, 'is_recurring' => false,
            'usage_limit' => 5, 'usage_limit_per_user' => 1,
        ]);

        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1, 'unit_price' => 100000]);

        $order = app(\App\Services\OrderService::class)->create($user, [
            'idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
            'address_id' => $address->id,
            'coupon_code' => 'PENDNOW',
        ], 'cod');

        // Đơn vừa tạo còn nguyên trạng thái "chờ xác nhận" — chưa ai bấm duyệt gì cả.
        $this->assertSame('pending', $order->status);

        // usage_limit tổng đã tăng ngay (2. → 1 lượt đã dùng).
        $this->assertSame(1, $promotion->fresh()->used_count);

        // usage_limit_per_user cũng đã chặn ngay cho lượt tiếp theo của CHÍNH user này.
        $result = $promotion->fresh()->checkValidity($user->fresh(), 100000, 'delivery', 1);
        $this->assertFalse($result['valid'], 'Đơn đang chờ xác nhận đã phải tính là đã dùng mã');
    }

    // ===== 4. TRẠNG THÁI / THỜI GIAN / GIỚI HẠN =====

    public function test_promotion_not_started_yet_is_rejected(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct(['base_price' => 50000]);
        $items = $this->pricedCart($user, [[$product, 1]]);

        Promotion::create([
            'code' => 'FUTURE',
            'scope' => 'order',
            'type' => 'percent',
            'value' => 10,
            'apply_for' => 'all',
            'applies_to' => 'all',
            'is_active' => true,
            'is_recurring' => false,
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
            'code' => 'EXPIRED',
            'scope' => 'order',
            'type' => 'percent',
            'value' => 10,
            'apply_for' => 'all',
            'applies_to' => 'all',
            'is_active' => true,
            'is_recurring' => false,
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
            'code' => 'LOCKED',
            'scope' => 'order',
            'type' => 'percent',
            'value' => 10,
            'apply_for' => 'all',
            'applies_to' => 'all',
            'is_active' => false,
            'is_recurring' => false,
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
            'code' => 'USEDUP',
            'scope' => 'order',
            'type' => 'percent',
            'value' => 10,
            'apply_for' => 'all',
            'applies_to' => 'all',
            'is_active' => true,
            'is_recurring' => false,
            'usage_limit' => 5,
            'used_count' => 5,
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->service()->resolveBestDiscount($items, 50000, $user, 'delivery', 1, 'USEDUP');
    }

    // Mã combo giờ nhập/bấm được ở ô mã giảm giá như mọi mã khác, miễn là giỏ đã đủ tổ hợp món.
    public function test_combo_code_can_be_applied_from_the_coupon_box(): void
    {
        $user = User::factory()->create();
        $coffee = $this->makeProduct(['base_price' => 30000]);
        $tea = $this->makeProduct(['base_price' => 20000]);
        $promotion = $this->makeCombo([[$coffee, 2]], gift: ['product' => $tea, 'quantity' => 1]);
        $items = $this->pricedCart($user, [[$coffee, 2]]);

        $result = $this->service()->resolveBestDiscount($items, 60000, $user, 'delivery', 2, $promotion->code);

        $this->assertSame($promotion->id, $result['promotion']->id);
        $this->assertCount(1, $result['gifts']);
    }

    // Combo đã hết lượt dùng thì không còn nằm trong danh sách chip gợi ý cho khách bấm.
    public function test_exhausted_combo_is_not_listed_as_applicable(): void
    {
        $user = User::factory()->create();
        $coffee = $this->makeProduct(['base_price' => 30000]);
        $tea = $this->makeProduct(['base_price' => 20000]);
        $promotion = $this->makeCombo([[$coffee, 2]], gift: ['product' => $tea, 'quantity' => 1]);
        $items = $this->pricedCart($user, [[$coffee, 2]]);

        $this->assertCount(1, $this->service()->applicableCombos($items, 'delivery'));

        $promotion->update(['usage_limit' => 1, 'used_count' => 1]);
        $this->assertCount(0, $this->service()->applicableCombos($items, 'delivery'));
    }

    // ===== 5. KHÔNG ÂM TIỀN + TƯƠNG THÍCH NGƯỢC =====

    public function test_discount_never_exceeds_eligible_subtotal(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct(['base_price' => 30000]);
        $items = $this->pricedCart($user, [[$product, 1]]);

        Promotion::create([
            'code' => 'HUGE',
            'scope' => 'order',
            'type' => 'fixed',
            'value' => 999999,
            'apply_for' => 'all',
            'applies_to' => 'all',
            'is_active' => true,
            'is_recurring' => false,
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
            'code' => 'OLD10',
            'type' => 'percent',
            'value' => 10,
            'apply_for' => 'all',
            'applies_to' => 'all',
            'is_active' => true,
            'is_recurring' => false,
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
            'code' => 'OLDFIX',
            'type' => 'fixed',
            'value' => 25000,
            'apply_for' => 'all',
            'applies_to' => 'all',
            'is_active' => true,
            'is_recurring' => false,
        ]);

        $result = $this->service()->resolveBestDiscount($items, 100000, $user, 'delivery', 1, 'OLDFIX');

        $this->assertSame(25000.0, $result['discount']);
    }
}
