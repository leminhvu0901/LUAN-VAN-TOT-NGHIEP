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

// File test này ban đầu (tên "FrontendAjaxTest") viết cho giai đoạn các endpoint frontend còn submit
// qua fetch() (AJAX) - dùng postJson()/getJson() để giả lập request thật của JS lúc đó. Dự án sau đó
// đã làm ngược lại: bỏ AJAX, quay về form POST/GET + redirect + full-page-reload truyền thống ở hầu
// hết các endpoint (chỉ còn giữ AJAX có chủ đích ở một số nơi: giỏ hàng/yêu thích, bản đồ + phí ship +
// coupon + tỉnh/phường lúc checkout, một số thao tác lặp lại nhanh ở màn POS lễ tân). Nhóm đăng nhập/
// đăng ký/OTP/quên-đặt lại mật khẩu từng giữ lại nhánh expectsJson() chỉ để không phá test, dù JS
// không còn gọi tới - nhánh đó đã bị xoá khỏi AuthController, nên các test dưới đây giờ dùng post()/
// get() thường và assert theo redirect + session flash, giống mọi endpoint đã bỏ AJAX khác.
//
// Vì vậy các test bên dưới chia làm 2 nhóm:
//  - Nhóm còn dùng postJson()/getJson(): endpoint đó VẪN hỗ trợ trả JSON thật (checkout, và validate
//    lỗi tự động của Laravel cho mọi request có Accept: application/json bất kể controller có code
//    riêng cho JSON hay không).
//  - Nhóm đã đổi sang post()/put()/get() thường: endpoint đó KHÔNG còn nhánh JSON nào nữa, chỉ còn
//    redirect + session flash - test phải giả lập đúng như trình duyệt thật gửi form.
class FrontendAjaxTest extends TestCase
{
    use RefreshDatabase;

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

    // ───────────────────────── Checkout ─────────────────────────

    public function test_checkout_returns_error_when_store_closed(): void
    {
        Setting::setValue('orders_enabled', '0');
        $user = User::factory()->create();
        $token = (string) Str::uuid();

        $response = $this->actingAs($user)->withSession(['checkout_token' => $token])
            ->post('/checkout', ['address_id' => 1, 'idempotency_key' => $token]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['checkout' => 'Cửa hàng hiện đang tạm ngưng nhận đơn hàng.']);

        Setting::setValue('orders_enabled', '1');
    }

    public function test_checkout_success_redirects_and_creates_order(): void
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
            ->post('/checkout', ['address_id' => $address->id, 'idempotency_key' => $token]);

