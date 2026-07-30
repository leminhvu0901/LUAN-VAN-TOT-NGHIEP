<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Luồng thanh toán VNPay: build URL đã ký (không gọi HTTP như MoMo), handleReturn (redirect trình
 * duyệt) và handleIpn (server-to-server, PHẢI luôn trả JSON {"RspCode":...} — rủi ro cao nhất nếu sai
 * định dạng VNPay sẽ retry vô hạn, xem VnpayController::handleIpn()).
 */
class VnpayCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function ensureVnpayConfigured(): void
    {
        config([
            'services.vnpay.sandbox.tmn_code' => 'TESTTMN',
            'services.vnpay.sandbox.hash_secret' => 'TESTSECRET',
            'services.vnpay.sandbox.url' => 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html',
        ]);
    }

    /**
     * Mirror thuật toán ký ở VnpayController::buildPaymentUrl()/verifySignature(): ksort() rồi
     * urlencode(key)=urlencode(value) nối bằng "&", HMAC-SHA512.
     */
    private function signVnpayParams(array $params, string $secret): array
    {
        ksort($params);
        $hashData = '';
        $first = true;
        foreach ($params as $key => $value) {
            if (!$first) {
                $hashData .= '&';
            }
            $hashData .= urlencode((string) $key) . '=' . urlencode((string) $value);
            $first = false;
        }
        $params['vnp_SecureHash'] = hash_hmac('sha512', $hashData, $secret);

        return $params;
    }

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
            'payment_method' => 'vnpay',
            'status' => 'pending',
            'delivery_type' => 'delivery',
            'shipping_fee' => 0,
            'weather_fee' => 0,
            'peak_hour_fee' => 0,
        ], $overrides);
    }

    // ───────────────────────── createPayment / buildPaymentUrl ─────────────────────────

    public function test_vnpay_checkout_creates_order_and_redirects_to_correctly_signed_payment_url(): void
    {
        $this->ensureVnpayConfigured();
        $this->travelTo(Carbon::parse('14:00:00'));

        $user = User::factory()->create();
        $product = $this->makeProduct();
        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1, 'unit_price' => 30000]);
        $address = $this->makeAddress($user);

        $response = $this->actingAs($user)->postJson('/checkout/vnpay', [
            'address_id' => $address->id, 'payment_method' => 'vnpay', 'distance_km' => 2.5,
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        $redirectUrl = $response->json('redirect_url');
        $this->assertNotNull($redirectUrl);
        $this->assertStringStartsWith('https://sandbox.vnpayment.vn/paymentv2/vpcpay.html?', $redirectUrl);
        $this->assertStringContainsString('vnp_SecureHash=', $redirectUrl);

        $order = Order::where('user_id', $user->id)->first();
        $this->assertNotNull($order);
        $this->assertSame('vnpay', $order->payment_method);
        $this->assertSame('unpaid', $order->payment_status);
        $this->assertStringContainsString('vnp_TxnRef=' . $order->order_code, $redirectUrl);

        // Chữ ký phải đúng thuật toán VNPay (ksort + query string urlencode, HMAC-SHA512) — tự tính
        // lại chữ ký kỳ vọng từ chính các tham số trong URL rồi so khớp, thay vì chỉ kiểm tra "có tồn tại".
        parse_str(parse_url($redirectUrl, PHP_URL_QUERY), $params);
        $receivedHash = $params['vnp_SecureHash'];
        unset($params['vnp_SecureHash']);
        $expected = $this->signVnpayParams($params, 'TESTSECRET')['vnp_SecureHash'];
        $this->assertSame($expected, $receivedHash);
    }

    public function test_vnpay_checkout_blocked_when_not_configured(): void
    {
        config([
            'services.vnpay.sandbox.tmn_code' => '',
            'services.vnpay.sandbox.hash_secret' => '',
        ]);
        $this->travelTo(Carbon::parse('14:00:00'));

        $user = User::factory()->create();
        $address = $this->makeAddress($user);

        $response = $this->actingAs($user)->postJson('/checkout/vnpay', [
            'address_id' => $address->id, 'payment_method' => 'vnpay', 'distance_km' => 2.5,
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('orders', 0);
    }

    // ───────────────────────── handleReturn ─────────────────────────

    public function test_vnpay_return_with_valid_signature_marks_order_paid(): void
    {
        $this->ensureVnpayConfigured();

        $user = User::factory()->create();
        $order = Order::create($this->orderData($user, ['final_amount' => 100000]));

        $signed = $this->signVnpayParams([
            'vnp_Amount' => '10000000',
            'vnp_ResponseCode' => '00',
            'vnp_TxnRef' => $order->order_code,
            'vnp_TransactionNo' => 'VNP-TX-1',
            'vnp_PayDate' => now()->format('YmdHis'),
        ], 'TESTSECRET');

        $response = $this->actingAs($user)->get('/checkout/vnpay/return?' . http_build_query($signed));
        $response->assertRedirect(route('orders'));

        $order = $order->fresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('VNP-TX-1', $order->payment_transaction_id);
    }

    public function test_vnpay_return_with_failed_response_code_deletes_unpaid_delivery_order(): void
    {
        $this->ensureVnpayConfigured();

        $user = User::factory()->create();
        $order = Order::create($this->orderData($user));

        $signed = $this->signVnpayParams([
            'vnp_Amount' => '10000000',
            'vnp_ResponseCode' => '24', // Khách hủy giao dịch
            'vnp_TxnRef' => $order->order_code,
        ], 'TESTSECRET');

        $response = $this->actingAs($user)->get('/checkout/vnpay/return?' . http_build_query($signed));
        $response->assertRedirect(route('checkout'));

        // Order model dùng SoftDeletes -> delete() chỉ set deleted_at, không xóa hẳn khỏi bảng.
        $this->assertSoftDeleted('orders', ['id' => $order->id]);
    }

    // ───────────────────────── handleIpn ─────────────────────────

    private function ipnParams(Order $order, array $overrides = []): array
    {
        return array_merge([
            'vnp_Amount' => (string) ((int) round((float) $order->final_amount) * 100),
            'vnp_ResponseCode' => '00',
            'vnp_TransactionStatus' => '00',
            'vnp_TxnRef' => $order->order_code,
            'vnp_TransactionNo' => 'VNP-IPN-TX-1',
            'vnp_PayDate' => now()->format('YmdHis'),
        ], $overrides);
    }

    public function test_vnpay_ipn_with_valid_signature_marks_order_paid_and_returns_success_rsp_code(): void
    {
        $this->ensureVnpayConfigured();

        $user = User::factory()->create();
        $order = Order::create($this->orderData($user, ['final_amount' => 100000]));

        $signed = $this->signVnpayParams($this->ipnParams($order), 'TESTSECRET');

        $response = $this->get('/checkout/vnpay/ipn?' . http_build_query($signed));

        $response->assertOk()->assertJson(['RspCode' => '00', 'Message' => 'Confirm Success']);
        $order = $order->fresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('VNP-IPN-TX-1', $order->payment_transaction_id);
    }

    public function test_vnpay_ipn_replay_on_already_paid_order_returns_rsp_code_02_without_reprocessing(): void
    {
        $this->ensureVnpayConfigured();

        $user = User::factory()->create();
        $order = Order::create($this->orderData($user, [
            'final_amount' => 100000,
            'payment_status' => 'paid',
            'payment_transaction_id' => 'VNP-IPN-TX-1',
            'paid_at' => now(),
        ]));

        $signed = $this->signVnpayParams($this->ipnParams($order), 'TESTSECRET');

        $response = $this->get('/checkout/vnpay/ipn?' . http_build_query($signed));

        $response->assertOk()->assertJson(['RspCode' => '02']);
    }

    public function test_vnpay_ipn_for_unknown_order_returns_rsp_code_01(): void
    {
        $this->ensureVnpayConfigured();

        $signed = $this->signVnpayParams([
            'vnp_Amount' => '10000000',
            'vnp_ResponseCode' => '00',
            'vnp_TransactionStatus' => '00',
            'vnp_TxnRef' => 'HPY-DOESNOTEXIST',
            'vnp_TransactionNo' => 'VNP-IPN-TX-X',
        ], 'TESTSECRET');

        $response = $this->get('/checkout/vnpay/ipn?' . http_build_query($signed));

        $response->assertOk()->assertJson(['RspCode' => '01']);
    }

    public function test_vnpay_ipn_with_amount_mismatch_returns_rsp_code_04(): void
    {
        $this->ensureVnpayConfigured();

        $user = User::factory()->create();
        $order = Order::create($this->orderData($user, ['final_amount' => 100000]));

        $signed = $this->signVnpayParams($this->ipnParams($order, ['vnp_Amount' => '999900']), 'TESTSECRET');

        $response = $this->get('/checkout/vnpay/ipn?' . http_build_query($signed));

        $response->assertOk()->assertJson(['RspCode' => '04']);
        $this->assertSame('unpaid', $order->fresh()->payment_status);
    }

    public function test_vnpay_ipn_with_invalid_signature_returns_rsp_code_97(): void
    {
        $this->ensureVnpayConfigured();

        $user = User::factory()->create();
        $order = Order::create($this->orderData($user, ['final_amount' => 100000]));

        $params = $this->ipnParams($order);
        $params['vnp_SecureHash'] = 'tampered-hash-value';

        $response = $this->get('/checkout/vnpay/ipn?' . http_build_query($params));

        $response->assertOk()->assertJson(['RspCode' => '97']);
        $this->assertSame('unpaid', $order->fresh()->payment_status);
    }
}
