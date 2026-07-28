<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MomoRefundTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(array $overrides = []): Order
    {
        return Order::create(array_merge([
            'order_code' => 'HPY-' . strtoupper(bin2hex(random_bytes(4))),
            'customer_name' => 'Khách hàng Test',
            'customer_phone' => '0901234567',
            'delivery_address' => '123 Đường Test',
            'total_amount' => 50000,
            'final_amount' => 50000,
            'payment_status' => 'unpaid',
            'payment_method' => 'cod',
            'status' => 'pending',
        ], $overrides));
    }

    private function fakeRefundResponse(int $resultCode = 0, string $transId = 'REFUND-TX-1', ?string $message = null): void
    {
        Http::fake([
            '*/v2/gateway/api/refund' => Http::response(array_filter([
                'resultCode' => $resultCode,
                'transId' => $resultCode === 0 ? $transId : null,
                'message' => $message,
            ], fn ($v) => $v !== null), 200),
        ]);
    }

    public function test_receptionist_refunds_paid_momo_order_and_cancels_it(): void
    {
        $this->fakeRefundResponse(0, 'REFUND-TX-1');

        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $order = $this->makeOrder([
            'status' => 'confirmed',
            'payment_method' => 'momo',
            'payment_status' => 'paid',
            'paid_at' => now(),
            'payment_transaction_id' => 'ORIGINAL-TX-1',
            'points_redeemed' => 0,
        ]);

        $this->actingAs($receptionist);
        $response = $this->postJson("/staff/reception/orders/{$order->id}/refund", [
            'cancel_reason' => 'Khách đổi ý muốn hủy đơn',
        ]);
        $response->assertOk()->assertJson(['success' => true]);

        $order = $order->fresh();
        $this->assertEquals('refunded', $order->payment_status);
        $this->assertEquals('cancelled', $order->status);
        $this->assertEquals('REFUND-TX-1', $order->refund_transaction_id);
        $this->assertNotNull($order->refunded_at);
        $this->assertEquals('Khách đổi ý muốn hủy đơn', $order->cancel_reason);
    }

    public function test_refund_fails_when_momo_returns_error_and_order_stays_untouched(): void
    {
        $this->fakeRefundResponse(99, message: 'Giao dịch không hợp lệ');

        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $order = $this->makeOrder([
            'status' => 'confirmed',
            'payment_method' => 'momo',
            'payment_status' => 'paid',
            'paid_at' => now(),
            'payment_transaction_id' => 'ORIGINAL-TX-2',
        ]);

        $this->actingAs($receptionist);
        $response = $this->postJson("/staff/reception/orders/{$order->id}/refund", [
            'cancel_reason' => 'Khách đổi ý muốn hủy đơn',
        ]);
        $response->assertStatus(422);

        $order = $order->fresh();
        $this->assertEquals('paid', $order->payment_status);
        $this->assertEquals('confirmed', $order->status);
        $this->assertNull($order->refund_transaction_id);
    }

    public function test_refund_is_idempotent_against_double_submit(): void
    {
        $this->fakeRefundResponse(0, 'REFUND-TX-3');

        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $customer = User::factory()->create(['role' => 'customer', 'points' => 0]);
        $order = $this->makeOrder([
            'status' => 'confirmed',
            'payment_method' => 'momo',
            'payment_status' => 'paid',
            'paid_at' => now(),
            'payment_transaction_id' => 'ORIGINAL-TX-3',
            'user_id' => $customer->id,
            'points_redeemed' => 10,
        ]);

        $this->actingAs($receptionist);
        $this->postJson("/staff/reception/orders/{$order->id}/refund", ['cancel_reason' => 'Khách hủy đơn'])
            ->assertOk();
        Http::assertSentCount(1);
        $this->assertEquals(10, $customer->fresh()->points);

        // Gọi lại lần 2 (double-submit) trên đơn đã refunded -> route guard chặn ngay (payment_status
        // không còn 'paid'), không gọi lại MoMo, không hoàn điểm 2 lần.
        $response = $this->postJson("/staff/reception/orders/{$order->id}/refund", ['cancel_reason' => 'Khách hủy đơn']);
        $response->assertStatus(422);
        Http::assertSentCount(1);
        $this->assertEquals(10, $customer->fresh()->points);
    }

    public function test_admin_can_also_refund_and_cancel_paid_momo_order(): void
    {
        $this->fakeRefundResponse(0, 'REFUND-TX-4');

        $admin = User::factory()->create(['role' => 'admin']);
        $order = $this->makeOrder([
            'status' => 'pending',
            'payment_method' => 'momo',
            'payment_status' => 'paid',
            'paid_at' => now(),
            'payment_transaction_id' => 'ORIGINAL-TX-4',
        ]);

        $this->actingAs($admin);
        $response = $this->postJson("/admin/orders/{$order->id}/refund", [
            'cancel_reason' => 'Admin hủy đơn theo yêu cầu khách',
        ]);
        $response->assertOk()->assertJson(['success' => true]);

        $order = $order->fresh();
        $this->assertEquals('refunded', $order->payment_status);
        $this->assertEquals('cancelled', $order->status);
    }

    public function test_cash_paid_order_cannot_be_refunded_via_momo_route_and_cancel_still_blocked(): void
    {
        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $order = $this->makeOrder([
            'status' => 'confirmed',
            'payment_method' => 'cash',
            'payment_status' => 'paid',
        ]);

        $this->actingAs($receptionist);

        $refundResponse = $this->postJson("/staff/reception/orders/{$order->id}/refund", [
            'cancel_reason' => 'Khách hủy đơn',
        ]);
        $refundResponse->assertStatus(422);

        // Hủy qua route transition() thường vẫn bị chặn như cũ (không đổi hành vi đơn tiền mặt/COD).
        $cancelResponse = $this->patch("/staff/reception/orders/{$order->id}/status", [
            'status' => 'cancelled',
            'cancel_reason' => 'Khách hủy đơn',
        ]);
        $cancelResponse->assertSessionHasErrors('status');

        $order = $order->fresh();
        $this->assertEquals('paid', $order->payment_status);
        $this->assertEquals('confirmed', $order->status);
    }

    private function assertRefundBlockedForStatus(string $status): void
    {
        $this->fakeRefundResponse(0);

        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $order = $this->makeOrder([
            'status' => $status,
            'payment_method' => 'momo',
            'payment_status' => 'paid',
            'paid_at' => now(),
            'payment_transaction_id' => 'ORIGINAL-TX-STATUS-' . $status,
        ]);

        $this->actingAs($receptionist);
        $response = $this->postJson("/staff/reception/orders/{$order->id}/refund", [
            'cancel_reason' => 'Khách hủy đơn',
        ]);
        $response->assertStatus(422);
        Http::assertNothingSent();

        $order = $order->fresh();
        $this->assertEquals('paid', $order->payment_status);
        $this->assertEquals($status, $order->status);
    }

    public function test_refund_blocked_for_shipping_order(): void
    {
        $this->assertRefundBlockedForStatus('shipping');
    }

    public function test_refund_blocked_for_completed_order(): void
    {
        $this->assertRefundBlockedForStatus('completed');
    }

    public function test_refund_blocked_for_already_cancelled_order(): void
    {
        $this->assertRefundBlockedForStatus('cancelled');
    }

    public function test_refund_blocked_when_missing_original_payment_transaction_id(): void
    {
        $this->fakeRefundResponse(0);

        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $order = $this->makeOrder([
            'status' => 'confirmed',
            'payment_method' => 'momo',
            'payment_status' => 'paid',
            'paid_at' => now(),
            'payment_transaction_id' => null,
        ]);

        $this->actingAs($receptionist);
        $response = $this->postJson("/staff/reception/orders/{$order->id}/refund", [
            'cancel_reason' => 'Khách hủy đơn',
        ]);
        $response->assertStatus(422);
        Http::assertNothingSent();

        $this->assertEquals('paid', $order->fresh()->payment_status);
    }

    public function test_refund_requires_cancel_reason_at_least_5_characters(): void
    {
        $this->fakeRefundResponse(0);

        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $order = $this->makeOrder([
            'status' => 'confirmed',
            'payment_method' => 'momo',
            'payment_status' => 'paid',
            'paid_at' => now(),
            'payment_transaction_id' => 'ORIGINAL-TX-REASON',
        ]);

        $this->actingAs($receptionist);

        $this->postJson("/staff/reception/orders/{$order->id}/refund", [])
            ->assertStatus(422)->assertJsonValidationErrors('cancel_reason');

        $this->postJson("/staff/reception/orders/{$order->id}/refund", ['cancel_reason' => 'Hủy'])
            ->assertStatus(422)->assertJsonValidationErrors('cancel_reason');

        Http::assertNothingSent();
        $this->assertEquals('paid', $order->fresh()->payment_status);
    }

    public function test_refund_blocked_when_momo_not_configured(): void
    {
        config([
            'services.momo.sandbox.partner_code' => '',
            'services.momo.sandbox.access_key' => '',
            'services.momo.sandbox.secret_key' => '',
        ]);

        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $order = $this->makeOrder([
            'status' => 'confirmed',
            'payment_method' => 'momo',
            'payment_status' => 'paid',
            'paid_at' => now(),
            'payment_transaction_id' => 'ORIGINAL-TX-CFG',
        ]);

        $this->actingAs($receptionist);
        $response = $this->postJson("/staff/reception/orders/{$order->id}/refund", [
            'cancel_reason' => 'Khách hủy đơn',
        ]);
        $response->assertStatus(422);

        $this->assertEquals('paid', $order->fresh()->payment_status);
    }

    public function test_refund_handles_momo_network_exception_gracefully(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection timed out');
        });

        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $order = $this->makeOrder([
            'status' => 'confirmed',
            'payment_method' => 'momo',
            'payment_status' => 'paid',
            'paid_at' => now(),
            'payment_transaction_id' => 'ORIGINAL-TX-NET',
        ]);

        $this->actingAs($receptionist);
        $response = $this->postJson("/staff/reception/orders/{$order->id}/refund", [
            'cancel_reason' => 'Khách hủy đơn',
        ]);
        $response->assertStatus(422);

        $order = $order->fresh();
        $this->assertEquals('paid', $order->payment_status);
        $this->assertEquals('confirmed', $order->status);
    }

    public function test_guest_and_customer_cannot_access_refund_routes(): void
    {
        $order = $this->makeOrder([
            'status' => 'confirmed',
            'payment_method' => 'momo',
            'payment_status' => 'paid',
            'paid_at' => now(),
            'payment_transaction_id' => 'ORIGINAL-TX-AUTH',
        ]);

        $this->postJson("/staff/reception/orders/{$order->id}/refund", ['cancel_reason' => 'x'])->assertStatus(401);
        $this->postJson("/admin/orders/{$order->id}/refund", ['cancel_reason' => 'x'])->assertStatus(401);

        $customer = User::factory()->create(['role' => 'customer']);
        $this->actingAs($customer);
        $this->postJson("/staff/reception/orders/{$order->id}/refund", ['cancel_reason' => 'x'])->assertStatus(403);
        $this->postJson("/admin/orders/{$order->id}/refund", ['cancel_reason' => 'x'])->assertStatus(403);

        $this->assertEquals('paid', $order->fresh()->payment_status);
    }

    public function test_delivery_staff_redirected_away_from_reception_refund_route(): void
    {
        $delivery = User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery']);
        $order = $this->makeOrder([
            'status' => 'confirmed',
            'payment_method' => 'momo',
            'payment_status' => 'paid',
            'paid_at' => now(),
            'payment_transaction_id' => 'ORIGINAL-TX-CROSSROLE',
        ]);

        $this->actingAs($delivery);
        $this->post("/staff/reception/orders/{$order->id}/refund", ['cancel_reason' => 'x'])
            ->assertRedirect(route('staff.delivery.dashboard'));

        $this->assertEquals('paid', $order->fresh()->payment_status);
    }

    public function test_reception_order_show_page_displays_refund_button_only_when_eligible(): void
    {
        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $this->actingAs($receptionist);

        $refundable = $this->makeOrder([
            'status' => 'confirmed', 'payment_method' => 'momo', 'payment_status' => 'paid',
            'paid_at' => now(), 'payment_transaction_id' => 'TX-VIEW-1',
        ]);
        $this->get("/staff/reception/orders/{$refundable->id}")
            ->assertOk()
            ->assertSee('currency_exchange')
            ->assertDontSee('cần hoàn tiền trước khi hủy');

        $unpaidMomo = $this->makeOrder(['status' => 'pending', 'payment_method' => 'momo', 'payment_status' => 'unpaid']);
        $this->get("/staff/reception/orders/{$unpaidMomo->id}")
            ->assertOk()
            ->assertSee('>Hủy đơn<', false)
            ->assertDontSee('currency_exchange');

        $paidCash = $this->makeOrder(['status' => 'confirmed', 'payment_method' => 'cash', 'payment_status' => 'paid']);
        $this->get("/staff/reception/orders/{$paidCash->id}")
            ->assertOk()
            ->assertSee('cần hoàn tiền trước khi hủy')
            ->assertDontSee('currency_exchange');

        $refunded = $this->makeOrder([
            'status' => 'cancelled', 'payment_method' => 'momo', 'payment_status' => 'refunded',
            'refunded_at' => now(), 'cancel_reason' => 'Đã hoàn tiền test',
        ]);
        $this->get("/staff/reception/orders/{$refunded->id}")
            ->assertOk()
            ->assertSee('Đã hoàn tiền');
    }

    public function test_admin_order_show_page_displays_refund_button_only_when_eligible(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $refundable = $this->makeOrder([
            'status' => 'pending', 'payment_method' => 'momo', 'payment_status' => 'paid',
            'paid_at' => now(), 'payment_transaction_id' => 'TX-VIEW-ADMIN-1',
        ]);
        $this->get("/admin/orders/{$refundable->id}")
            ->assertOk()
            ->assertSee('currency_exchange');

        $refunded = $this->makeOrder([
            'status' => 'cancelled', 'payment_method' => 'momo', 'payment_status' => 'refunded',
            'refunded_at' => now(), 'cancel_reason' => 'Đã hoàn tiền test',
        ]);
        $this->get("/admin/orders/{$refunded->id}")
            ->assertOk()
            ->assertSee('Đã hoàn tiền');
    }
}