        $response->assertRedirect(route('orders'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('orders', ['user_id' => $user->id, 'payment_method' => 'cod']);
    }

    public function test_vnpay_checkout_ajax_returns_json_error_for_empty_cart(): void
    {
        config([
            'services.vnpay.sandbox.tmn_code' => 'TESTTMN',
            'services.vnpay.sandbox.hash_secret' => 'TESTSECRET',
        ]);
        $this->travelTo(Carbon::parse('14:00:00'));
        $user = User::factory()->create();
        $address = $this->makeAddress($user);

        $response = $this->actingAs($user)->postJson('/checkout/vnpay', [
            'address_id' => $address->id, 'payment_method' => 'vnpay', 'distance_km' => 2.5,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Giỏ hàng của bạn đang trống.');
    }

    // ───────────────────────── Products filter/pagination ─────────────────────────

    public function test_products_page_renders_full_page_with_filtered_results(): void
    {
        $this->makeProduct(['name' => 'Cà phê đen']);

        $response = $this->get('/products');

        $response->assertOk();
        $response->assertSee('<html', false);
        $response->assertSee('Cà phê đen');
    }

    // ───────────────────────── Profile ─────────────────────────

    public function test_profile_update_redirects_back_with_success_and_saves_changes(): void
    {
        $user = User::create([
            'name' => 'Old Name', 'email' => 'ajax-profile@test.com', 'password' => bcrypt('password'),
            'role' => 'customer', 'is_active' => 1,
        ]);

        $response = $this->actingAs($user)->post('/profile', ['name' => 'New Name', 'phone' => '0912345678']);

        $response->assertRedirect();
        $response->assertSessionHas('success');
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

    public function test_change_password_success(): void
    {
        $user = User::create([
            'name' => 'Test', 'email' => 'ajax-pass@test.com', 'password' => bcrypt('oldpassword'),
            'role' => 'customer', 'is_active' => 1,
        ]);

        $response = $this->actingAs($user)->post('/profile/change-password', [
            'current_password' => 'oldpassword', 'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('newpassword123', $user->fresh()->password));
    }

    public function test_change_password_returns_error_on_wrong_current_password(): void
    {
        $user = User::create([
            'name' => 'Test', 'email' => 'ajax-pass2@test.com', 'password' => bcrypt('oldpassword'),
            'role' => 'customer', 'is_active' => 1,
        ]);

        $response = $this->actingAs($user)->post('/profile/change-password', [
            'current_password' => 'wrongpassword', 'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['current_password' => 'Mật khẩu hiện tại không chính xác.']);
    }

    // ───────────────────────── Orders cancel ─────────────────────────

    public function test_cancel_order_redirects_back_with_success_and_updates_status(): void
    {
        $user = User::factory()->create();
        $order = Order::create([
            'order_code' => 'HPY-' . strtoupper(Str::random(8)), 'user_id' => $user->id,
            'customer_name' => $user->name, 'customer_phone' => '0900000000',
            'delivery_address' => 'Test address', 'total_amount' => 50000, 'discount_amount' => 0,
            'final_amount' => 50000, 'payment_status' => 'unpaid', 'payment_method' => 'cod',
            'status' => 'pending', 'delivery_type' => 'delivery',
        ]);

        $response = $this->actingAs($user)->post("/orders/{$order->id}/cancel", [
            'cancel_reason' => 'Đổi ý không muốn mua nữa',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertSame('cancelled', $order->fresh()->status);
    }

    public function test_cancel_order_redirects_back_with_error_when_not_pending(): void
    {
        $user = User::factory()->create();
        $order = Order::create([
            'order_code' => 'HPY-' . strtoupper(Str::random(8)), 'user_id' => $user->id,
            'customer_name' => $user->name, 'customer_phone' => '0900000000',
            'delivery_address' => 'Test address', 'total_amount' => 50000, 'discount_amount' => 0,
            'final_amount' => 50000, 'payment_status' => 'paid', 'payment_method' => 'cod',
            'status' => 'completed', 'delivery_type' => 'delivery',
        ]);

        $response = $this->actingAs($user)->post("/orders/{$order->id}/cancel", [
            'cancel_reason' => 'Đổi ý không muốn mua nữa',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertSame('completed', $order->fresh()->status);
    }

    private function makePaidOnlineOrder(User $user, string $paymentMethod = 'vnpay'): Order
    {
        return Order::create([
            'order_code' => 'HPY-' . strtoupper(Str::random(8)), 'user_id' => $user->id,
            'customer_name' => $user->name, 'customer_phone' => '0900000000',
            'delivery_address' => 'Test address', 'total_amount' => 50000, 'discount_amount' => 0,
            'final_amount' => 50000, 'payment_status' => 'paid', 'payment_method' => $paymentMethod,
            'paid_at' => now(), 'payment_transaction_id' => 'ORIGINAL-TX-' . Str::random(6),
            'status' => 'pending', 'delivery_type' => 'delivery',
        ]);
    }

    // Đơn đã thanh toán online mà shop CHƯA xác nhận: khách VẪN được tự hủy, hệ thống tự động gọi API
    // hoàn tiền của cổng thanh toán rồi mới hủy — không bắt khách phải liên hệ cửa hàng thủ công.
    public function test_customer_can_self_cancel_paid_vnpay_order_and_money_is_refunded_automatically(): void
    {
        config([
            'services.vnpay.sandbox.tmn_code' => 'TESTTMN',
            'services.vnpay.sandbox.hash_secret' => 'TESTSECRET',
        ]);
        \Illuminate\Support\Facades\Http::fake([
            '*/merchant_webapi/api/transaction' => \Illuminate\Support\Facades\Http::response(
                ['vnp_ResponseCode' => '00', 'vnp_TransactionNo' => 'VNP-REFUND-TX-1'], 200
            ),
        ]);

        $user = User::factory()->create();
        $order = $this->makePaidOnlineOrder($user, 'vnpay');

        $response = $this->actingAs($user)->post("/orders/{$order->id}/cancel", [
            'cancel_reason' => 'Đổi ý không muốn mua nữa',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $order = $order->fresh();
        $this->assertSame('cancelled', $order->status);
        $this->assertSame('refunded', $order->payment_status);
        $this->assertSame('VNP-REFUND-TX-1', $order->refund_transaction_id);
        $this->assertNotNull($order->refunded_at);
    }

    // Hoàn tiền thất bại -> KHÔNG được hủy đơn (không bao giờ hủy "chay" đơn đã trừ tiền của khách),
    // đơn giữ nguyên paid/pending và khách nhận thông báo lỗi rõ ràng.
    public function test_customer_self_cancel_leaves_order_untouched_when_refund_fails(): void
    {
        config([
            'services.vnpay.sandbox.tmn_code' => 'TESTTMN',
            'services.vnpay.sandbox.hash_secret' => 'TESTSECRET',
        ]);
        \Illuminate\Support\Facades\Http::fake([
            '*/merchant_webapi/api/transaction' => \Illuminate\Support\Facades\Http::response(
                ['vnp_ResponseCode' => '99', 'vnp_Message' => 'Giao dịch không hợp lệ'], 200
            ),
        ]);

        $user = User::factory()->create();
        $order = $this->makePaidOnlineOrder($user, 'vnpay');

        $response = $this->actingAs($user)->post("/orders/{$order->id}/cancel", [
            'cancel_reason' => 'Đổi ý không muốn mua nữa',
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('error');

        $order = $order->fresh();
        $this->assertSame('pending', $order->status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertNull($order->refund_transaction_id);
    }

    // Khách không được hủy đơn của người khác (kể cả đơn đã thanh toán) — ownership check phải chặn
    // TRƯỚC khi bất kỳ lời gọi hoàn tiền nào xảy ra.
    public function test_customer_cannot_self_cancel_someone_elses_paid_order(): void
    {
        \Illuminate\Support\Facades\Http::fake();

        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $order = $this->makePaidOnlineOrder($owner, 'vnpay');

        $this->actingAs($attacker)->postJson("/orders/{$order->id}/cancel", [
            'cancel_reason' => 'Cố hủy đơn của người khác',
        ])->assertStatus(404);

        \Illuminate\Support\Facades\Http::assertNothingSent();
        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    // Nút hủy vẫn hiện cho đơn "Chờ xác nhận" đã thanh toán (nhãn "Hủy & hoàn tiền"), không còn bắt
    // khách liên hệ cửa hàng. Đơn đã xác nhận trở đi thì không có nút nữa.
    public function test_my_orders_page_shows_refund_and_cancel_button_for_paid_pending_orders(): void
    {
        $user = User::factory()->create();
        $paid = $this->makePaidOnlineOrder($user, 'vnpay');

        $this->actingAs($user)->get('/orders')
            ->assertOk()
            ->assertSee("cancel-btn-{$paid->id}", false)
            ->assertSee('Hủy &amp; hoàn tiền', false)
            ->assertDontSee('liên hệ cửa hàng để hoàn tiền', false);

        $confirmed = $this->makePaidOnlineOrder($user, 'vnpay');
        $confirmed->update(['status' => 'confirmed']);

        $this->actingAs($user)->get('/orders')
            ->assertOk()
            ->assertDontSee("cancel-btn-{$confirmed->id}", false);
    }

    // Phần "Chi tiết" của đơn hàng (mở ra khi bấm nút Chi tiết) phải cho khách thấy rõ hình thức
    // thanh toán và trạng thái thanh toán/hoàn tiền — trước đây hoàn toàn không hiển thị, khách tự
    // hủy+hoàn tiền xong không có cách nào biết đơn đã hoàn tiền hay chưa từ chính trang của mình.
    public function test_order_details_show_payment_method_and_refund_status(): void
    {
        $user = User::factory()->create();

        $refunded = $this->makePaidOnlineOrder($user, 'vnpay');
        app(\App\Services\OrderWorkflowService::class)->refundAndCancel($refunded, 'VNP-REFUND-TX-9', 'Khách hàng tự hủy đơn hàng.');

        $this->actingAs($user)->get('/orders')
            ->assertOk()
            ->assertSee('VNPay', false)
            ->assertSee('Đã hoàn tiền', false)
            ->assertSee('Hoàn tiền lúc', false);

        $cod = Order::create([
            'order_code' => 'HPY-' . strtoupper(Str::random(8)), 'user_id' => $user->id,
            'customer_name' => $user->name, 'customer_phone' => '0900000000',
            'delivery_address' => 'Test address', 'total_amount' => 50000, 'discount_amount' => 0,
            'final_amount' => 50000, 'payment_status' => 'unpaid', 'payment_method' => 'cod',
            'status' => 'pending', 'delivery_type' => 'delivery',
        ]);

        $this->actingAs($user)->get('/orders')
            ->assertOk()
            ->assertSee('COD (khi nhận hàng)', false)
            ->assertSee('Thanh toán khi nhận hàng', false);
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

    public function test_review_store_redirects_to_orders_with_success(): void
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
            ->post("/orders/{$order->id}/products/{$product->id}/review", ['rating' => 5, 'comment' => 'Ngon lắm']);

        $response->assertRedirect(route('orders', ['status' => 'completed']));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('reviews', ['order_id' => $order->id, 'product_id' => $product->id, 'rating' => 5]);
    }

    // Trước đây truy cập lại trang đánh giá sau khi đã đánh giá rồi sẽ bị chặn (redirect kèm lỗi) —
    // không có nơi nào để bấm "Xem đánh giá". Giờ trang phải hiển thị lại ĐÚNG nội dung đã gửi ở chế
    // độ chỉ xem thay vì chặn, để làm đích đến cho nút "Xem đánh giá" ở trang đơn hàng.
    public function test_review_page_shows_readonly_content_when_already_reviewed(): void
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
        \App\Models\Review::create([
            'user_id' => $user->id, 'product_id' => $product->id, 'order_id' => $order->id,
            'rating' => 4, 'comment' => 'Trà sữa ngon, giao nhanh', 'is_visible' => 1,
        ]);

        $response = $this->actingAs($user)->get("/orders/{$order->id}/products/{$product->id}/review");

        $response->assertOk(); // không còn bị redirect chặn nữa
        $response->assertSee('Trà sữa ngon, giao nhanh');
        $response->assertSee('Đánh giá của bạn');
        $response->assertDontSee('Viết đánh giá của bạn');
        $response->assertDontSee('Gửi đánh giá');
    }

    // Chưa đánh giá thì trang vẫn hiển thị form nhập bình thường như cũ (không đổi hành vi).
    public function test_review_page_shows_form_when_not_yet_reviewed(): void
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

        $response = $this->actingAs($user)->get("/orders/{$order->id}/products/{$product->id}/review");

        $response->assertOk();
        $response->assertSee('Viết đánh giá của bạn');
        $response->assertSee('Gửi đánh giá');
    }

    // Trong vòng 7 ngày kể từ lúc đánh giá -> trang chỉ xem có thêm nút "Chỉnh sửa đánh giá".
    public function test_review_page_shows_edit_button_within_edit_window(): void
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
        \App\Models\Review::create([
            'user_id' => $user->id, 'product_id' => $product->id, 'order_id' => $order->id,
            'rating' => 4, 'comment' => 'Ổn', 'is_visible' => 1,
        ]);

        $response = $this->actingAs($user)->get("/orders/{$order->id}/products/{$product->id}/review");

        $response->assertOk();
        $response->assertSee('Chỉnh sửa đánh giá');
        $response->assertDontSee('không thể chỉnh sửa nữa');
    }

    // Sau 7 ngày -> KHÔNG còn nút "Chỉnh sửa đánh giá" nữa, thay bằng ghi chú đã hết hạn.
    public function test_review_page_hides_edit_button_after_edit_window_expires(): void
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
        $review = \App\Models\Review::create([
            'user_id' => $user->id, 'product_id' => $product->id, 'order_id' => $order->id,
            'rating' => 4, 'comment' => 'Ổn', 'is_visible' => 1,
        ]);
        DB::table('reviews')->where('id', $review->id)->update(['created_at' => now()->subDays(8)]);

        $response = $this->actingAs($user)->get("/orders/{$order->id}/products/{$product->id}/review");

        $response->assertOk();
        $response->assertDontSee('Chỉnh sửa đánh giá');
        $response->assertSee('không thể chỉnh sửa nữa');
    }

    public function test_review_update_redirects_with_success_within_edit_window(): void
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
        $review = \App\Models\Review::create([
            'user_id' => $user->id, 'product_id' => $product->id, 'order_id' => $order->id,
            'rating' => 3, 'comment' => 'Bình thường', 'is_visible' => 1,
        ]);

        $response = $this->actingAs($user)
            ->put("/orders/{$order->id}/products/{$product->id}/review", ['rating' => 5, 'comment' => 'Giờ thấy ngon hơn']);

        $response->assertRedirect(route('orders', ['status' => 'completed']));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'rating' => 5, 'comment' => 'Giờ thấy ngon hơn']);
        $this->assertNotNull($review->fresh()->edited_at);
    }

    // Chỉ được sửa ĐÚNG 1 LẦN cho mỗi đánh giá — sửa lần 2 (dù vẫn còn trong hạn 7 ngày) phải bị chặn.
    public function test_review_update_redirects_with_error_on_second_edit_attempt(): void
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
        $review = \App\Models\Review::create([
            'user_id' => $user->id, 'product_id' => $product->id, 'order_id' => $order->id,
            'rating' => 3, 'comment' => 'Bình thường', 'is_visible' => 1,
        ]);

        // Lần sửa thứ nhất -> thành công.
        $this->actingAs($user)
            ->put("/orders/{$order->id}/products/{$product->id}/review", ['rating' => 5, 'comment' => 'Ngon hơn tôi nghĩ'])
            ->assertRedirect(route('orders', ['status' => 'completed']));

        // Lần sửa thứ hai -> bị chặn, nội dung KHÔNG đổi thêm nữa.
        $response = $this->actingAs($user)
            ->put("/orders/{$order->id}/products/{$product->id}/review", ['rating' => 1, 'comment' => 'Đổi ý lần 2']);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'rating' => 5, 'comment' => 'Ngon hơn tôi nghĩ']);
    }

    // Đã sửa 1 lần rồi thì trang chỉ xem KHÔNG còn nút "Chỉnh sửa đánh giá" nữa, dù vẫn còn trong hạn
    // 7 ngày — kèm ghi chú lý do đúng ("đã dùng lượt sửa"), khác thông báo hết hạn 7 ngày.
    public function test_review_page_hides_edit_button_after_already_edited_once(): void
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
        \App\Models\Review::create([
            'user_id' => $user->id, 'product_id' => $product->id, 'order_id' => $order->id,
            'rating' => 4, 'comment' => 'Ổn', 'is_visible' => 1, 'edited_at' => now(),
        ]);

        $response = $this->actingAs($user)->get("/orders/{$order->id}/products/{$product->id}/review");

        $response->assertOk();
        $response->assertDontSee('Chỉnh sửa đánh giá');
        $response->assertSee('đã sử dụng lượt sửa đó rồi');
        $response->assertDontSee('không thể chỉnh sửa nữa'); // không nhầm sang lý do "hết hạn 7 ngày"
    }

    public function test_review_update_redirects_with_error_after_edit_window_expires(): void
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
        $review = \App\Models\Review::create([
            'user_id' => $user->id, 'product_id' => $product->id, 'order_id' => $order->id,
            'rating' => 3, 'comment' => 'Bình thường', 'is_visible' => 1,
        ]);
        DB::table('reviews')->where('id', $review->id)->update(['created_at' => now()->subDays(8)]);

        $response = $this->actingAs($user)
            ->put("/orders/{$order->id}/products/{$product->id}/review", ['rating' => 5, 'comment' => 'Sửa trễ']);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'rating' => 3, 'comment' => 'Bình thường']);
    }

    public function test_review_update_ajax_returns_422_when_rating_missing(): void
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
        \App\Models\Review::create([
            'user_id' => $user->id, 'product_id' => $product->id, 'order_id' => $order->id,
            'rating' => 3, 'comment' => 'Bình thường', 'is_visible' => 1,
        ]);

        $response = $this->actingAs($user)
            ->putJson("/orders/{$order->id}/products/{$product->id}/review", ['comment' => 'Sửa nhưng quên chọn sao']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('rating');
    }

    // Trang danh sách đơn hàng: sản phẩm CHƯA đánh giá hiện nút "Đánh giá" trỏ tới review.create; sản
    // phẩm ĐÃ đánh giá hiện link "Xem đánh giá" trỏ tới ĐÚNG cùng route đó (không còn là <span> tĩnh
    // không bấm được như trước).
    public function test_orders_page_shows_review_and_view_review_links_correctly(): void
    {
        $user = User::factory()->create();
        $reviewedProduct = $this->makeProduct(['name' => 'Trà sữa đã đánh giá']);
        $unreviewedProduct = $this->makeProduct(['name' => 'Trà sữa chưa đánh giá']);
        $order = Order::create([
            'order_code' => 'HPY-' . strtoupper(Str::random(8)), 'user_id' => $user->id,
            'customer_name' => $user->name, 'customer_phone' => '0900000000',
            'delivery_address' => 'Test address', 'total_amount' => 60000, 'discount_amount' => 0,
            'final_amount' => 60000, 'payment_status' => 'paid', 'payment_method' => 'cod',
            'status' => 'completed', 'delivery_type' => 'delivery',
        ]);
        OrderItem::create(['order_id' => $order->id, 'product_id' => $reviewedProduct->id, 'quantity' => 1, 'unit_price' => 30000]);
        OrderItem::create(['order_id' => $order->id, 'product_id' => $unreviewedProduct->id, 'quantity' => 1, 'unit_price' => 30000]);
        \App\Models\Review::create([
            'user_id' => $user->id, 'product_id' => $reviewedProduct->id, 'order_id' => $order->id,
            'rating' => 5, 'comment' => 'Rất ngon', 'is_visible' => 1,
        ]);

        $response = $this->actingAs($user)->get('/orders?status=completed');

        $response->assertOk();
        $response->assertSee('Xem đánh giá');
        $response->assertSee(route('review.create', ['orderId' => $order->id, 'productId' => $reviewedProduct->id]), false);
        $response->assertSee('>Đánh giá<', false);
        $response->assertDontSee('>Đã đánh giá<', false);
    }

    // ───────────────────────── Auth: login/register/forgot-password/otp ─────────────────────────

    public function test_login_returns_error_on_wrong_credentials(): void
    {
        $response = $this->post('/login', ['email' => 'nobody@test.com', 'password' => 'wrong']);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['login_error' => 'Thông tin đăng nhập không chính xác.']);
    }

    public function test_login_success_redirects_home(): void
    {
        $user = User::create([
            'name' => 'Test', 'email' => 'ajax-login@test.com', 'password' => bcrypt('password123'),
            'role' => 'customer', 'is_active' => 1,
        ]);

        $response = $this->post('/login', ['email' => 'ajax-login@test.com', 'password' => 'password123']);

        $response->assertRedirect(url('/'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_rejects_locked_account(): void
    {
        User::create([
            'name' => 'Locked', 'email' => 'locked@test.com', 'password' => bcrypt('password123'),
            'role' => 'customer', 'is_active' => 0, 'lock_reason' => 'Vi phạm điều khoản',
        ]);

        $response = $this->post('/login', ['email' => 'locked@test.com', 'password' => 'password123']);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['login_error' => 'Tài khoản của bạn đã bị khóa: Vi phạm điều khoản']);
        $this->assertGuest();
    }

    public function test_register_returns_error_when_email_exists(): void
    {
        User::create([
            'name' => 'Existing', 'email' => 'exists@test.com', 'password' => bcrypt('password'),
            'role' => 'customer', 'is_active' => 1,
        ]);

        $response = $this->post('/register', [
            'full_name' => 'New User', 'email' => 'exists@test.com',
            'password' => 'Passw0rd!', 'password_confirmation' => 'Passw0rd!',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['register_error' => 'Email đã được sử dụng.']);
    }

    public function test_register_success_shows_otp_modal(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $response = $this->post('/register', [
            'full_name' => 'New User', 'email' => 'newuser@test.com',
            'password' => 'Passw0rd!', 'password_confirmation' => 'Passw0rd!',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('show_otp', true);
        $this->assertSame('newuser@test.com', session('verify_email'));
    }

    public function test_verify_otp_returns_error_on_wrong_code(): void
    {
        $response = $this->withSession([
            'verify_email' => 'newuser@test.com',
            'verify_otp' => '1234',
            'verify_otp_time' => now(),
        ])->post('/verify-otp', ['otp' => ['9', '9', '9', '9']]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['otp_error' => 'Mã OTP không chính xác. Vui lòng thử lại.']);
    }

    public function test_verify_otp_success_creates_user_and_logs_in(): void
    {
        $response = $this->withSession([
            'verify_email' => 'newuser2@test.com',
            'verify_otp' => '1234',
            'verify_otp_time' => now(),
            'register_data' => [
                'name' => 'New User 2', 'email' => 'newuser2@test.com', 'phone' => null,
                'password' => bcrypt('Passw0rd!'), 'role' => 'customer', 'is_active' => 1,
            ],
        ])->post('/verify-otp', ['otp' => ['1', '2', '3', '4']]);

        $response->assertRedirect(url('/'));
        $this->assertDatabaseHas('users', ['email' => 'newuser2@test.com']);
        $this->assertAuthenticated();
    }

    public function test_forgot_password_returns_error_when_email_not_found(): void
    {
        $response = $this->post('/forgot-password', ['recovery_contact' => 'nobody@test.com']);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors(['forgot_error' => 'Email không tồn tại trong hệ thống.']);
    }

    public function test_forgot_password_success_shows_otp_modal(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        User::create([
            'name' => 'Test', 'email' => 'forgot@test.com', 'password' => bcrypt('password'),
            'role' => 'customer', 'is_active' => 1,
        ]);

        $response = $this->post('/forgot-password', ['recovery_contact' => 'forgot@test.com']);

        $response->assertRedirect();
        $response->assertSessionHas('show_otp', true);
        $this->assertSame('forgot@test.com', session('verify_email'));
    }

    // Reset-password used to be its own full page (GET /reset-password). It's now a modal included on
    // every page, opened via the show_reset_password flash flag - reset-password.blade.php reads that
    // flag itself on page load and pops the modal open, no JS-driven navigation involved.
    public function test_forgot_password_full_flow_signals_reset_modal_instead_of_redirect(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        User::create([
            'name' => 'Test', 'email' => 'fullflow@test.com', 'password' => bcrypt('OldPassword1@'),
            'role' => 'customer', 'is_active' => 1,
        ]);

        $this->post('/forgot-password', ['recovery_contact' => 'fullflow@test.com'])->assertRedirect();

        $otp = session('verify_otp');
        $digits = str_split((string) $otp);

        $verify = $this->post('/verify-otp', ['otp' => $digits]);
        $verify->assertRedirect()->assertSessionHas('show_reset_password', true);
        $this->assertGuest();

        $reset = $this->post('/reset-password', [
            'password' => 'NewPassword1@',
            'password_confirmation' => 'NewPassword1@',
        ]);

        $reset->assertRedirect(url('/'));
        $this->assertAuthenticated();
        $this->assertSame('fullflow@test.com', auth()->user()->email);
        $this->assertNotEmpty(session('success'));
    }

    // SECURITY REGRESSION: can_reset_password is a long-lived permission flag, not a "show the modal
    // now" signal. Driving the modal off it meant any subsequent page load - including clicking
    // "Gửi lại" on an expired OTP, which does a full redirect - popped the reset-password modal open,
    // letting anyone set a new password without ever entering a correct OTP.
    public function test_reset_modal_does_not_auto_open_from_lingering_permission_flag(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        User::create([
            'name' => 'Test', 'email' => 'lingering@test.com', 'password' => bcrypt('OldPassword1@'),
            'role' => 'customer', 'is_active' => 1,
        ]);

        $this->post('/forgot-password', ['recovery_contact' => 'lingering@test.com'])->assertRedirect();
        $digits = str_split((string) session('verify_otp'));
        $this->post('/verify-otp', ['otp' => $digits])->assertRedirect();

        // The verify-otp response flashes show_reset_password for the very next request only (the
        // page the browser lands on right after submitting the OTP form) - burn through that one-shot
        // request here so the assertion below reflects a LATER page load, once the flash is gone but
        // the long-lived can_reset_password permission flag is still sitting in the session.
        $this->get('/');
        $this->assertTrue(session()->has('can_reset_password'));

        $this->get('/')->assertOk()->assertDontSee('data-show-reset-password="true"', false);
    }

    public function test_resending_an_otp_does_not_open_the_reset_password_modal(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        User::create([
            'name' => 'Test', 'email' => 'resendflow@test.com', 'password' => bcrypt('OldPassword1@'),
            'role' => 'customer', 'is_active' => 1,
        ]);

        $this->post('/forgot-password', ['recovery_contact' => 'resendflow@test.com'])->assertRedirect();

        $this->followingRedirects()->get('/resend-otp')
            ->assertOk()
            ->assertDontSee('data-show-reset-password="true"', false);
    }

    public function test_reset_password_permission_expires_after_its_window(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        User::create([
            'name' => 'Test', 'email' => 'staleperm@test.com', 'password' => bcrypt('OldPassword1@'),
            'role' => 'customer', 'is_active' => 1,
        ]);

        $this->post('/forgot-password', ['recovery_contact' => 'staleperm@test.com'])->assertRedirect();
        $digits = str_split((string) session('verify_otp'));
        $this->post('/verify-otp', ['otp' => $digits])->assertRedirect();

        // Rewind the grant well past the allowed window.
        session(['can_reset_password_at' => now()->subHour()->toDateTimeString()]);

        $this->post('/reset-password', [
            'password' => 'NewPassword1@',
            'password_confirmation' => 'NewPassword1@',
        ])->assertRedirect();

        $this->assertGuest();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('OldPassword1@', User::where('email', 'staleperm@test.com')->first()->password));
    }

    public function test_reset_password_rejects_direct_access_without_otp_verification(): void
    {
        $response = $this->post('/reset-password', [
            'password' => 'NewPassword1@',
            'password_confirmation' => 'NewPassword1@',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['reset_error' => 'Phiên xác thực đã hết hạn, vui lòng thực hiện lại thao tác quên mật khẩu.']);
        $this->assertGuest();
    }
}
