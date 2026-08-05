<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CartItemTopping;
use App\Models\Favorite;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\Topping;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Bổ sung test cho các hành động phía khách hàng trước đây chưa có: yêu thích sản phẩm (favorite
 * toggle), giỏ hàng (thêm tất cả từ yêu thích/đổi số lượng/chọn sản phẩm để thanh toán), sổ địa chỉ
 * (sửa/xóa/đặt mặc định - trước đây chỉ có test cho "thêm mới"), và đặt lại đơn cũ (reorder).
 */
class FrontendCustomerActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // updateAddress() đối chiếu province_code/ward_code với API hành chính thật (giống storeAddress
        // đã test ở AddressStoreTest.php) - mock để không gọi mạng thật trong test.
        \Illuminate\Support\Facades\Http::fake([
            'https://provinces.open-api.vn/api/v2/p/' => \Illuminate\Support\Facades\Http::response([
                ['code' => 79, 'name' => 'Thành phố Hồ Chí Minh', 'division_type' => 'thành phố trung ương', 'codename' => 'ho_chi_minh', 'phone_code' => 28, 'wards' => []],
            ], 200),
            'https://provinces.open-api.vn/api/v2/p/79?depth=2' => \Illuminate\Support\Facades\Http::response([
                'code' => 79,
                'name' => 'Thành phố Hồ Chí Minh',
                'wards' => [
                    ['code' => 25747, 'name' => 'Phường Chánh Hưng', 'division_type' => 'phường', 'codename' => 'phuong_chanh_hung', 'province_code' => 79],
                ],
            ], 200),
        ]);
    }

    private function makeProduct(array $overrides = []): Product
    {
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Drink', 'created_at' => now(), 'updated_at' => now(),
        ]);

        return Product::create(array_merge([
            'name' => 'Trà sữa', 'slug' => 'tra-sua-' . Str::random(6), 'sku' => 'SKU-' . Str::random(6),
            'base_price' => 30000, 'category_id' => $categoryId, 'is_active' => true,
        ], $overrides));
    }

    private function makeAddress(User $user, array $overrides = []): UserAddress
    {
        return UserAddress::create(array_merge([
            'user_id' => $user->id, 'fullname' => 'Nguyễn Văn A', 'phone' => '0912345678',
            'province' => 'Thành phố Hồ Chí Minh', 'district' => 'Quận 8', 'ward' => 'Phường Chánh Hưng',
            'specific_address' => '180 Cao Lỗ', 'latitude' => 10.7383043, 'longitude' => 106.6788227,
        ], $overrides));
    }

    // ───────────────────────── Yêu thích (Favorite) ─────────────────────────

    public function test_toggle_favorite_adds_then_removes_product(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $product = $this->makeProduct();

        $response = $this->actingAs($user)->postJson('/favorite/toggle', ['product_id' => $product->id]);
        $response->assertOk()->assertJson(['success' => true, 'status' => 'added']);
        $this->assertDatabaseHas('favorites', ['user_id' => $user->id, 'product_id' => $product->id]);

        $response = $this->actingAs($user)->postJson('/favorite/toggle', ['product_id' => $product->id]);
        $response->assertOk()->assertJson(['success' => true, 'status' => 'removed']);
        $this->assertDatabaseMissing('favorites', ['user_id' => $user->id, 'product_id' => $product->id]);
    }

    public function test_toggle_favorite_requires_product_id(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($user)->postJson('/favorite/toggle', []);

        $response->assertOk()->assertJson(['success' => false]);
        $this->assertSame(0, Favorite::count());
    }

    // ───────────────────────── Giỏ hàng (Cart) ─────────────────────────

    public function test_cart_add_all_from_favorites_skips_inactive_products(): void
    {
        // Route nằm trong nhóm middleware 'auth' nên khách vãng lai bị chặn ở đó (401 JSON) - không
        // bao giờ chạm tới nhánh Auth::check() bên trong addAll(), dù controller vẫn tự kiểm tra thêm.
        $this->postJson('/cart/add-all')->assertStatus(401);

        $user = User::factory()->create(['role' => 'customer']);
        $activeProduct = $this->makeProduct(['name' => 'Trà sữa còn bán', 'base_price' => 30000]);
        $inactiveProduct = $this->makeProduct(['name' => 'Trà sữa ngừng bán', 'is_active' => false]);
        Favorite::create(['user_id' => $user->id, 'product_id' => $activeProduct->id]);
        Favorite::create(['user_id' => $user->id, 'product_id' => $inactiveProduct->id]);

        $response = $this->actingAs($user)->postJson('/cart/add-all');

        $response->assertOk()->assertJson(['success' => true, 'count' => 1]);
        $cart = Cart::where('user_id', $user->id)->first();
        $this->assertSame(1, CartItem::where('cart_id', $cart->id)->count());
        $this->assertDatabaseHas('cart_items', ['cart_id' => $cart->id, 'product_id' => $activeProduct->id]);
    }

    public function test_cart_add_all_merges_into_existing_matching_item_instead_of_duplicating(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $product = $this->makeProduct();
        Favorite::create(['user_id' => $user->id, 'product_id' => $product->id]);

        $this->actingAs($user);
        $this->postJson('/cart/add-all')->assertOk();
        $this->postJson('/cart/add-all')->assertOk();

        $cart = Cart::where('user_id', $user->id)->first();
        $item = CartItem::where('cart_id', $cart->id)->where('product_id', $product->id)->first();
        $this->assertNotNull($item);
        $this->assertSame(2, (int) $item->quantity);
        $this->assertSame(1, CartItem::where('cart_id', $cart->id)->count());
    }

    public function test_cart_update_quantity_only_affects_own_cart_item(): void
    {
        $owner = User::factory()->create(['role' => 'customer']);
        $stranger = User::factory()->create(['role' => 'customer']);
        $product = $this->makeProduct();
        $cart = Cart::create(['user_id' => $owner->id]);
        $item = CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1, 'unit_price' => 30000]);

        // Người khác không đổi được số lượng của item không thuộc giỏ hàng mình.
        $this->actingAs($stranger)->postJson('/cart/update', ['item_id' => $item->id, 'quantity' => 5])->assertOk();
        $this->assertSame(1, (int) $item->fresh()->quantity);

        // Chính chủ đổi số lượng thành công.
        $this->actingAs($owner)->postJson('/cart/update', ['item_id' => $item->id, 'quantity' => 5])->assertOk();
        $this->assertSame(5, (int) $item->fresh()->quantity);
    }

    public function test_cart_update_rejects_quantity_out_of_range(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $product = $this->makeProduct();
        $cart = Cart::create(['user_id' => $user->id]);
        $item = CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1, 'unit_price' => 30000]);

        $this->actingAs($user)->postJson('/cart/update', ['item_id' => $item->id, 'quantity' => 0])->assertStatus(422);
        $this->actingAs($user)->postJson('/cart/update', ['item_id' => $item->id, 'quantity' => 100])->assertStatus(422);
        $this->assertSame(1, (int) $item->fresh()->quantity);
    }

    /**
     * "Xóa đã chọn" (cart.remove-many) - trước đây giỏ hàng không có cách nào xóa nhiều sản phẩm
     * cùng lúc, phải xóa từng món một. Chỉ được xóa item thuộc CHÍNH giỏ hàng của mình.
     */
    public function test_cart_remove_many_deletes_only_specified_items_from_own_cart(): void
    {
        $owner = User::factory()->create(['role' => 'customer']);
        $stranger = User::factory()->create(['role' => 'customer']);
        $product = $this->makeProduct();
        $ownerCart = Cart::create(['user_id' => $owner->id]);
        $strangerCart = Cart::create(['user_id' => $stranger->id]);

        $itemToRemove1 = CartItem::create(['cart_id' => $ownerCart->id, 'product_id' => $product->id, 'quantity' => 1, 'unit_price' => 30000]);
        $itemToRemove2 = CartItem::create(['cart_id' => $ownerCart->id, 'product_id' => $product->id, 'quantity' => 1, 'unit_price' => 30000]);
        $itemToKeep = CartItem::create(['cart_id' => $ownerCart->id, 'product_id' => $product->id, 'quantity' => 1, 'unit_price' => 30000]);
        $strangerItem = CartItem::create(['cart_id' => $strangerCart->id, 'product_id' => $product->id, 'quantity' => 1, 'unit_price' => 30000]);

        $response = $this->actingAs($owner)->postJson('/cart/remove-many', [
            'item_ids' => [$itemToRemove1->id, $itemToRemove2->id, $strangerItem->id],
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseMissing('cart_items', ['id' => $itemToRemove1->id]);
        $this->assertDatabaseMissing('cart_items', ['id' => $itemToRemove2->id]);
        $this->assertDatabaseHas('cart_items', ['id' => $itemToKeep->id]);
        $this->assertDatabaseHas('cart_items', ['id' => $strangerItem->id]);
    }

    public function test_cart_remove_many_requires_at_least_one_id(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $this->actingAs($user)->postJson('/cart/remove-many', ['item_ids' => []])->assertStatus(422);
    }

    /**
     * "Xóa tất cả" (cart.clear) - chỉ xóa sạch giỏ hàng của CHÍNH mình, không đụng giỏ hàng người khác.
     */
    public function test_cart_clear_empties_only_own_cart(): void
    {
        $owner = User::factory()->create(['role' => 'customer']);
        $stranger = User::factory()->create(['role' => 'customer']);
        $product = $this->makeProduct();
        $ownerCart = Cart::create(['user_id' => $owner->id]);
        $strangerCart = Cart::create(['user_id' => $stranger->id]);
        CartItem::create(['cart_id' => $ownerCart->id, 'product_id' => $product->id, 'quantity' => 1, 'unit_price' => 30000]);
        CartItem::create(['cart_id' => $ownerCart->id, 'product_id' => $product->id, 'quantity' => 2, 'unit_price' => 30000]);
        $strangerItem = CartItem::create(['cart_id' => $strangerCart->id, 'product_id' => $product->id, 'quantity' => 1, 'unit_price' => 30000]);

        $response = $this->actingAs($owner)->postJson('/cart/clear');

        $response->assertOk()->assertJson(['success' => true, 'count' => 0]);
        $this->assertSame(0, CartItem::where('cart_id', $ownerCart->id)->count());
        $this->assertDatabaseHas('cart_items', ['id' => $strangerItem->id]);
    }

    public function test_cart_clear_with_no_cart_returns_empty_state_without_error(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($user)->postJson('/cart/clear');

        $response->assertOk()->assertJson(['success' => true, 'count' => 0]);
    }

    public function test_cart_set_selected_only_accepts_ids_from_own_cart(): void
    {
        $owner = User::factory()->create(['role' => 'customer']);
        $stranger = User::factory()->create(['role' => 'customer']);
        $product = $this->makeProduct();
        $ownerCart = Cart::create(['user_id' => $owner->id]);
        $strangerCart = Cart::create(['user_id' => $stranger->id]);
        $ownItem = CartItem::create(['cart_id' => $ownerCart->id, 'product_id' => $product->id, 'quantity' => 1, 'unit_price' => 30000]);
        $strangerItem = CartItem::create(['cart_id' => $strangerCart->id, 'product_id' => $product->id, 'quantity' => 1, 'unit_price' => 30000]);

        $response = $this->actingAs($owner)->postJson('/cart/set-selected', [
            'selected_item_ids' => [$ownItem->id, $strangerItem->id],
        ]);

        // Chỉ ID thuộc giỏ hàng của CHÍNH mình được chấp nhận - ID của người khác bị lọc bỏ âm thầm,
        // không lưu vào session, dù request gửi lên gộp chung cả 2.
        $response->assertOk()->assertJson(['success' => true, 'count' => 1, 'selected' => [$ownItem->id]]);
        $this->assertSame([$ownItem->id], session('selected_cart_item_ids'));
    }

    public function test_cart_set_selected_rejects_when_no_valid_ids(): void
    {
        $owner = User::factory()->create(['role' => 'customer']);
        $stranger = User::factory()->create(['role' => 'customer']);
        $product = $this->makeProduct();
        $strangerCart = Cart::create(['user_id' => $stranger->id]);
        $strangerItem = CartItem::create(['cart_id' => $strangerCart->id, 'product_id' => $product->id, 'quantity' => 1, 'unit_price' => 30000]);
        Cart::create(['user_id' => $owner->id]);

        $response = $this->actingAs($owner)->postJson('/cart/set-selected', [
            'selected_item_ids' => [$strangerItem->id],
        ]);

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    // ───────────────────────── Sổ địa chỉ (Address) ─────────────────────────

    public function test_update_address_changes_fields_and_reassigns_default(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $first = $this->makeAddress($user, ['fullname' => 'Địa chỉ 1', 'is_default' => true]);
        $second = $this->makeAddress($user, ['fullname' => 'Địa chỉ 2', 'is_default' => false]);

        $response = $this->actingAs($user)->postJson("/profile/address/{$second->id}", [
            'fullname' => 'Địa chỉ 2 (đã sửa)',
            'phone' => '0987654321',
            'province_code' => 79,
            'ward_code' => 25747,
            'specific_address' => '180 Cao Lỗ',
            'type' => 'home',
            'latitude' => 10.7383043,
            'longitude' => 106.6788227,
            'location_method' => 'map',
            'is_default' => '1',
        ]);

        $response->assertOk();
        $this->assertSame('Địa chỉ 2 (đã sửa)', $second->fresh()->fullname);
        $this->assertTrue((bool) $second->fresh()->is_default);
        $this->assertFalse((bool) $first->fresh()->is_default);
    }

    public function test_delete_address_promotes_another_address_to_default_when_default_removed(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $default = $this->makeAddress($user, ['fullname' => 'Mặc định', 'is_default' => true]);
        $other = $this->makeAddress($user, ['fullname' => 'Còn lại', 'is_default' => false]);

        $response = $this->actingAs($user)->postJson("/profile/address/{$default->id}/delete");

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseMissing('user_addresses', ['id' => $default->id]);
        $this->assertTrue((bool) $other->fresh()->is_default);
    }

    public function test_delete_address_does_not_affect_another_users_address(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $stranger = User::factory()->create(['role' => 'customer']);
        $strangerAddress = $this->makeAddress($stranger);

        $response = $this->actingAs($user)->postJson("/profile/address/{$strangerAddress->id}/delete");

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('user_addresses', ['id' => $strangerAddress->id]);
    }

    public function test_set_default_address_unsets_previous_default(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $first = $this->makeAddress($user, ['is_default' => true]);
        $second = $this->makeAddress($user, ['is_default' => false]);

        $response = $this->actingAs($user)->postJson("/profile/address/{$second->id}/default");

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertTrue((bool) $second->fresh()->is_default);
        $this->assertFalse((bool) $first->fresh()->is_default);
    }

    public function test_set_default_address_cannot_target_another_users_address(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $stranger = User::factory()->create(['role' => 'customer']);
        $strangerAddress = $this->makeAddress($stranger, ['is_default' => true]);

        $this->actingAs($user)->postJson("/profile/address/{$strangerAddress->id}/default")->assertOk();

        $this->assertTrue((bool) $strangerAddress->fresh()->is_default);
    }

    // ───────────────────────── Đặt lại đơn cũ (Reorder) ─────────────────────────

    private function makeOrderWithItem(User $user, Product $product, array $itemOverrides = []): Order
    {
        $order = Order::create([
            'user_id' => $user->id, 'order_code' => 'HPY-' . strtoupper(uniqid()),
            'customer_name' => $user->name, 'customer_phone' => '0900000000',
            'delivery_address' => 'Test address', 'total_amount' => 30000, 'discount_amount' => 0,
            'final_amount' => 30000, 'payment_status' => 'paid', 'payment_method' => 'cash',
            'status' => 'completed', 'delivery_type' => 'pickup',
        ]);
        OrderItem::create(array_merge([
            'order_id' => $order->id, 'product_id' => $product->id, 'product_name' => $product->name,
            'quantity' => 2, 'unit_price' => 30000,
        ], $itemOverrides));

        return $order;
    }

    public function test_reorder_adds_previous_items_to_cart_and_redirects_to_checkout(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $product = $this->makeProduct(['base_price' => 30000]);
        $order = $this->makeOrderWithItem($user, $product, ['quantity' => 3]);

        $response = $this->actingAs($user)->post("/orders/{$order->id}/reorder");

        $response->assertRedirect(route('checkout'));
        $response->assertSessionHasNoErrors();
        $cart = Cart::where('user_id', $user->id)->first();
        $item = CartItem::where('cart_id', $cart->id)->where('product_id', $product->id)->first();
        $this->assertNotNull($item);
        $this->assertSame(3, (int) $item->quantity);
    }

    public function test_reorder_skips_products_that_are_no_longer_active(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $activeProduct = $this->makeProduct(['name' => 'Còn bán']);
        $inactiveProduct = $this->makeProduct(['name' => 'Ngừng bán', 'is_active' => false]);
        $order = Order::create([
            'user_id' => $user->id, 'order_code' => 'HPY-' . strtoupper(uniqid()),
            'customer_name' => $user->name, 'customer_phone' => '0900000000',
            'delivery_address' => 'Test address', 'total_amount' => 60000, 'discount_amount' => 0,
            'final_amount' => 60000, 'payment_status' => 'paid', 'payment_method' => 'cash',
            'status' => 'completed', 'delivery_type' => 'pickup',
        ]);
        OrderItem::create(['order_id' => $order->id, 'product_id' => $activeProduct->id, 'product_name' => $activeProduct->name, 'quantity' => 1, 'unit_price' => 30000]);
        OrderItem::create(['order_id' => $order->id, 'product_id' => $inactiveProduct->id, 'product_name' => $inactiveProduct->name, 'quantity' => 1, 'unit_price' => 30000]);

        $response = $this->actingAs($user)->post("/orders/{$order->id}/reorder");

        $response->assertRedirect(route('checkout'));
        $cart = Cart::where('user_id', $user->id)->first();
        $this->assertSame(1, CartItem::where('cart_id', $cart->id)->count());
        $this->assertDatabaseHas('cart_items', ['cart_id' => $cart->id, 'product_id' => $activeProduct->id]);
    }

    public function test_reorder_fails_when_no_products_are_still_active(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $product = $this->makeProduct(['is_active' => false]);
        $order = $this->makeOrderWithItem($user, $product);

        $response = $this->actingAs($user)->post("/orders/{$order->id}/reorder");

        $response->assertSessionHasErrors('order');
        $this->assertSame(0, CartItem::count());
    }

    public function test_reorder_rejects_another_users_order(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $stranger = User::factory()->create(['role' => 'customer']);
        $product = $this->makeProduct();
        $order = $this->makeOrderWithItem($stranger, $product);

        $this->actingAs($user)->post("/orders/{$order->id}/reorder")->assertStatus(404);
    }

    /**
     * Nút "Mua lại" trên trang danh sách đơn từng bị khóa "hidden md:inline-block" - ẩn hoàn toàn
     * trên di động, chỉ hiện trên desktop. Khóa lại bằng cách kiểm tra nút KHÔNG còn nằm trong 1
     * phần tử mang class "hidden" (Tailwind ẩn hẳn khỏi layout, không phải chỉ thu nhỏ).
     */
    public function test_reorder_button_is_visible_on_mobile_not_just_desktop(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $product = $this->makeProduct();
        $this->makeOrderWithItem($user, $product);

        $response = $this->actingAs($user)->get('/orders');
        $order = Order::first();

        $response->assertOk();
        $response->assertSee(route('orders.reorder', ['order' => $order->id]), false);
        $response->assertSee('action="' . route('orders.reorder', ['order' => $order->id]) . '" class="inline-block"', false);
    }
}
