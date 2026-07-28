<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserAddress;
use App\Services\ShippingQuoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Kiểm tra các endpoint frontend vừa chuyển từ form POST cổ điển sang submit qua fetch (AJAX).
 * Dùng postJson()/getJson() (chỉ gửi header Accept: application/json) thay vì tự set thêm
 * X-Requested-With — vì đó chính xác là những gì fetch() thật trong các file JS gửi lên, khác với
 * jQuery $.ajax() cũ. Test bằng X-Requested-With sẽ che giấu mất lỗi $request->ajax() không nhận ra
 * request từ fetch() (đã gặp thật khi viết bộ test này — xem commit sửa ajax() -> expectsJson()).
 */
class FrontendAjaxTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(array $overrides = []): Product
    {
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Drink', 'slug' => 'drink-' . Str::random(6), 'created_at' => now(), 'updated_at' => now(),
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

    // ───────────────────────── Checkout ─────────────────────────

    public function test_checkout_ajax_returns_json_error_when_store_closed(): void
    {
        Setting::setValue('orders_enabled', '0');
        $user = User::factory()->create();
        $token = (string) Str::uuid();

        $response = $this->actingAs($user)->withSession(['checkout_token' => $token])
            ->postJson('/checkout', ['address_id' => 1, 'idempotency_key' => $token]);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.checkout.0', 'Cửa hàng hiện đang tạm ngưng nhận đơn hàng.');

        Setting::setValue('orders_enabled', '1');
    }

    public function test_checkout_ajax_success_returns_redirect_url_and_creates_order(): void
    {
        $this->travelTo(Carbon::parse('14:00:00'));
        Setting::setValue('orders_enabled', '1');
        Setting::setValue('cod_enabled', '1');

        $this->mock(ShippingQuoteService::class, function ($mock) {
            $mock->shouldReceive('quote')->andReturn(['shipping_fee' => 0, 'weather_fee' => 0, 'distance_km' => 2.5]);
        });

        $user = User::factory()->create();
        $product = $this->makeProduct();
        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1, 'unit_price' => 30000]);
        $address = $this->makeAddress($user);
        $token = (string) Str::uuid();

        $response = $this->actingAs($user)->withSession(['checkout_token' => $token])
            ->postJson('/checkout', ['address_id' => $address->id, 'idempotency_key' => $token]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertNotNull($response->json('redirect_url'));
        $this->assertDatabaseHas('orders', ['user_id' => $user->id, 'payment_method' => 'cod']);
    }

    public function test_momo_checkout_ajax_returns_json_error_for_empty_cart(): void
    {
        $this->travelTo(Carbon::parse('14:00:00'));
        $user = User::factory()->create();
        $address = $this->makeAddress($user);

        $response = $this->actingAs($user)->postJson('/checkout/momo', [
            'address_id' => $address->id, 'payment_method' => 'momo', 'distance_km' => 2.5,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Giỏ hàng của bạn đang trống.');
    }

    // ───────────────────────── Products filter/pagination ─────────────────────────

    public function test_products_ajax_request_returns_partial_not_full_page(): void
    {
        $this->makeProduct(['name' => 'Cà phê đen']);

        $response = $this->getJson('/products');

        $response->assertOk();
        $response->assertSee('ajax-product-area', false);
        $response->assertDontSee('<html', false);
    }

    // ───────────────────────── Profile ─────────────────────────

    public function test_profile_update_ajax_returns_updated_user_json(): void
    {
        $user = User::create([
            'name' => 'Old Name', 'email' => 'ajax-profile@test.com', 'password' => bcrypt('password'),
            'role' => 'customer', 'is_active' => 1,
        ]);

        $response = $this->actingAs($user)->postJson('/profile', ['name' => 'New Name', 'phone' => '0912345678']);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $response->assertJsonPath('user.name', 'New Name');
        $this->assertSame('New Name', $user->fresh()->name);
    }

    public function test_profile_update_ajax_returns_422_on_invalid_phone(): void
    {
        $user = User::create([
            'name' => 'Test', 'email' => 'ajax-profile2@test.com', 'password' => bcrypt('password'),
            'role' => 'customer', 'is_active' => 1,
        ]);

        $response = $this->actingAs($user)->postJson('/profile', ['name' => 'Test', 'phone' => '0388359vsdasd']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('phone');
    }

    public function test_change_password_ajax_success(): void
    {
        $user = User::create([
            'name' => 'Test', 'email' => 'ajax-pass@test.com', 'password' => bcrypt('oldpassword'),
            'role' => 'customer', 'is_active' => 1,
        ]);

        $response = $this->actingAs($user)->postJson('/profile/change-password', [
            'current_password' => 'oldpassword', 'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('newpassword123', $user->fresh()->password));
    }

    public function test_change_password_ajax_returns_422_on_wrong_current_password(): void
    {
        $user = User::create([
            'name' => 'Test', 'email' => 'ajax-pass2@test.com', 'password' => bcrypt('oldpassword'),
            'role' => 'customer', 'is_active' => 1,
        ]);

        $response = $this->actingAs($user)->postJson('/profile/change-password', [
            'current_password' => 'wrongpassword', 'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.current_password.0', 'Mật khẩu hiện tại không chính xác.');
    }

    // ───────────────────────── Orders cancel ─────────────────────────

    public function test_cancel_order_ajax_success_updates_status(): void
    {
        $user = User::factory()->create();
        $order = Order::create([
            'order_code' => 'HPY-' . strtoupper(Str::random(8)), 'user_id' => $user->id,
            'customer_name' => $user->name, 'customer_phone' => '0900000000',
            'delivery_address' => 'Test address', 'total_amount' => 50000, 'discount_amount' => 0,
            'final_amount' => 50000, 'payment_status' => 'unpaid', 'payment_method' => 'cod',
            'status' => 'pending', 'delivery_type' => 'delivery',
        ]);

        $response = $this->actingAs($user)->postJson("/orders/{$order->id}/cancel", [
            'cancel_reason' => 'Đổi ý không muốn mua nữa',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertSame('cancelled', $order->fresh()->status);
    }

    public function test_cancel_order_ajax_returns_422_when_not_pending(): void
    {
        $user = User::factory()->create();
        $order = Order::create([
            'order_code' => 'HPY-' . strtoupper(Str::random(8)), 'user_id' => $user->id,
            'customer_name' => $user->name, 'customer_phone' => '0900000000',
            'delivery_address' => 'Test address', 'total_amount' => 50000, 'discount_amount' => 0,
            'final_amount' => 50000, 'payment_status' => 'paid', 'payment_method' => 'cod',
            'status' => 'completed', 'delivery_type' => 'delivery',
        ]);

        $response = $this->actingAs($user)->postJson("/orders/{$order->id}/cancel", [
            'cancel_reason' => 'Đổi ý không muốn mua nữa',
        ]);

        $response->assertStatus(422);
    }

    // ───────────────────────── Reviews ─────────────────────────

    public function test_review_ajax_returns_422_when_rating_missing(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        $order = Order::create([
            'order_code' => 'HPY-' . strtoupper(Str::random(8)), 'user_id' => $user->id,
            'customer_name' => $user->name, 'customer_phone' => '0900000000',
            'delivery_address' => 'Test address', 'total_amount' => 30000, 'discount_amount' => 0,
            'final_amount' => 30000, 'payment_status' => 'paid', 'payment_method' => 'cod',
            'status' => 'completed', 'delivery_type' => 'delivery',
        ]);
        OrderItem::create(['order_id' => $order->id, 'product_id' => $product->id, 'quantity' => 1, 'unit_price' => 30000]);

        $response = $this->actingAs($user)
            ->postJson("/orders/{$order->id}/products/{$product->id}/review", ['comment' => 'Ngon']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('rating');
    }

    public function test_review_ajax_success_returns_redirect_url(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        $order = Order::create([
            'order_code' => 'HPY-' . strtoupper(Str::random(8)), 'user_id' => $user->id,
            'customer_name' => $user->name, 'customer_phone' => '0900000000',
            'delivery_address' => 'Test address', 'total_amount' => 30000, 'discount_amount' => 0,
            'final_amount' => 30000, 'payment_status' => 'paid', 'payment_method' => 'cod',
            'status' => 'completed', 'delivery_type' => 'delivery',
        ]);
        OrderItem::create(['order_id' => $order->id, 'product_id' => $product->id, 'quantity' => 1, 'unit_price' => 30000]);

        $response = $this->actingAs($user)
            ->postJson("/orders/{$order->id}/products/{$product->id}/review", ['rating' => 5, 'comment' => 'Ngon lắm']);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertNotNull($response->json('redirect_url'));
        $this->assertDatabaseHas('reviews', ['order_id' => $order->id, 'product_id' => $product->id, 'rating' => 5]);
    }

    // ───────────────────────── Auth: login/register/forgot-password/otp ─────────────────────────

    public function test_login_ajax_returns_422_on_wrong_credentials(): void
    {
        $response = $this->postJson('/login', ['email' => 'nobody@test.com', 'password' => 'wrong']);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.login_error.0', 'Thông tin đăng nhập không chính xác.');
    }

    public function test_login_ajax_success_returns_redirect_url(): void
    {
        $user = User::create([
            'name' => 'Test', 'email' => 'ajax-login@test.com', 'password' => bcrypt('password123'),
            'role' => 'customer', 'is_active' => 1,
        ]);

        $response = $this->postJson('/login', ['email' => 'ajax-login@test.com', 'password' => 'password123']);

        $response->assertOk();
        $response->assertJson(['success' => true, 'redirect_url' => url('/')]);
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_ajax_rejects_locked_account(): void
    {
        User::create([
            'name' => 'Locked', 'email' => 'locked@test.com', 'password' => bcrypt('password123'),
            'role' => 'customer', 'is_active' => 0, 'lock_reason' => 'Vi phạm điều khoản',
        ]);

        $response = $this->postJson('/login', ['email' => 'locked@test.com', 'password' => 'password123']);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.login_error.0', 'Tài khoản của bạn đã bị khóa: Vi phạm điều khoản');
        $this->assertGuest();
    }

    public function test_register_ajax_returns_422_when_email_exists(): void
    {
        User::create([
            'name' => 'Existing', 'email' => 'exists@test.com', 'password' => bcrypt('password'),
            'role' => 'customer', 'is_active' => 1,
        ]);

        $response = $this->postJson('/register', [
            'full_name' => 'New User', 'email' => 'exists@test.com',
            'password' => 'Passw0rd!', 'password_confirmation' => 'Passw0rd!',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.register_error.0', 'Email đã được sử dụng.');
    }

    public function test_register_ajax_success_signals_otp_required(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $response = $this->postJson('/register', [
            'full_name' => 'New User', 'email' => 'newuser@test.com',
            'password' => 'Passw0rd!', 'password_confirmation' => 'Passw0rd!',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true, 'otp_required' => true, 'email' => 'newuser@test.com']);
    }

    public function test_verify_otp_ajax_returns_422_on_wrong_code(): void
    {
        $response = $this->withSession([
            'verify_email' => 'newuser@test.com',
            'verify_otp' => '1234',
            'verify_otp_time' => now(),
        ])->postJson('/verify-otp', ['otp' => ['9', '9', '9', '9']]);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.otp_error.0', 'Mã OTP không chính xác. Vui lòng thử lại.');
    }

    public function test_verify_otp_ajax_success_creates_user_and_logs_in(): void
    {
        $response = $this->withSession([
            'verify_email' => 'newuser2@test.com',
            'verify_otp' => '1234',
            'verify_otp_time' => now(),
            'register_data' => [
                'name' => 'New User 2', 'email' => 'newuser2@test.com', 'phone' => null,
                'password' => bcrypt('Passw0rd!'), 'role' => 'customer', 'is_active' => 1,
            ],
        ])->postJson('/verify-otp', ['otp' => ['1', '2', '3', '4']]);

        $response->assertOk();
        $response->assertJson(['success' => true, 'redirect_url' => url('/')]);
        $this->assertDatabaseHas('users', ['email' => 'newuser2@test.com']);
        $this->assertAuthenticated();
    }

    public function test_forgot_password_ajax_returns_422_when_email_not_found(): void
    {
        $response = $this->postJson('/forgot-password', ['recovery_contact' => 'nobody@test.com']);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.forgot_error.0', 'Email không tồn tại trong hệ thống.');
    }

    public function test_forgot_password_ajax_success_signals_otp_required(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        User::create([
            'name' => 'Test', 'email' => 'forgot@test.com', 'password' => bcrypt('password'),
            'role' => 'customer', 'is_active' => 1,
        ]);

        $response = $this->postJson('/forgot-password', ['recovery_contact' => 'forgot@test.com']);

        $response->assertOk();
        $response->assertJson(['success' => true, 'otp_required' => true, 'email' => 'forgot@test.com']);
    }
}
