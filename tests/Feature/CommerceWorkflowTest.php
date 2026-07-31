<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CommerceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_development_backdoor_routes_are_not_registered(): void
    {
        $this->get('/auto-login')->assertNotFound();
        $this->get('/test-db')->assertNotFound();
    }

    public function test_user_cannot_remove_another_users_cart_item(): void
    {
        [$owner, $attacker] = [User::factory()->create(), User::factory()->create()];
        $categoryId = DB::table('categories')->insertGetId(['name' => 'Drink', 'slug' => 'drink', 'created_at' => now(), 'updated_at' => now()]);
        $product = Product::create(['name' => 'Coffee', 'slug' => 'coffee', 'sku' => 'CF-1', 'base_price' => 25000, 'category_id' => $categoryId, 'is_active' => true]);
        $cart = Cart::create(['user_id' => $owner->id]);
        $item = CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1, 'unit_price' => 25000]);

        $this->actingAs($attacker)->postJson('/cart/remove', ['item_id' => $item->id])->assertOk();
        $this->assertDatabaseHas('cart_items', ['id' => $item->id]);
    }

    // Mã có min_quantity phải được chấp nhận khi giỏ đủ số ly. Trước đây validateCoupon() không
    // truyền tổng số lượng vào checkValidity() nên luôn coi là 0 món -> mọi mã min_quantity đều bị
    // từ chối ở trang checkout dù lúc đặt hàng thật lại hợp lệ.
    public function test_coupon_with_min_quantity_counts_cart_items(): void
    {
        $user = User::factory()->create();
        $categoryId = DB::table('categories')->insertGetId(['name' => 'Drink', 'slug' => 'drink', 'created_at' => now(), 'updated_at' => now()]);
        $product = Product::create(['name' => 'Tea', 'slug' => 'tea', 'sku' => 'TEA-9', 'base_price' => 30000, 'category_id' => $categoryId, 'is_active' => true]);
        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 3, 'unit_price' => 30000]);

        \App\Models\Promotion::create([
            'code' => 'GIAM50',
            'type' => 'fixed',
            'value' => 50000,
            'min_quantity' => 3,
            'is_active' => true,
            'apply_for' => 'all',
            'applies_to' => 'all',
        ]);

        $this->actingAs($user)
            ->postJson('/checkout/validate-coupon', ['coupon_code' => 'GIAM50', 'subtotal' => 90000])
            ->assertOk()
            ->assertJson(['valid' => true]);
    }

    // Ngược lại: giỏ chưa đủ số ly thì vẫn phải từ chối đúng lý do.
    public function test_coupon_with_min_quantity_rejected_when_cart_has_too_few_items(): void
    {
        $user = User::factory()->create();
        $categoryId = DB::table('categories')->insertGetId(['name' => 'Drink', 'slug' => 'drink', 'created_at' => now(), 'updated_at' => now()]);
        $product = Product::create(['name' => 'Tea', 'slug' => 'tea2', 'sku' => 'TEA-8', 'base_price' => 30000, 'category_id' => $categoryId, 'is_active' => true]);
        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1, 'unit_price' => 30000]);

        \App\Models\Promotion::create([
            'code' => 'COMBO3',
            'type' => 'fixed',
            'value' => 50000,
            'min_quantity' => 3,
            'is_active' => true,
            'apply_for' => 'all',
            'applies_to' => 'all',
        ]);

        $this->actingAs($user)
            ->postJson('/checkout/validate-coupon', ['coupon_code' => 'COMBO3', 'subtotal' => 30000])
            ->assertOk()
            ->assertJson(['valid' => false]);
    }

    public function test_completing_order_awards_points_only_once(): void
    {
        \App\Models\Setting::setValue('loyalty_money_per_point', 1000);
        $user = User::factory()->create(['points' => 0, 'membership_level' => 'new']);
        $order = Order::create($this->orderData($user, ['payment_status' => 'paid', 'final_amount' => 100000]));
        $workflow = app(OrderWorkflowService::class);

        $workflow->transition($order, 'confirmed');
        $workflow->transition($order->fresh(), 'shipping');
        $workflow->transition($order->fresh(), 'completed');
        $workflow->transition($order->fresh(), 'completed');

        $this->assertSame(100, (int) $user->fresh()->points);
        $this->assertSame(100, (int) $order->fresh()->loyalty_points_awarded);
    }

    public function test_unsigned_momo_return_cannot_mark_order_as_paid(): void
    {
        $user = User::factory()->create();
        $order = Order::create($this->orderData($user, ['payment_method' => 'momo']));

        $this->actingAs($user)->get('/checkout/momo/return?orderId=' . $order->order_code . '&resultCode=0')
            ->assertRedirect(route('orders'));
        $this->assertSame('unpaid', $order->fresh()->payment_status);
    }

    public function test_unsigned_vnpay_return_cannot_mark_order_as_paid(): void
    {
        $user = User::factory()->create();
        $order = Order::create($this->orderData($user, ['payment_method' => 'vnpay']));

        $this->actingAs($user)->get('/checkout/vnpay/return?vnp_TxnRef=' . $order->order_code . '&vnp_ResponseCode=00')
            ->assertRedirect(route('orders'));
        $this->assertSame('unpaid', $order->fresh()->payment_status);
    }

    private function orderData(User $user, array $overrides = []): array
    {
        return array_merge([
            'order_code' => 'HPY-' . strtoupper(bin2hex(random_bytes(4))),
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_phone' => '0900000000',
            'delivery_address' => 'Test address',
            'total_amount' => 100000,
            'discount_amount' => 0,
            'final_amount' => 100000,
            'payment_status' => 'unpaid',
            'payment_method' => 'cod',
            'status' => 'pending',
            'delivery_type' => 'delivery',
            'shipping_fee' => 0,
            'weather_fee' => 0,
            'peak_hour_fee' => 0,
        ], $overrides);
    }
}
