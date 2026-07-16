<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Material;
use App\Models\MaterialImport;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\InventoryService;
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

    public function test_cancelling_order_restores_reserved_material_to_same_lot(): void
    {
        $user = User::factory()->create();
        $categoryId = DB::table('categories')->insertGetId(['name' => 'Drink', 'slug' => 'drink', 'created_at' => now(), 'updated_at' => now()]);
        $product = Product::create(['name' => 'Tea', 'slug' => 'tea', 'sku' => 'TEA-1', 'base_price' => 20000, 'category_id' => $categoryId, 'is_active' => true]);
        $material = Material::create(['name' => 'Tea leaf', 'unit' => 'g', 'unit_price' => 100, 'current_stock' => 10, 'is_active' => true]);
        $lot = MaterialImport::create(['material_id' => $material->id, 'quantity' => 10, 'remaining_quantity' => 10, 'total_price' => 1000, 'expiration_date' => today()->addMonth()]);
        DB::table('product_materials')->insert(['product_id' => $product->id, 'material_id' => $material->id, 'quantity_used' => 2, 'created_at' => now(), 'updated_at' => now()]);
        $order = Order::create($this->orderData($user));

        app(InventoryService::class)->reserveForOrder($order, collect([(object) ['product_id' => $product->id, 'quantity' => 2]]));
        $this->assertEquals(6, (float) $lot->fresh()->remaining_quantity);
        app(OrderWorkflowService::class)->transition($order->fresh(), 'cancelled', 'Khách đổi ý không nhận hàng');

        $this->assertEquals(10, (float) $lot->fresh()->remaining_quantity);
        $this->assertEquals(10, (float) $material->fresh()->current_stock);
    }

    public function test_unsigned_momo_return_cannot_mark_order_as_paid(): void
    {
        $user = User::factory()->create();
        $order = Order::create($this->orderData($user, ['payment_method' => 'momo']));

        $this->actingAs($user)->get('/checkout/momo/return?orderId=' . $order->order_code . '&resultCode=0')
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
