<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Kiểm tra các endpoint backend (nhân viên giao hàng, chi tiết đơn lễ tân, đối soát COD) vừa
 * chuyển từ form POST cổ điển sang submit qua fetch (AJAX) — cùng đợt với FrontendAjaxTest.php,
 * dùng postJson()/getJson() để khớp đúng header fetch() thật gửi lên.
 */
class BackendAjaxTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(array $overrides = []): Order
    {
        return Order::create(array_merge([
            'order_code' => 'HPY-' . strtoupper(Str::random(8)),
            'customer_name' => 'Khách hàng Test',
            'customer_phone' => '0901234567',
            'delivery_address' => '123 Đường Test',
            'total_amount' => 50000,
            'final_amount' => 50000,
            'payment_status' => 'unpaid',
            'payment_method' => 'cod',
            'status' => 'pending',
            'delivery_type' => 'delivery',
        ], $overrides));
    }

    // ───────────────────────── Nhân viên giao hàng ─────────────────────────

    public function test_delivery_ship_ajax_returns_redirect_url(): void
    {
        $shipper = User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery']);
        $order = $this->makeOrder(['status' => 'confirmed', 'delivery_staff_id' => $shipper->id]);

        $response = $this->actingAs($shipper)->patchJson("/staff/delivery/orders/{$order->id}/ship");

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertNotNull($response->json('redirect_url'));
        $this->assertSame('shipping', $order->fresh()->status);
    }

    public function test_delivery_fail_ajax_returns_422_when_reason_too_short(): void
    {
        $shipper = User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery']);
        $order = $this->makeOrder(['status' => 'shipping', 'delivery_staff_id' => $shipper->id]);

        $response = $this->actingAs($shipper)->patchJson("/staff/delivery/orders/{$order->id}/fail", ['reason' => 'ab']);

        $response->assertStatus(422);
        $this->assertSame('shipping', $order->fresh()->status);
    }

    public function test_delivery_cannot_act_on_another_shippers_order(): void
    {
        $shipper = User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery']);
        $otherShipper = User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery']);
        $order = $this->makeOrder(['status' => 'confirmed', 'delivery_staff_id' => $otherShipper->id]);

        $this->actingAs($shipper)->patchJson("/staff/delivery/orders/{$order->id}/ship")->assertStatus(403);
    }

    // ───────────────────────── Chi tiết đơn (lễ tân) ─────────────────────────

    public function test_reception_confirm_order_ajax_success(): void
    {
        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $order = $this->makeOrder(['status' => 'pending']);

        $response = $this->actingAs($receptionist)
            ->patchJson("/staff/reception/orders/{$order->id}/status", ['status' => 'confirmed']);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertSame('confirmed', $order->fresh()->status);
    }

    public function test_reception_cancel_order_ajax_returns_422_when_reason_too_short(): void
    {
        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $order = $this->makeOrder(['status' => 'pending']);

        $response = $this->actingAs($receptionist)
            ->patchJson("/staff/reception/orders/{$order->id}/status", ['status' => 'cancelled', 'cancel_reason' => 'ab']);

        $response->assertStatus(422);
        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_reception_assign_delivery_ajax_success(): void
    {
        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $shipper = User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery']);
        $order = $this->makeOrder(['status' => 'confirmed']);

        $response = $this->actingAs($receptionist)
            ->postJson("/staff/reception/orders/{$order->id}/assign-delivery", ['delivery_staff_id' => $shipper->id]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertSame($shipper->id, $order->fresh()->delivery_staff_id);
    }

    public function test_reception_confirm_cash_ajax_returns_422_when_amount_insufficient(): void
    {
        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $order = $this->makeOrder(['payment_method' => 'cash', 'payment_status' => 'unpaid', 'final_amount' => 50000]);

        $response = $this->actingAs($receptionist)
            ->postJson("/staff/reception/orders/{$order->id}/confirm-cash", ['amount_tendered' => 20000]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('amount_tendered');
        $this->assertSame('unpaid', $order->fresh()->payment_status);
    }

    public function test_reception_confirm_cash_ajax_success(): void
    {
        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $order = $this->makeOrder(['payment_method' => 'cash', 'payment_status' => 'unpaid', 'final_amount' => 50000]);

        $response = $this->actingAs($receptionist)
            ->postJson("/staff/reception/orders/{$order->id}/confirm-cash", ['amount_tendered' => 100000]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    // ───────────────────────── Đối soát COD ─────────────────────────

    public function test_cod_settle_one_ajax_success(): void
    {
        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $shipper = User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery']);
        $order = $this->makeOrder([
            'status' => 'completed', 'payment_method' => 'cod', 'delivery_staff_id' => $shipper->id,
        ]);

        $response = $this->actingAs($receptionist)->postJson("/staff/reception/cod-settlement/orders/{$order->id}/settle");

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertNotNull($order->fresh()->cod_settled_at);
    }

    public function test_cod_settle_all_ajax_reports_zero_orders(): void
    {
        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $shipper = User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery']);

        $response = $this->actingAs($receptionist)->postJson("/staff/reception/cod-settlement/staff/{$shipper->id}/settle-all");

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $response->assertJsonPath('message', "{$shipper->name} không có đơn COD nào cần đối soát.");
    }

}
