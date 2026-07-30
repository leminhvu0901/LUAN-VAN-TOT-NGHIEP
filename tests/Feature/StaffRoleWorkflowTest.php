<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Order;
use App\Models\Material;
use App\Models\MaterialImport;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StaffRoleWorkflowTest extends TestCase
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

    /**
     * Guest/customer không vào được bất kỳ route staff nào.
     */
    public function test_guest_and_customer_cannot_access_staff_routes(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->get('/staff/reception/dashboard')->assertRedirect('/login');
        $this->get('/staff/delivery/dashboard')->assertRedirect('/login');
        $this->get('/admin/staff-accounts')->assertRedirect('/login');

        $this->actingAs($customer);
        $this->get('/staff/reception/dashboard')->assertStatus(403);
        $this->get('/staff/delivery/dashboard')->assertStatus(403);
        $this->get('/admin/staff-accounts')->assertStatus(403);
    }

    /**
     * Lễ tân vào được khu vực lễ tân, bị chặn ở khu vực vận chuyển (và ngược lại). Admin vào được cả hai.
     */
    public function test_receptionist_and_delivery_are_isolated_from_each_other(): void
    {
        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $delivery = User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($receptionist);
        $this->get('/staff/reception/dashboard')->assertStatus(200);
        $this->get('/staff/delivery/dashboard')->assertRedirect(route('staff.reception.dashboard'));
        $this->get('/admin/staff-accounts')->assertRedirect(route('staff.reception.dashboard'));

        $this->actingAs($delivery);
        $this->get('/staff/delivery/dashboard')->assertStatus(200);
        $this->get('/staff/reception/dashboard')->assertRedirect(route('staff.delivery.dashboard'));
        $this->get('/staff/reception/materials')->assertRedirect(route('staff.delivery.dashboard'));

        $this->actingAs($admin);
        $this->get('/staff/reception/dashboard')->assertStatus(200);
        $this->get('/staff/delivery/dashboard')->assertStatus(200);
        $this->get('/admin/staff-accounts')->assertStatus(200);
    }

    /**
     * Lễ tân cập nhật trạng thái đơn hàng (PATCH) qua route reception mới.
     */
    /**
     * Render toàn bộ trang chính khu vực lễ tân/vận chuyển — bắt lỗi "View not found"
     * (vd include sai đường dẫn) mà test API-only (PATCH/POST) không phát hiện được.
     */
    public function test_reception_and_delivery_pages_render_without_view_errors(): void
    {
        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $delivery = User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery']);
        $material = Material::create([
            'name' => 'Trân châu', 'unit' => 'kg', 'unit_price' => 50000, 'current_stock' => 10, 'is_active' => true,
        ]);
        $this->makeOrder(['delivery_staff_id' => $delivery->id, 'status' => 'confirmed', 'assigned_at' => now()]);

        $this->actingAs($receptionist);
        $this->get('/staff/reception/dashboard')->assertStatus(200);
        $this->get('/staff/reception/orders')->assertStatus(200);
        $this->get('/staff/reception/materials')->assertStatus(200);
        $this->get("/staff/reception/materials/{$material->id}/imports")->assertStatus(200);
        $this->get('/staff/reception/orders/create')->assertStatus(200);
        $this->get('/staff/reception/promotions')->assertStatus(200);
        $this->get('/staff/reception/profile')->assertStatus(200);
        $this->get('/staff/reception/cod-settlement')->assertStatus(200);

        $this->actingAs($delivery);
        $this->get('/staff/delivery/dashboard')->assertStatus(200);
        $this->get('/staff/delivery/orders')->assertStatus(200);
        $this->get('/staff/delivery/orders?tab=shipping')->assertStatus(200);
        $this->get('/staff/delivery/orders?tab=history')->assertStatus(200);
        $this->get('/staff/delivery/profile')->assertStatus(200);
    }

    public function test_receptionist_can_update_order_status(): void
    {
        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $order = $this->makeOrder();

        $this->actingAs($receptionist);

        $response = $this->patch("/staff/reception/orders/{$order->id}/status", ['status' => 'confirmed']);
        $response->assertRedirect();
        $this->assertEquals('confirmed', $order->fresh()->status);

        $response = $this->patch("/staff/reception/orders/{$order->id}/status", ['status' => 'invalid_status']);
        $response->assertSessionHasErrors('status');
    }

    /**
     * Lễ tân phân công nhân viên vận chuyển cho đơn đã xác nhận; không phân công được nhân viên
     * không phải staff_type=delivery (vd gán nhầm 1 lễ tân khác).
     */
    public function test_receptionist_can_assign_delivery_staff_with_correct_authorization(): void
    {
        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $delivery = User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery']);
        $anotherReceptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $order = $this->makeOrder(['status' => 'confirmed']);

        $this->actingAs($receptionist);

        $response = $this->post("/staff/reception/orders/{$order->id}/assign-delivery", [
            'delivery_staff_id' => $anotherReceptionist->id,
        ]);
        $response->assertSessionHasErrors('delivery_staff_id');
        $this->assertNull($order->fresh()->delivery_staff_id);

        $response = $this->post("/staff/reception/orders/{$order->id}/assign-delivery", [
            'delivery_staff_id' => $delivery->id,
        ]);
        $response->assertRedirect();

        $order = $order->fresh();
        $this->assertEquals($delivery->id, $order->delivery_staff_id);
        $this->assertEquals($receptionist->id, $order->assigned_by);
        $this->assertNotNull($order->assigned_at);
    }

    /**
     * Nhân viên vận chuyển chỉ thấy đơn được phân công cho chính mình; không xem được đơn của người khác.
     */
    public function test_delivery_staff_only_sees_own_assigned_orders(): void
    {
        $delivery = User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery']);
        $otherDelivery = User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery']);

        $myOrder = $this->makeOrder(['status' => 'confirmed', 'delivery_staff_id' => $delivery->id, 'assigned_at' => now()]);
        $otherOrder = $this->makeOrder(['status' => 'confirmed', 'delivery_staff_id' => $otherDelivery->id, 'assigned_at' => now()]);

        $this->actingAs($delivery);

        $response = $this->get('/staff/delivery/orders?tab=assigned');
        $response->assertStatus(200);
        $response->assertSee($myOrder->order_code);
        $response->assertDontSee($otherOrder->order_code);

        // Không xem được chi tiết đơn không được phân công cho mình -> 403
        $this->get("/staff/delivery/orders/{$otherOrder->id}")->assertStatus(403);
        $this->get("/staff/delivery/orders/{$myOrder->id}")->assertStatus(200);
    }

    /**
     * Nhân viên vận chuyển nhận đơn (confirmed->shipping) và hoàn thành (shipping->completed) đúng luồng.
     */
    public function test_delivery_staff_updates_shipping_and_completed_correctly(): void
    {
        $delivery = User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery']);
        $order = $this->makeOrder(['status' => 'confirmed', 'delivery_staff_id' => $delivery->id, 'assigned_at' => now()]);

        $this->actingAs($delivery);

        $this->patch("/staff/delivery/orders/{$order->id}/ship")->assertRedirect();
        $this->assertEquals('shipping', $order->fresh()->status);

        $this->patch("/staff/delivery/orders/{$order->id}/complete")->assertRedirect();
        $this->assertEquals('completed', $order->fresh()->status);
    }

    /**
     * 3 tab của danh sách đơn giao hàng (assigned/shipping/history) phải lọc đúng theo trạng thái,
     * mỗi đơn chỉ xuất hiện ở ĐÚNG 1 tab - trước đây chỉ được smoke-test load trang, chưa kiểm tra
     * nội dung lọc thật sự đúng theo tab nào.
     */
    public function test_delivery_orders_index_tabs_filter_by_status_correctly(): void
    {
        $delivery = User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery']);

        $assignedOrder = $this->makeOrder(['status' => 'confirmed', 'delivery_staff_id' => $delivery->id, 'assigned_at' => now()]);
        $shippingOrder = $this->makeOrder(['status' => 'shipping', 'delivery_staff_id' => $delivery->id, 'assigned_at' => now()]);
        $completedOrder = $this->makeOrder(['status' => 'completed', 'delivery_staff_id' => $delivery->id, 'assigned_at' => now()]);
        $cancelledOrder = $this->makeOrder(['status' => 'cancelled', 'delivery_staff_id' => $delivery->id, 'assigned_at' => now()]);

        $this->actingAs($delivery);

        $response = $this->get('/staff/delivery/orders?tab=assigned');
        $response->assertSee($assignedOrder->order_code);
        $response->assertDontSee($shippingOrder->order_code);
        $response->assertDontSee($completedOrder->order_code);
        $response->assertDontSee($cancelledOrder->order_code);

        $response = $this->get('/staff/delivery/orders?tab=shipping');
        $response->assertSee($shippingOrder->order_code);
        $response->assertDontSee($assignedOrder->order_code);
        $response->assertDontSee($completedOrder->order_code);

        // Tab "Lịch sử" gộp cả completed lẫn cancelled.
        $response = $this->get('/staff/delivery/orders?tab=history');
        $response->assertSee($completedOrder->order_code);
        $response->assertSee($cancelledOrder->order_code);
        $response->assertDontSee($assignedOrder->order_code);
        $response->assertDontSee($shippingOrder->order_code);

        // Query "tab" không hợp lệ -> mặc định về "assigned" (confirmed), không lỗi/không rỗng bất thường.
        $response = $this->get('/staff/delivery/orders?tab=not_a_real_tab');
        $response->assertSee($assignedOrder->order_code);
    }

    /**
     * Nhân viên vận chuyển KHÔNG được thao tác (nhận đơn/hoàn thành/báo thất bại) trên đơn được phân
     * công cho người khác - authorizeOwnership() chỉ mới được test qua show(), chưa test qua chính
     * các action làm thay đổi trạng thái đơn.
     */
    public function test_delivery_staff_cannot_ship_complete_or_fail_another_staffs_order(): void
    {
        $delivery = User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery']);
        $otherDelivery = User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery']);
        $assignedOrder = $this->makeOrder(['status' => 'confirmed', 'delivery_staff_id' => $otherDelivery->id, 'assigned_at' => now()]);
        $shippingOrder = $this->makeOrder(['status' => 'shipping', 'delivery_staff_id' => $otherDelivery->id, 'assigned_at' => now()]);

        $this->actingAs($delivery);

        $this->patch("/staff/delivery/orders/{$assignedOrder->id}/ship")->assertStatus(403);
        $this->assertEquals('confirmed', $assignedOrder->fresh()->status);

        $this->patch("/staff/delivery/orders/{$shippingOrder->id}/complete")->assertStatus(403);
        $this->assertEquals('shipping', $shippingOrder->fresh()->status);

        $this->patch("/staff/delivery/orders/{$shippingOrder->id}/fail", [
            'reason' => 'Thử thao tác đơn không phải của mình', 'failure_type' => 'other',
        ])->assertStatus(403);
        $this->assertEquals('shipping', $shippingOrder->fresh()->status);
    }

    /**
     * Admin được phép thao tác lên BẤT KỲ đơn giao hàng nào (authorizeOwnership() cho phép role admin
     * đi qua, không chỉ đúng nhân viên được phân công) - phục vụ giám sát/hỗ trợ khi cần.
     */
    public function test_admin_can_ship_any_delivery_order_regardless_of_assigned_staff(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $delivery = User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery']);
        $order = $this->makeOrder(['status' => 'confirmed', 'delivery_staff_id' => $delivery->id, 'assigned_at' => now()]);

        $this->actingAs($admin)->patch("/staff/delivery/orders/{$order->id}/ship")->assertRedirect();

        $this->assertEquals('shipping', $order->fresh()->status);
    }

    /**
     * Trang tổng quan (dashboard) của nhân viên vận chuyển: 4 thẻ đếm số đơn theo trạng thái + danh
     * sách "đơn gần đây" (tối đa 5, chỉ gồm confirmed/shipping) - trước đây chỉ được test 2 số liệu
     * COD, chưa test các thẻ đếm đơn và danh sách đơn gần đây.
     */
    public function test_delivery_dashboard_shows_correct_order_counts_and_recent_orders(): void
    {
        $delivery = User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery']);
        $otherDelivery = User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery']);

        $this->makeOrder(['status' => 'confirmed', 'delivery_staff_id' => $delivery->id, 'assigned_at' => now()]);
        $this->makeOrder(['status' => 'confirmed', 'delivery_staff_id' => $delivery->id, 'assigned_at' => now()]);
        $this->makeOrder(['status' => 'shipping', 'delivery_staff_id' => $delivery->id, 'assigned_at' => now()]);
        $this->makeOrder(['status' => 'completed', 'delivery_staff_id' => $delivery->id, 'assigned_at' => now()]);
        $this->makeOrder([
            'status' => 'cancelled', 'delivery_staff_id' => $delivery->id, 'assigned_at' => now(),
            'delivery_failed_at' => now(), 'delivery_failed_reason' => 'Khách không nhận', 'delivery_failure_type' => 'other',
        ]);
        // Đơn của người khác không được tính vào bất kỳ thẻ nào của $delivery.
        $this->makeOrder(['status' => 'confirmed', 'delivery_staff_id' => $otherDelivery->id, 'assigned_at' => now()]);

        $response = $this->actingAs($delivery)->get('/staff/delivery/dashboard');

        $response->assertStatus(200);
        $response->assertViewHas('pendingPickupCount', 2);
        $response->assertViewHas('shippingCount', 1);
        $response->assertViewHas('completedCount', 1);
        $response->assertViewHas('failedCount', 1);
        $response->assertViewHas('recentOrders', function ($recentOrders) {
            // Chỉ gồm đơn confirmed/shipping (2 + 1 = 3), không gồm completed/cancelled.
            return $recentOrders->count() === 3
                && $recentOrders->every(fn ($o) => in_array($o->status, ['confirmed', 'shipping'], true));
        });
    }

    /**
     * order_items.product_id có restrictOnDelete() nên 1 sản phẩm đã có lịch sử đơn hàng KHÔNG BAO
     * GIỜ bị xóa cứng được (khớp rule đã xác nhận ở HardenedProductController::destroy() - chỉ
     * ngừng kinh doanh, không xóa) - JOIN sang bảng products vì vậy luôn khớp được. Cột snapshot
     * (order_items.product_name/product_image) mới là cái có thể null với dữ liệu cũ; khi đó trang
     * chi tiết đơn của nhân viên vận chuyển phải rơi về đúng tên/ảnh THẬT của sản phẩm, không hiện
     * trống trơn hay vỡ trang.
     */
    public function test_delivery_order_show_page_falls_back_to_live_product_when_snapshot_missing(): void
    {
        $delivery = User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery']);
        $order = $this->makeOrder(['status' => 'shipping', 'delivery_staff_id' => $delivery->id, 'assigned_at' => now()]);
        $product = $this->makeProduct(['name' => 'Trà sữa trân châu (tên hiện tại)']);

        DB::table('order_items')->insert([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => null,
            'product_image' => null,
            'quantity' => 1,
            'unit_price' => 30000,
        ]);

        $response = $this->actingAs($delivery)->get("/staff/delivery/orders/{$order->id}");

        $response->assertStatus(200);
        $response->assertSee('Trà sữa trân châu (tên hiện tại)');
    }

    /**
     * Giao thất bại: bắt buộc nhập lý do + loại lý do, đơn chuyển 'cancelled' kèm delivery_failed_reason/at,
     * kể cả khi đơn đã thanh toán trước (payment_status=paid) — với loại lý do 'customer_unreachable'
     * (khách không nhận hàng) thì hủy thẳng KHÔNG hoàn tiền, theo quyết định nghiệp vụ đã duyệt.
     */
    public function test_delivery_staff_marks_failed_delivery_with_required_reason(): void
    {
        $delivery = User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery']);
        $order = $this->makeOrder([
            'status' => 'shipping',
            'delivery_staff_id' => $delivery->id,
            'assigned_at' => now(),
            'payment_method' => 'momo',
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);

        $this->actingAs($delivery);

        // Thiếu lý do -> bị từ chối
        $this->patch("/staff/delivery/orders/{$order->id}/fail", [])->assertSessionHasErrors(['reason', 'failure_type']);
        $this->assertEquals('shipping', $order->fresh()->status);

        // Có lý do + loại "khách không nhận hàng" -> hủy thẳng dù đã thanh toán (paid), KHÔNG hoàn tiền
        \Illuminate\Support\Facades\Http::fake();
        $response = $this->patch("/staff/delivery/orders/{$order->id}/fail", [
            'reason' => 'Khách không nghe máy, không có người nhận',
            'failure_type' => 'customer_unreachable',
        ]);
        $response->assertRedirect();
        \Illuminate\Support\Facades\Http::assertNothingSent();

        $order = $order->fresh();
        $this->assertEquals('cancelled', $order->status);
        $this->assertEquals('paid', $order->payment_status);
        $this->assertEquals('Khách không nghe máy, không có người nhận', $order->delivery_failed_reason);
        $this->assertEquals('customer_unreachable', $order->delivery_failure_type);
        $this->assertNotNull($order->delivery_failed_at);
    }

    /**
     * Giao thất bại vì "hàng hư hỏng/đổ vỡ" trên đơn MoMo đã thanh toán -> tự động hoàn tiền MoMo
     * trước khi hủy đơn, đơn chuyển payment_status=refunded + status=cancelled.
     */
    public function test_delivery_staff_marks_failed_delivery_damaged_triggers_refund(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            '*/v2/gateway/api/refund' => \Illuminate\Support\Facades\Http::response(['resultCode' => 0, 'transId' => 'REFUND-TX-1'], 200),
        ]);

        $delivery = User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery']);
        $order = $this->makeOrder([
            'status' => 'shipping',
            'delivery_staff_id' => $delivery->id,
            'assigned_at' => now(),
            'payment_method' => 'momo',
            'payment_status' => 'paid',
            'paid_at' => now(),
            'payment_transaction_id' => 'ORIGINAL-TX-1',
        ]);

        $this->actingAs($delivery);
        $response = $this->patch("/staff/delivery/orders/{$order->id}/fail", [
            'reason' => 'Trà sữa đổ vỡ trong lúc vận chuyển',
            'failure_type' => 'damaged',
        ]);
        $response->assertRedirect();

        $order = $order->fresh();
        $this->assertEquals('cancelled', $order->status);
        $this->assertEquals('refunded', $order->payment_status);
        $this->assertEquals('REFUND-TX-1', $order->refund_transaction_id);
        $this->assertNotNull($order->refunded_at);
        $this->assertEquals('damaged', $order->delivery_failure_type);
    }

    /**
     * Giao thất bại vì "hàng hư hỏng/đổ vỡ" nhưng MoMo hoàn tiền thất bại -> vẫn hủy đơn (shipper
     * không thể kẹt ngoài đường chờ retry), nhưng KHÔNG đánh dấu đã hoàn tiền — payment_status vẫn
     * 'paid' trên đơn 'cancelled' để lễ tân/admin biết cần xử lý hoàn tiền thủ công.
     */
    public function test_delivery_staff_marks_failed_delivery_damaged_refund_failure_still_cancels(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            '*/v2/gateway/api/refund' => \Illuminate\Support\Facades\Http::response(['resultCode' => 99, 'message' => 'Lỗi hệ thống MoMo'], 200),
        ]);

        $delivery = User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery']);
        $order = $this->makeOrder([
            'status' => 'shipping',
            'delivery_staff_id' => $delivery->id,
            'assigned_at' => now(),
            'payment_method' => 'momo',
            'payment_status' => 'paid',
            'paid_at' => now(),
            'payment_transaction_id' => 'ORIGINAL-TX-2',
        ]);

        $this->actingAs($delivery);
        $response = $this->patch("/staff/delivery/orders/{$order->id}/fail", [
            'reason' => 'Trà sữa đổ vỡ trong lúc vận chuyển',
            'failure_type' => 'damaged',
        ]);
        $response->assertRedirect();

        $order = $order->fresh();
        $this->assertEquals('cancelled', $order->status);
        $this->assertEquals('paid', $order->payment_status);
        $this->assertNull($order->refund_transaction_id);
    }

    /**
     * Giao thất bại với loại lý do "Khác" trên đơn MoMo đã thanh toán -> giống 'customer_unreachable',
     * hủy thẳng KHÔNG hoàn tiền (chỉ 'damaged' mới tự động hoàn tiền).
     */
    public function test_delivery_staff_marks_failed_delivery_other_reason_does_not_trigger_refund(): void
    {
        \Illuminate\Support\Facades\Http::fake();

        $delivery = User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery']);
        $order = $this->makeOrder([
            'status' => 'shipping',
            'delivery_staff_id' => $delivery->id,
            'assigned_at' => now(),
            'payment_method' => 'momo',
            'payment_status' => 'paid',
            'paid_at' => now(),
            'payment_transaction_id' => 'ORIGINAL-TX-OTHER',
        ]);

        $this->actingAs($delivery);
        $response = $this->patch("/staff/delivery/orders/{$order->id}/fail", [
            'reason' => 'Không tìm thấy địa chỉ giao hàng',
            'failure_type' => 'other',
        ]);
        $response->assertRedirect();
        \Illuminate\Support\Facades\Http::assertNothingSent();

        $order = $order->fresh();
        $this->assertEquals('cancelled', $order->status);
        $this->assertEquals('paid', $order->payment_status);
        $this->assertEquals('other', $order->delivery_failure_type);
    }

    /**
     * failure_type sai giá trị (không thuộc damaged/customer_unreachable/other) -> bị từ chối validate.
     */
    public function test_delivery_staff_fail_rejects_invalid_failure_type(): void
    {
        $delivery = User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery']);
        $order = $this->makeOrder(['status' => 'shipping', 'delivery_staff_id' => $delivery->id, 'assigned_at' => now()]);

        $this->actingAs($delivery);
        $this->patch("/staff/delivery/orders/{$order->id}/fail", [
            'reason' => 'Lý do hợp lệ nhưng loại sai',
            'failure_type' => 'invalid_type',
        ])->assertSessionHasErrors('failure_type');

        $this->assertEquals('shipping', $order->fresh()->status);
    }

    /**
     * Nhân viên vận chuyển không được: xem kho, xóa đơn, sửa tổng tiền.
     */
    public function test_delivery_staff_cannot_access_inventory_or_destructive_actions(): void
    {
        $delivery = User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery']);
        $order = $this->makeOrder(['status' => 'shipping', 'delivery_staff_id' => $delivery->id, 'assigned_at' => now()]);

        $this->actingAs($delivery);

        // Không có route kho nào trong khu vực delivery
        $this->get('/staff/reception/materials')->assertRedirect(route('staff.delivery.dashboard'));

        // Không có route xóa đơn/sửa tổng tiền nào được đăng ký cho delivery — thử route admin
        // (IsAdmin không cho role=staff vào, tự chuyển hướng về đúng dashboard của họ thay vì 403)
        $this->delete("/admin/orders/{$order->id}")->assertRedirect(route('staff.delivery.dashboard'));
        $this->assertNotNull($order->fresh());
    }

    /**
     * Lễ tân và vận chuyển đều không truy cập được các trang quản trị admin-only
     * (cài đặt hệ thống, quản lý sản phẩm/khuyến mãi có quyền ghi) — chỉ admin mới vào được.
     */
    public function test_receptionist_and_delivery_cannot_access_admin_settings(): void
    {
        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $delivery = User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($receptionist);
        $this->get('/admin/settings')->assertRedirect(route('staff.reception.dashboard'));

        $this->actingAs($delivery);
        $this->get('/admin/settings')->assertRedirect(route('staff.delivery.dashboard'));

        $this->actingAs($admin);
        $this->get('/admin/settings')->assertStatus(200);
    }

    /**
     * Request chuyển trạng thái trái luồng (vd shipping -> pending) phải bị từ chối bởi OrderWorkflowService.
     */
    public function test_out_of_flow_status_transition_is_rejected(): void
    {
        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $order = $this->makeOrder(['status' => 'shipping']);

        $this->actingAs($receptionist);

        $response = $this->patch("/staff/reception/orders/{$order->id}/status", ['status' => 'pending']);
        $response->assertSessionHasErrors('status');
        $this->assertEquals('shipping', $order->fresh()->status);
    }

    /**
     * Đơn MoMo "chờ thanh toán" (pending/unpaid) treo quá 15 phút bị tự động hủy — giải phóng tồn
     * kho đã trừ trước (đơn tại quầy). Đơn còn mới, đơn COD, đơn đã xác nhận/đã thanh toán thì
     * KHÔNG bị đụng tới.
     */
    public function test_cancel_stale_pending_payments_only_cancels_old_unpaid_momo_orders(): void
    {
        $stalePickup = $this->makeOrder([
            'delivery_type' => 'pickup', 'delivery_address' => null,
            'payment_method' => 'momo', 'payment_status' => 'unpaid', 'status' => 'pending',
            'created_at' => now()->subMinutes(20),
        ]);
        $freshMomo = $this->makeOrder([
            'payment_method' => 'momo', 'payment_status' => 'unpaid', 'status' => 'pending',
            'created_at' => now()->subMinutes(5),
        ]);
        $staleCod = $this->makeOrder([
            'payment_method' => 'cod', 'payment_status' => 'unpaid', 'status' => 'pending',
            'created_at' => now()->subMinutes(20),
        ]);
        $staleButConfirmed = $this->makeOrder([
            'payment_method' => 'momo', 'payment_status' => 'unpaid', 'status' => 'confirmed',
            'created_at' => now()->subMinutes(20),
        ]);
        $staleButPaid = $this->makeOrder([
            'payment_method' => 'momo', 'payment_status' => 'paid', 'status' => 'pending',
            'created_at' => now()->subMinutes(20),
        ]);

        $cancelledCount = app(\App\Services\OrderWorkflowService::class)->cancelStalePendingPayments(15);

        $this->assertEquals(1, $cancelledCount);
        $this->assertEquals('cancelled', $stalePickup->fresh()->status);
        $this->assertEquals('pending', $freshMomo->fresh()->status);
        $this->assertEquals('pending', $staleCod->fresh()->status);
        $this->assertEquals('confirmed', $staleButConfirmed->fresh()->status);
        $this->assertEquals('pending', $staleButPaid->fresh()->status);
    }

    /**
     * Mở trang danh sách đơn của lễ tân cũng tự dọn luôn đơn MoMo treo quá lâu — để có hiệu quả
     * ngay cả khi không có Task Scheduler/cron thật chạy `orders:cancel-stale-pending`.
     */
    public function test_visiting_reception_order_list_opportunistically_cancels_stale_momo_orders(): void
    {
        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $stalePickup = $this->makeOrder([
            'delivery_type' => 'pickup', 'delivery_address' => null,
            'payment_method' => 'momo', 'payment_status' => 'unpaid', 'status' => 'pending',
            'created_at' => now()->subMinutes(30),
        ]);

        $this->actingAs($receptionist);
        $this->get('/staff/reception/orders')->assertStatus(200);

        $this->assertEquals('cancelled', $stalePickup->fresh()->status);
    }

    /**
     * Admin tạo tài khoản nhân viên với staff_type hợp lệ; giá trị staff_type giả bị từ chối.
     */
    public function test_admin_creates_staff_account_with_valid_staff_type_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $payload = [
            'name' => 'Nhân viên Mới',
            'email' => 'nv.moi@happytea.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'is_active' => 1,
        ];

        // staff_type giả -> bị từ chối
        $response = $this->post('/admin/staff-accounts', $payload + ['staff_type' => 'super_admin']);
        $response->assertSessionHasErrors('staff_type');
        $this->assertDatabaseMissing('users', ['email' => 'nv.moi@happytea.com']);

        // staff_type hợp lệ -> tạo thành công, role luôn bị ép 'staff'
        $response = $this->post('/admin/staff-accounts', $payload + ['staff_type' => 'delivery']);
        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'nv.moi@happytea.com',
            'role' => 'staff',
            'staff_type' => 'delivery',
        ]);
    }

    /**
     * Lễ tân nhập kho và ghi nhận audit log (giữ nguyên hành vi đã có, chỉ đổi route sang reception).
     */
    public function test_receptionist_can_import_stock_with_audit_logging(): void
    {
        $staff = User::factory()->create([
            'name' => 'Nguyễn Nhân Viên',
            'email' => 'nv@happytea.com',
            'role' => 'staff',
            'staff_type' => 'receptionist',
        ]);

        $material = Material::create([
            'name' => 'Sữa đặc',
            'unit' => 'hộp',
            'unit_price' => 20000,
            'current_stock' => 10,
            'is_active' => true
        ]);

        $this->actingAs($staff);

        $response = $this->post("/staff/reception/materials/{$material->id}/imports", [
            'quantity' => 5,
            'total_price' => 110000,
            'note' => 'Nhập lô hàng sữa đặc tháng 7',
        ]);

        $response->assertRedirect();

        $expectedNote = '[Nhân viên: Nguyễn Nhân Viên (nv@happytea.com)] Nhập lô hàng sữa đặc tháng 7';

        $this->assertDatabaseHas('material_imports', [
            'material_id' => $material->id,
            'quantity' => 5,
            'total_price' => 110000,
            'note' => $expectedNote,
        ]);

        $material = $material->fresh();
        $this->assertEquals(15, (float) $material->current_stock);
        $this->assertEquals(20666.6666, (float) $material->unit_price);
    }

    /**
     * Lễ tân xuất kho sử dụng (lấy hàng ra khỏi kho để dùng tại quầy, không qua đơn hàng) - LUÔN từ
     * một lô cụ thể do người dùng chọn (nút "Xuất" trên từng dòng lô), y hệt cách admin làm
     * (MaterialController(Admin)::consumeBatch()) - không còn bản "tự động chọn lô theo hạn dùng gần
     * nhất" như trước. Điểm quan trọng nhất cần khóa lại: chỉ trừ ĐÚNG lô được chọn, các lô khác của
     * cùng vật tư không bị đụng tới.
     */
    public function test_receptionist_can_consume_stock_from_a_specific_lot_without_touching_other_lots(): void
    {
        $staff = User::factory()->create([
            'name' => 'Trần Thu Ngân',
            'email' => 'tn@happytea.com',
            'role' => 'staff',
            'staff_type' => 'receptionist',
        ]);

        $material = Material::create([
            'name' => 'Ly nhựa 500ml',
            'unit' => 'lốc',
            'unit_price' => 15000,
            'current_stock' => 15,
            'is_active' => true,
        ]);

        $olderLot = MaterialImport::create(['material_id' => $material->id, 'quantity' => 10, 'remaining_quantity' => 10, 'total_price' => 150000]);
        $newerLot = MaterialImport::create(['material_id' => $material->id, 'quantity' => 5, 'remaining_quantity' => 5, 'total_price' => 100000]);

        $this->actingAs($staff);

        // Chủ động chọn xuất từ lô MỚI HƠN, dù FIFO/hạn dùng sẽ ưu tiên lô cũ hơn nếu dùng form chung.
        $response = $this->post("/staff/reception/materials/imports/{$newerLot->id}/consume-batch", [
            'quantity' => 1,
            'reason' => 'Hết ly tại quầy, lấy thêm để pha chế',
        ]);

        $response->assertRedirect();

        $material = $material->fresh();
        $this->assertEquals(14, (float) $material->current_stock);
        $this->assertEquals(4, (float) $newerLot->fresh()->remaining_quantity);
        $this->assertEquals(10, (float) $olderLot->fresh()->remaining_quantity, 'Lô không được chọn không được đụng tới.');

        $expectedNote = 'Xuất dùng từ lô LOT-' . $newerLot->id . ': [Nhân viên: Trần Thu Ngân (tn@happytea.com)] Hết ly tại quầy, lấy thêm để pha chế';
        $this->assertDatabaseHas('material_imports', [
            'material_id' => $material->id,
            'quantity' => -1,
            'note' => $expectedNote,
        ]);

        // Vượt tồn kho CỦA RIÊNG LÔ NÀY (dù material còn đủ tổng) -> vẫn phải chặn.
        $response = $this->post("/staff/reception/materials/imports/{$newerLot->id}/consume-batch", [
            'quantity' => 999,
            'reason' => 'Thử vượt tồn kho',
        ]);
        $response->assertSessionHasErrors('quantity');
        $this->assertEquals(4, (float) $newerLot->fresh()->remaining_quantity);
        $this->assertEquals(14, (float) $material->fresh()->current_stock);
    }

    /**
     * Admin trước đây KHÔNG có đường nào để ghi nhận "Xuất kho sử dụng" (chỉ khu vực lễ tân có) dù
     * có toàn quyền quản lý kho. Thêm MaterialController(Admin)::consumeBatch() - LUÔN xuất từ một lô
     * cụ thể do người dùng chọn (nút "Xuất" trên từng dòng lô), không có bản "tự động chọn lô" như
     * lễ tân vì bên admin đã thấy rõ danh sách lô ngay trên trang, không cần thêm 1 con đường mơ hồ
     * nữa. Điểm quan trọng nhất cần khóa lại: chỉ trừ ĐÚNG lô được chọn, các lô khác của cùng vật tư
     * không bị đụng tới.
     */
    public function test_admin_can_consume_stock_from_a_specific_lot_without_touching_other_lots(): void
    {
        $admin = User::factory()->create([
            'name' => 'Quản Trị Viên', 'email' => 'admin@happytea.com', 'role' => 'admin',
        ]);
        $material = Material::create([
            'name' => 'Ly nhựa 500ml', 'unit' => 'lốc', 'unit_price' => 15000, 'current_stock' => 15, 'is_active' => true,
        ]);
        $olderLot = MaterialImport::create(['material_id' => $material->id, 'quantity' => 10, 'remaining_quantity' => 10, 'total_price' => 150000]);
        $newerLot = MaterialImport::create(['material_id' => $material->id, 'quantity' => 5, 'remaining_quantity' => 5, 'total_price' => 100000]);

        $this->actingAs($admin);

        // Chủ động chọn xuất từ lô MỚI HƠN, dù FIFO/hạn dùng sẽ ưu tiên lô cũ hơn nếu dùng form chung.
        $response = $this->post("/admin/materials/imports/{$newerLot->id}/consume-batch", [
            'quantity' => 3,
            'reason' => 'Cần đúng loại ly của lô mới cho đơn hàng đặc biệt',
        ]);

        $response->assertRedirect();
        $this->assertEquals(2, (float) $newerLot->fresh()->remaining_quantity);
        $this->assertEquals(10, (float) $olderLot->fresh()->remaining_quantity, 'Lô không được chọn không được đụng tới.');
        $this->assertEquals(12, (float) $material->fresh()->current_stock);

        $expectedNote = 'Xuất dùng từ lô LOT-' . $newerLot->id . ': [Admin: Quản Trị Viên (admin@happytea.com)] Cần đúng loại ly của lô mới cho đơn hàng đặc biệt';
        $this->assertDatabaseHas('material_imports', [
            'material_id' => $material->id, 'quantity' => -3, 'note' => $expectedNote,
        ]);

        // Vượt tồn kho CỦA RIÊNG LÔ NÀY (dù material còn đủ tổng) -> vẫn phải chặn.
        $response = $this->post("/admin/materials/imports/{$newerLot->id}/consume-batch", [
            'quantity' => 5, 'reason' => 'Thử vượt tồn kho của lô',
        ]);
        $response->assertSessionHasErrors('quantity');
        $this->assertEquals(2, (float) $newerLot->fresh()->remaining_quantity);
    }

    /**
     * Trang danh sách Vật tư (`materials.index`) trước đây chỉ được smoke-test (status 200), chưa
     * kiểm tra đúng nội dung: thẻ thống kê (tổng/sắp hết/hết hàng) và bộ lọc theo trạng thái.
     */
    public function test_materials_index_shows_correct_stats_and_filters(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        Material::create(['name' => 'Trân châu đen', 'unit' => 'kg', 'unit_price' => 50000, 'current_stock' => 0, 'is_active' => true]);
        Material::create(['name' => 'Sữa tươi', 'unit' => 'lít', 'unit_price' => 30000, 'current_stock' => 2, 'is_active' => true]);
        Material::create(['name' => 'Đường cát', 'unit' => 'kg', 'unit_price' => 20000, 'current_stock' => 50, 'is_active' => true]);

        $this->actingAs($staff);

        $response = $this->get('/staff/reception/materials');
        $response->assertOk();
        $response->assertSee('Trân châu đen');
        $response->assertSee('Sữa tươi');
        $response->assertSee('Đường cát');

        $response = $this->get('/staff/reception/materials?status=out_of_stock');
        $response->assertOk();
        $response->assertSee('Trân châu đen');
        $response->assertDontSee('Sữa tươi');
        $response->assertDontSee('Đường cát');

        $response = $this->get('/staff/reception/materials?status=low_stock');
        $response->assertOk();
        $response->assertSee('Sữa tươi');
        $response->assertDontSee('Trân châu đen');
        $response->assertDontSee('Đường cát');

        $response = $this->get('/staff/reception/materials?search=Đường');
        $response->assertOk();
        $response->assertSee('Đường cát');
        $response->assertDontSee('Trân châu đen');
    }

    /**
     * AJAX (lọc/phân trang không tải lại trang) chỉ trả về đúng phần bảng, không phải cả trang HTML.
     */
    public function test_materials_index_ajax_returns_partial_not_full_page(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        Material::create(['name' => 'Trân châu trắng', 'unit' => 'kg', 'unit_price' => 45000, 'current_stock' => 10, 'is_active' => true]);

        $response = $this->actingAs($staff)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson('/staff/reception/materials');

        $response->assertOk();
        $response->assertJsonStructure(['html']);
        $this->assertStringContainsString('Trân châu trắng', $response->json('html'));
        $this->assertStringNotContainsString('<html', $response->json('html'));
    }

    /**
     * Trang lịch sử nhập/xuất của 1 vật tư (`materials.imports`) phải hiện đúng các lô đã nhập/xuất
     * của CHÍNH vật tư đó, không lẫn của vật tư khác.
     */
    public function test_materials_imports_history_page_shows_correct_lots(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $material = Material::create(['name' => 'Bột matcha', 'unit' => 'kg', 'unit_price' => 200000, 'current_stock' => 5, 'is_active' => true]);
        $otherMaterial = Material::create(['name' => 'Bột cacao', 'unit' => 'kg', 'unit_price' => 150000, 'current_stock' => 3, 'is_active' => true]);

        MaterialImport::create(['material_id' => $material->id, 'quantity' => 5, 'remaining_quantity' => 5, 'total_price' => 1000000, 'note' => 'Lô nhập matcha đầu tiên']);
        MaterialImport::create(['material_id' => $otherMaterial->id, 'quantity' => 3, 'remaining_quantity' => 3, 'total_price' => 450000, 'note' => 'Lô nhập cacao']);

        $response = $this->actingAs($staff)->get("/staff/reception/materials/{$material->id}/imports");

        $response->assertOk();
        $response->assertSee('Bột matcha');
        $response->assertSee('Lô nhập matcha đầu tiên');
        $response->assertDontSee('Lô nhập cacao');
    }

    /**
     * Nút "Thanh toán chuyển khoản" trên trang chi tiết đơn cho phép lễ tân thử lại thanh toán MoMo
     * cho 1 đơn ĐÃ TỒN TẠI (vd khách chọn MoMo lúc tạo đơn nhưng chưa quét mã xong) - khác với luồng
     * redirect MoMo lúc mới TẠO đơn đã được test riêng.
     */
    public function test_receptionist_can_retry_momo_payment_on_existing_unpaid_order(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            '*' => \Illuminate\Support\Facades\Http::response(['payUrl' => 'https://test-payment.momo.vn/fake-retry-url'], 200),
        ]);
        config(['services.momo.production.partner_code' => 'TEST', 'services.momo.production.access_key' => 'TEST', 'services.momo.production.secret_key' => 'TEST']);

        $staff = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $order = $this->makeOrder([
            'payment_method' => 'momo', 'payment_status' => 'unpaid', 'delivery_type' => 'pickup',
            'delivery_address' => null, 'status' => 'confirmed',
        ]);

        $response = $this->actingAs($staff)->post("/staff/reception/orders/{$order->id}/pay-momo");

        $response->assertRedirect('https://test-payment.momo.vn/fake-retry-url');
        $this->assertSame('unpaid', $order->fresh()->payment_status);
    }

    /**
     * Đơn đã thanh toán rồi thì không được thử thanh toán lại lần nữa.
     */
    public function test_retrying_momo_payment_on_already_paid_order_is_rejected(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $order = $this->makeOrder([
            'payment_method' => 'momo', 'payment_status' => 'paid', 'delivery_type' => 'pickup',
            'delivery_address' => null, 'status' => 'confirmed',
        ]);

        $response = $this->actingAs($staff)
            ->from(route('staff.reception.orders.show', $order->id))
            ->post("/staff/reception/orders/{$order->id}/pay-momo");

        $response->assertRedirect(route('staff.reception.orders.show', $order->id));
        $response->assertSessionHas('error');
    }

    private function makeProduct(array $overrides = []): Product
    {
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Trà sữa', 'slug' => 'tra-sua-' . uniqid(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        return Product::create(array_merge([
            'name' => 'Trà sữa trân châu',
            'slug' => 'tra-sua-tran-chau-' . uniqid(),
            'sku' => 'SKU-' . strtoupper(uniqid()),
            'base_price' => 35000,
            'category_id' => $categoryId,
            'is_active' => true,
        ], $overrides));
    }

    private function makeAddress(int $userId, array $overrides = []): \App\Models\UserAddress
    {
        return \App\Models\UserAddress::create(array_merge([
            'user_id' => $userId,
            'fullname' => 'Khách Test',
            'phone' => '0909' . str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT),
            'province' => 'TP. Hồ Chí Minh',
            'district' => 'Quận 8',
            'ward' => 'Phường 4',
            'specific_address' => '180 Cao Lỗ',
            'type' => 'home',
            'is_default' => true,
        ], $overrides));
    }

    /**
     * Lễ tân tạo đơn tại quầy (pickup) — không cần địa chỉ, không cần tên/SĐT khách (khách uống
     * tại chỗ, không giao hàng); khách vãng lai (không chọn tài khoản nào) -> customer_phone phải
     * là NULL, không được ngầm fallback về SĐT của lễ tân. Từ Giai đoạn 3: tạo đơn tiền mặt KHÔNG
     * còn tự động đánh dấu đã thanh toán ngay — phải xác nhận riêng qua confirmCashPayment().
     */
    public function test_receptionist_can_create_counter_order_without_address(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('14:00:00'));

        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist', 'phone' => '0911222333']);
        $product = $this->makeProduct();

        $this->actingAs($receptionist);

        // Lễ tân thêm sản phẩm vào giỏ hàng của chính mình qua endpoint /cart/add có sẵn
        $this->postJson('/cart/add', ['product_id' => $product->id, 'quantity' => 2])->assertOk();

        $response = $this->post('/staff/reception/orders', [
            'payment_method' => 'cash',
            'note' => 'Ít đá',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $order = Order::where('created_by', $receptionist->id)->latest()->first();
        $this->assertNotNull($order);
        $this->assertEquals('pickup', $order->delivery_type);
        $this->assertNull($order->delivery_address);
        $this->assertEquals('Khách tại quầy', $order->customer_name);
        $this->assertNull($order->customer_phone);
        $this->assertEquals(70000, (float) $order->final_amount);
        $this->assertEquals('cash', $order->payment_method);
        $this->assertEquals('unpaid', $order->payment_status);
        $this->assertNull($order->paid_at);
    }

    /**
     * Giai đoạn 3: lễ tân phải nhập số tiền khách đưa + bấm "Xác nhận đã thu tiền" thì đơn tiền mặt
     * mới thực sự chuyển 'paid' — không được thu ít hơn giá trị đơn (số tiền không đủ bị từ chối).
     */
    public function test_receptionist_confirms_cash_payment_with_amount_tendered_and_change(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('14:00:00'));

        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $product = $this->makeProduct(['base_price' => 35000]);

        $this->actingAs($receptionist);
        $this->postJson('/cart/add', ['product_id' => $product->id, 'quantity' => 2])->assertOk();
        $this->post('/staff/reception/orders', ['payment_method' => 'cash']);
        $order = Order::where('created_by', $receptionist->id)->latest()->first();
        $this->assertEquals('unpaid', $order->payment_status);

        // Đưa thiếu tiền -> từ chối, vẫn unpaid
        $response = $this->post("/staff/reception/orders/{$order->id}/confirm-cash", ['amount_tendered' => 50000]);
        $response->assertSessionHasErrors('amount_tendered');
        $this->assertEquals('unpaid', $order->fresh()->payment_status);

        // Đưa đủ (dư ra để có tiền thừa) -> xác nhận thành công
        $response = $this->post("/staff/reception/orders/{$order->id}/confirm-cash", ['amount_tendered' => 100000]);
        $response->assertSessionHasNoErrors();

        $order = $order->fresh();
        $this->assertEquals('paid', $order->payment_status);
        $this->assertNotNull($order->paid_at);
        $this->assertEquals(100000, (float) $order->amount_tendered);
        // Tiền thừa = 100.000 - 70.000 = 30.000đ (tính khi hiển thị, không lưu riêng)
        $this->assertEquals(30000, (float) $order->amount_tendered - (float) $order->final_amount);
    }

    /**
     * Nút in hóa đơn/phiếu pha chế xuất hiện ngay khi đơn được XÁC NHẬN (status), không còn chờ
     * payment_status=paid ở BƯỚC IN nữa - pha chế cần phiếu để bắt đầu làm đồ. Nhưng với đơn tiền mặt
     * tại quầy, việc xác nhận ĐƠN tự nó lại đòi hỏi phải thu tiền trước (OrderWorkflowService::transition())
     * - khách đứng ngay quầy nên phải đưa tiền xong mới xác nhận, tránh xác nhận rồi mới phát hiện
     * chưa thu tiền. Vậy nên với đơn cash, in vẫn "gián tiếp" chờ thu tiền, chỉ là chờ ở bước xác nhận
     * đơn thay vì ở bước in.
     */
    public function test_print_buttons_appear_once_order_is_confirmed_regardless_of_payment(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('14:00:00'));

        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $product = $this->makeProduct();

        $this->actingAs($receptionist);
        $this->postJson('/cart/add', ['product_id' => $product->id, 'quantity' => 1])->assertOk();
        $this->post('/staff/reception/orders', ['payment_method' => 'cash']);
        $order = Order::where('created_by', $receptionist->id)->latest()->first();

        $response = $this->get("/staff/reception/orders/{$order->id}");
        $response->assertDontSee('print-prep-ticket-btn', false);
        $response->assertDontSee('print-invoice-btn', false);
        $response->assertSee('Xác nhận đơn để in');

        // Đơn tiền mặt CHƯA thu tiền -> không được phép xác nhận đơn (chặn ở transition()).
        $this->patch("/staff/reception/orders/{$order->id}/status", ['status' => 'confirmed'])
            ->assertSessionHasErrors('status');
        $this->assertSame('pending', $order->fresh()->status);

        // Thu tiền mặt xong -> giờ mới xác nhận đơn được, và in xuất hiện ngay (không cần thêm bước nào khác).
        $this->post("/staff/reception/orders/{$order->id}/confirm-cash", ['amount_tendered' => $order->final_amount]);
        $this->patch("/staff/reception/orders/{$order->id}/status", ['status' => 'confirmed'])
            ->assertSessionHasNoErrors();
        $this->assertSame('confirmed', $order->fresh()->status);

        $response = $this->get("/staff/reception/orders/{$order->id}");
        $response->assertSee('print-prep-ticket-btn', false);
        $response->assertSee('print-invoice-btn', false);
    }

    /**
     * Cùng hành vi cho đơn ĐẶT ONLINE (giao hàng, COD) - không riêng gì đơn tại quầy.
     */
    public function test_print_buttons_appear_for_confirmed_online_delivery_order_too(): void
    {
        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $order = $this->makeOrder(['delivery_type' => 'delivery', 'payment_method' => 'cod', 'status' => 'pending']);

        $response = $this->actingAs($receptionist)->get("/staff/reception/orders/{$order->id}");
        $response->assertDontSee('print-prep-ticket-btn', false);
        $response->assertDontSee('print-invoice-btn', false);

        $this->patch("/staff/reception/orders/{$order->id}/status", ['status' => 'confirmed']);
        $this->assertSame('unpaid', $order->fresh()->payment_status);

        $response = $this->get("/staff/reception/orders/{$order->id}");
        $response->assertSee('print-prep-ticket-btn', false);
        $response->assertSee('print-invoice-btn', false);
    }

    /**
     * Đơn tại quầy chọn MoMo -> KHÔNG tự đánh dấu đã thanh toán, mà chuyển hướng sang cổng MoMo
     * để khách quét QR; việc đánh dấu paid thật sự chỉ xảy ra ở webhook/return của MoMo (đã có sẵn).
     */
    public function test_receptionist_counter_order_momo_redirects_to_payment_gateway(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('14:00:00'));
        \Illuminate\Support\Facades\Http::fake([
            '*' => \Illuminate\Support\Facades\Http::response(['payUrl' => 'https://test-payment.momo.vn/fake-pay-url'], 200),
        ]);

        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $product = $this->makeProduct();

        $this->actingAs($receptionist);
        $this->postJson('/cart/add', ['product_id' => $product->id, 'quantity' => 1])->assertOk();

        $response = $this->post('/staff/reception/orders', ['payment_method' => 'momo']);
        $response->assertRedirect('https://test-payment.momo.vn/fake-pay-url');

        $order = Order::where('created_by', $receptionist->id)->latest()->first();
        $this->assertNotNull($order);
        $this->assertEquals('momo', $order->payment_method);
        $this->assertEquals('unpaid', $order->payment_status);
    }

    /**
     * Không chọn phương thức thanh toán (hoặc chọn giá trị lạ) -> bị từ chối, không tạo đơn.
     */
    public function test_receptionist_counter_order_requires_valid_payment_method(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('14:00:00'));

        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $product = $this->makeProduct();

        $this->actingAs($receptionist);
        $this->postJson('/cart/add', ['product_id' => $product->id, 'quantity' => 1])->assertOk();

        $response = $this->post('/staff/reception/orders', ['payment_method' => 'bank_transfer']);
        $response->assertSessionHasErrors('payment_method');
        $this->assertNull(Order::where('created_by', $receptionist->id)->latest()->first());
    }

    /**
     * Lễ tân xem trang Khuyến mãi đang áp dụng (đọc-only).
     */
    public function test_receptionist_can_view_promotions_readonly(): void
    {
        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);

        $this->actingAs($receptionist);

        $this->get('/staff/reception/promotions')->assertStatus(200);
    }

    /**
     * Lễ tân tìm khách hàng theo tên/SĐT khi tạo đơn.
     */
    public function test_receptionist_can_search_customers(): void
    {
        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $customer = User::factory()->create(['role' => 'customer', 'name' => 'Nguyễn Văn Khách', 'phone' => '0912345678']);

        $this->actingAs($receptionist);

        $response = $this->getJson('/staff/reception/customers/search?q=0912345678');
        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Nguyễn Văn Khách']);
    }

    /**
     * Nhân viên (cả 2 loại) chỉ được XEM hồ sơ cá nhân — không có quyền tự sửa (tên/SĐT/mật khẩu),
     * kể cả khi tự gửi PUT trực tiếp tới route (route đã bị gỡ hoàn toàn, không chỉ ẩn UI).
     */
    public function test_staff_can_only_view_profile_not_edit_it(): void
    {
        $receptionist = User::factory()->create([
            'role' => 'staff', 'staff_type' => 'receptionist',
            'name' => 'Tên Gốc', 'password' => bcrypt('OldPassword123'),
        ]);

        $this->actingAs($receptionist);

        $response = $this->get('/staff/reception/profile');
        $response->assertStatus(200);
        $response->assertSee('Tên Gốc');
        $response->assertSee('chỉ có thể xem thông tin tài khoản', false);

        // Route sửa hồ sơ đã bị gỡ bỏ hoàn toàn -> PUT trả 405 (URI chỉ còn đăng ký GET), không còn
        // cách nào tự sửa được nữa dù cố gửi thẳng request.
        $response = $this->put('/staff/reception/profile', [
            'name' => 'Tên Bị Đổi Trái Phép',
            'current_password' => 'OldPassword123',
            'new_password' => 'NewPassword123',
            'new_password_confirmation' => 'NewPassword123',
        ]);
        $response->assertStatus(405);

        $receptionist = $receptionist->fresh();
        $this->assertEquals('Tên Gốc', $receptionist->name);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('OldPassword123', $receptionist->password));
    }

    /**
     * Nhân viên vận chuyển cũng dùng chung được trang Hồ sơ cá nhân (route riêng của khu vực delivery).
     */
    public function test_delivery_staff_can_access_own_profile(): void
    {
        $delivery = User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery']);
        $this->actingAs($delivery);

        $this->get('/staff/delivery/profile')->assertStatus(200);
        // Cũng không có quyền tự sửa — route đã bị gỡ bỏ (URI chỉ còn đăng ký GET -> 405).
        $this->put('/staff/delivery/profile', ['name' => 'Hack'])->assertStatus(405);
    }

    /**
     * Dashboard vận chuyển hiển thị đúng đối soát COD: tiền cần thu (đơn đang giao)
     * tách biệt với tiền đã thu (đơn đã hoàn thành) — chỉ tính đơn của chính nhân viên.
     */
    public function test_delivery_dashboard_shows_cod_reconciliation_totals(): void
    {
        $delivery = User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery']);
        $otherDelivery = User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery']);

        $this->makeOrder([
            'delivery_staff_id' => $delivery->id, 'status' => 'shipping',
            'payment_method' => 'cod', 'final_amount' => 40000,
        ]);
        $this->makeOrder([
            'delivery_staff_id' => $delivery->id, 'status' => 'completed',
            'payment_method' => 'cod', 'final_amount' => 60000,
        ]);
        // Đơn của người khác không được tính vào tổng của $delivery
        $this->makeOrder([
            'delivery_staff_id' => $otherDelivery->id, 'status' => 'completed',
            'payment_method' => 'cod', 'final_amount' => 99000,
        ]);

        $this->actingAs($delivery);
        $response = $this->get('/staff/delivery/dashboard');

        $response->assertStatus(200);
        $response->assertViewHas('codToCollect', 40000.0);
        $response->assertViewHas('codCollectedTotal', 60000.0);
    }

    /**
     * Admin lọc danh sách nhân viên theo "Loại nhân viên" (Lễ tân/Vận chuyển).
     */
    public function test_admin_can_filter_staff_accounts_by_staff_type(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist', 'name' => 'Lễ Tân A']);
        $delivery = User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery', 'name' => 'Vận Chuyển B']);

        $this->actingAs($admin);

        $response = $this->get('/admin/staff-accounts?staff_type=receptionist');
        $response->assertStatus(200);
        $response->assertSee('Lễ Tân A');
        $response->assertDontSee('Vận Chuyển B');

        $response = $this->get('/admin/staff-accounts?staff_type=delivery');
        $response->assertStatus(200);
        $response->assertSee('Vận Chuyển B');
        $response->assertDontSee('Lễ Tân A');
    }

    /**
     * Admin đổi loại nhân viên (lễ tân <-> vận chuyển); giá trị lạ bị từ chối;
     * chỉ tác động tài khoản role=staff, không đụng customer/admin dù trùng ID.
     */
    public function test_admin_can_update_staff_type_with_whitelist_and_scope(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $this->actingAs($admin);

        // Giá trị lạ -> từ chối
        $response = $this->patchJson("/admin/staff-accounts/{$receptionist->id}/staff-type", ['staff_type' => 'manager']);
        $response->assertStatus(422);
        $this->assertEquals('receptionist', $receptionist->fresh()->staff_type);

        // Giá trị hợp lệ -> đổi thành công
        $response = $this->patchJson("/admin/staff-accounts/{$receptionist->id}/staff-type", ['staff_type' => 'delivery']);
        $response->assertOk();
        $response->assertJson(['success' => true, 'staff_type' => 'delivery']);
        $this->assertEquals('delivery', $receptionist->fresh()->staff_type);

        // Không đụng tài khoản customer/admin dù ID trùng khớp về mặt số học với 1 staff khác
        $customer = User::factory()->create(['role' => 'customer']);
        $response = $this->patchJson("/admin/staff-accounts/{$customer->id}/staff-type", ['staff_type' => 'delivery']);
        $response->assertStatus(404);
        $this->assertNull($customer->fresh()->staff_type);
    }

    /**
     * Dashboard lễ tân tính đúng doanh thu hôm nay theo hình thức thanh toán: tiền mặt gộp cả
     * 'cash' và 'cod', chuyển khoản là 'momo'; chỉ tính đơn đã thanh toán (paid_at) hôm nay,
     * bỏ qua đơn chưa thanh toán và đơn đã thanh toán từ hôm qua.
     */
    public function test_reception_dashboard_computes_todays_revenue_by_payment_method(): void
    {
        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);

        // Tiền mặt hôm nay: 'cash' + 'cod' đã thanh toán
        $this->makeOrder(['payment_method' => 'cash', 'payment_status' => 'paid', 'paid_at' => now(), 'final_amount' => 40000]);
        $this->makeOrder(['payment_method' => 'cod', 'payment_status' => 'paid', 'paid_at' => now(), 'final_amount' => 20000]);
        // Chuyển khoản hôm nay: 'momo' đã thanh toán
        $this->makeOrder(['payment_method' => 'momo', 'payment_status' => 'paid', 'paid_at' => now(), 'final_amount' => 60000]);
        // Không tính: momo chưa thanh toán
        $this->makeOrder(['payment_method' => 'momo', 'payment_status' => 'unpaid', 'paid_at' => null, 'final_amount' => 99000]);
        // Không tính: đã thanh toán nhưng từ hôm qua
        $this->makeOrder(['payment_method' => 'cash', 'payment_status' => 'paid', 'paid_at' => now()->subDay(), 'final_amount' => 77000]);

        $this->actingAs($receptionist);
        $response = $this->get('/staff/reception/dashboard');

        $response->assertStatus(200);
        $response->assertViewHas('cashRevenueToday', 60000.0);
        $response->assertViewHas('transferRevenueToday', 60000.0);
        $response->assertViewHas('totalRevenueToday', 120000.0);
        $response->assertViewHas('cashRevenuePercent', 50);
        $response->assertViewHas('transferRevenuePercent', 50);
    }

    /**
     * Đơn GIAO HÀNG (không phải tại quầy): lễ tân chỉ được xác nhận/hủy — không được tự ý nhảy
     * thẳng sang "đang giao"/"hoàn thành" (bước đó chỉ nhân viên vận chuyển được làm sau khi
     * được phân công). Đây là fix cho lỗi lễ tân bấm tắt qua bước phân công khiến đơn không bao
     * giờ có delivery_staff_id, làm tài khoản vận chuyển không thấy đơn nào.
     */
    public function test_receptionist_cannot_skip_delivery_assignment_for_delivery_orders(): void
    {
        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $order = $this->makeOrder(['delivery_type' => 'delivery', 'status' => 'confirmed']);

        $this->actingAs($receptionist);

        $response = $this->patch("/staff/reception/orders/{$order->id}/status", ['status' => 'shipping']);
        $response->assertSessionHasErrors('status');
        $this->assertEquals('confirmed', $order->fresh()->status);

        $response = $this->patch("/staff/reception/orders/{$order->id}/status", ['status' => 'completed']);
        $response->assertSessionHasErrors('status');
        $this->assertEquals('confirmed', $order->fresh()->status);

        // Vẫn hủy được vì hủy không cần shipper
        $response = $this->patch("/staff/reception/orders/{$order->id}/status", ['status' => 'cancelled', 'cancel_reason' => 'Khách đổi ý']);
        $response->assertSessionHasNoErrors();
        $this->assertEquals('cancelled', $order->fresh()->status);
    }

    /**
     * Đơn TẠI QUẦY (pickup): không có shipper, không có bước "đang giao" — xác nhận xong là
     * chuyển thẳng sang hoàn thành luôn (bỏ qua "shipping" theo PICKUP_TRANSITIONS).
     */
    public function test_receptionist_can_fully_complete_pickup_order_without_delivery_staff(): void
    {
        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $order = $this->makeOrder(['delivery_type' => 'pickup', 'delivery_address' => null, 'status' => 'confirmed']);

        $this->actingAs($receptionist);

        $response = $this->patch("/staff/reception/orders/{$order->id}/status", ['status' => 'completed']);
        $response->assertSessionHasNoErrors();
        $this->assertEquals('completed', $order->fresh()->status);
    }

    /**
     * Trang chi tiết đơn giao hàng đã xác nhận, chưa phân công: hiển thị danh sách nhân viên
     * vận chuyển đang hoạt động để lễ tân chọn — đây là UI còn thiếu trước đây khiến lễ tân
     * không có cách nào thật sự phân công (route/controller đã có nhưng chưa có giao diện).
     */
    public function test_order_show_page_offers_available_delivery_staff_for_unassigned_confirmed_order(): void
    {
        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $activeDelivery = User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery', 'is_active' => true, 'name' => 'Shipper Hoạt Động']);
        User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery', 'is_active' => false, 'name' => 'Shipper Đã Khóa']);
        $order = $this->makeOrder(['delivery_type' => 'delivery', 'status' => 'confirmed']);

        $this->actingAs($receptionist);
        $response = $this->get("/staff/reception/orders/{$order->id}");

        $response->assertStatus(200);
        $response->assertSee('Shipper Hoạt Động');
        $response->assertDontSee('Shipper Đã Khóa');
    }

    /**
     * Luồng đầy đủ: lễ tân xác nhận đơn giao hàng, phân công nhân viên vận chuyển qua đúng
     * endpoint (không tắt qua bước status) -> nhân viên vận chuyển đó thấy ngay đơn trong danh
     * sách "Đơn được giao" của họ. Tái hiện đúng bug người dùng báo: "đăng nhập tài khoản giao
     * hàng không thấy đơn nào" — nguyên nhân là chưa từng phân công qua endpoint đúng.
     */
    public function test_full_reception_to_delivery_handoff_makes_order_visible_to_assigned_shipper(): void
    {
        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $delivery = User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery', 'is_active' => true]);
        $order = $this->makeOrder(['delivery_type' => 'delivery', 'status' => 'confirmed']);

        $this->actingAs($receptionist);
        $this->post("/staff/reception/orders/{$order->id}/assign-delivery", ['delivery_staff_id' => $delivery->id])
            ->assertSessionHasNoErrors();

        $this->actingAs($delivery);
        $response = $this->get('/staff/delivery/orders?tab=assigned');
        $response->assertStatus(200);
        $response->assertSee($order->order_code);
    }

    /**
     * Dashboard lễ tân đếm đúng số đơn giao hàng đã xác nhận nhưng CHƯA có shipper — số này phải
     * nổi bật để lễ tân không bỏ sót đơn "kẹt" ở bước xác nhận không ai xử lý tiếp (nguyên nhân
     * gốc của bug "nhân viên giao hàng không thấy đơn nào").
     */
    public function test_reception_dashboard_counts_orders_needing_delivery_assignment(): void
    {
        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $delivery = User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery']);

        // Cần đếm: đơn giao hàng, đã xác nhận, chưa có shipper
        $this->makeOrder(['delivery_type' => 'delivery', 'status' => 'confirmed']);
        $this->makeOrder(['delivery_type' => 'delivery', 'status' => 'confirmed']);
        // Không đếm: đã có shipper
        $this->makeOrder(['delivery_type' => 'delivery', 'status' => 'confirmed', 'delivery_staff_id' => $delivery->id, 'assigned_at' => now()]);
        // Không đếm: chưa xác nhận
        $this->makeOrder(['delivery_type' => 'delivery', 'status' => 'pending']);
        // Không đếm: đơn tại quầy (không cần shipper)
        $this->makeOrder(['delivery_type' => 'pickup', 'status' => 'confirmed', 'delivery_address' => null]);

        $this->actingAs($receptionist);
        $response = $this->get('/staff/reception/dashboard');

        $response->assertStatus(200);
        $response->assertViewHas('needsAssignmentCount', 2);
        $response->assertSee('Chờ phân công');
    }

    /**
     * Đơn giao hàng (không phải tại quầy) tạo qua OrderService::create() phải snapshot tọa độ GPS
     * từ địa chỉ khách đã chọn — để nhân viên vận chuyển mở đúng điểm trên bản đồ thay vì tìm theo
     * chuỗi địa chỉ text (dễ ra nhiều kết quả trùng tên đường/khu vực).
     */
    public function test_delivery_order_snapshots_gps_coordinates_from_address(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('14:00:00'));

        $customer = User::factory()->create(['role' => 'customer']);
        $product = $this->makeProduct();

        $address = \App\Models\UserAddress::create([
            'user_id' => $customer->id,
            'fullname' => 'Nguyễn Văn A',
            'phone' => '0911222333',
            'province' => 'Thành phố Hồ Chí Minh',
            'district' => 'Quận 8',
            'ward' => 'Phường Chánh Hưng',
            'specific_address' => '218 Cao Lỗ',
            'type' => 'home',
            'is_default' => true,
            'latitude' => 10.7368782,
            'longitude' => 106.6801247,
        ]);

        $cart = \App\Models\Cart::create(['user_id' => $customer->id]);
        \App\Models\CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => $product->base_price,
        ]);

        $order = app(\App\Services\OrderService::class)->create($customer, [
            'idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
            'address_id' => $address->id,
        ], 'cod');

        $this->assertEquals('delivery', $order->delivery_type);
        $this->assertEqualsWithDelta(10.7368782, (float) $order->delivery_latitude, 0.0001);
        $this->assertEqualsWithDelta(106.6801247, (float) $order->delivery_longitude, 0.0001);
    }

    /**
     * Đơn tại quầy (pickup) không có địa chỉ nên không snapshot tọa độ — cột phải là NULL, không lỗi.
     */
    public function test_pickup_order_has_no_gps_coordinates(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('14:00:00'));

        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $product = $this->makeProduct();

        $this->actingAs($receptionist);
        $this->postJson('/cart/add', ['product_id' => $product->id, 'quantity' => 1])->assertOk();
        $this->post('/staff/reception/orders', ['payment_method' => 'cash']);

        $order = Order::where('created_by', $receptionist->id)->latest()->first();
        $this->assertNull($order->delivery_latitude);
        $this->assertNull($order->delivery_longitude);
    }

    /**
     * Đơn giao hàng đã "đang giao": lễ tân KHÔNG được hủy nữa (kể cả khi chưa thanh toán) — mọi thay
     * đổi từ đây (hoàn thành/hủy/giao thất bại) đều thuộc về nhân viên vận chuyển. Đây là siết chặt
     * bổ sung so với rule trước đó (trước chỉ chặn shipping/completed, chưa chặn cancelled).
     */
    public function test_receptionist_cannot_cancel_delivery_order_once_shipping(): void
    {
        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $order = $this->makeOrder(['delivery_type' => 'delivery', 'status' => 'shipping', 'payment_status' => 'unpaid']);

        $this->actingAs($receptionist);

        $response = $this->patch("/staff/reception/orders/{$order->id}/status", [
            'status' => 'cancelled',
            'cancel_reason' => 'Thử hủy trái phép',
        ]);
        $response->assertSessionHasErrors('status');
        $this->assertEquals('shipping', $order->fresh()->status);
    }

    /**
     * Danh sách đơn hàng lễ tân giờ chỉ HIỂN THỊ trạng thái (badge tĩnh), không còn dropdown chỉnh
     * được tại đây nữa — mọi thay đổi trạng thái phải vào trang chi tiết đơn.
     */
    public function test_reception_order_list_shows_readonly_status_badge_not_editable_dropdown(): void
    {
        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $this->makeOrder(['delivery_type' => 'delivery', 'status' => 'confirmed']);

        $this->actingAs($receptionist);
        $response = $this->get('/staff/reception/orders');

        $response->assertStatus(200);
        // 'order-status-select' là class riêng của dropdown chỉnh trạng thái theo dòng đã bị gỡ bỏ —
        // không dùng assertDontSee('name="status"') vì thanh bộ lọc phía trên vẫn có select lọc theo
        // trạng thái hợp lệ (id="status-select"), không liên quan tới việc chỉnh trạng thái từng đơn.
        $response->assertDontSee('order-status-select', false);
    }

    /**
     * Trang chi tiết đơn hiện đủ các nút hành động đổi trạng thái phù hợp theo từng trạng thái:
     * pending -> "Xác nhận đơn"; pickup confirmed -> "Hoàn thành" thẳng (không có bước "đang giao"
     * — khách nhận trực tiếp tại quầy). Đơn giao hàng (không pickup) đã "đang giao" thì KHÔNG có
     * nút nào (chỉ ghi chú, chỉ nhân viên vận chuyển được cập nhật tiếp).
     */
    public function test_order_show_page_offers_correct_status_actions_per_state(): void
    {
        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $this->actingAs($receptionist);

        $pendingOrder = $this->makeOrder(['status' => 'pending']);
        $this->get("/staff/reception/orders/{$pendingOrder->id}")->assertSee('Xác nhận đơn');

        $pickupConfirmed = $this->makeOrder(['delivery_type' => 'pickup', 'delivery_address' => null, 'status' => 'confirmed']);
        $this->get("/staff/reception/orders/{$pickupConfirmed->id}")->assertSee('Hoàn thành');

        // Đơn pickup cũ lỡ đang ở 'shipping' (trước khi đổi luồng) vẫn hoàn thành được — tương thích ngược.
        $pickupShipping = $this->makeOrder(['delivery_type' => 'pickup', 'delivery_address' => null, 'status' => 'shipping']);
        $this->get("/staff/reception/orders/{$pickupShipping->id}")->assertSee('Hoàn thành');

        $deliveryShipping = $this->makeOrder(['delivery_type' => 'delivery', 'status' => 'shipping']);
        $response = $this->get("/staff/reception/orders/{$deliveryShipping->id}");
        $response->assertDontSee('name="status" value="cancelled"', false);
        $response->assertSee('chỉ nhân viên vận chuyển được cập nhật tiếp');
    }

    /**
     * Đơn tại quầy tự động áp dụng khuyến mãi "apply_for=all" đang hoạt động và đủ điều kiện
     * (đơn tối thiểu) mà không cần lễ tân nhập mã — vì giao diện POS không có ô nhập mã.
     */
    /**
     * POS Giai đoạn 1: chọn size/topping/đường/đá khi thêm sản phẩm vào giỏ (không chỉ giá gốc số
     * lượng 1 như trước) — đơn tại quầy phải lưu đúng biến thể và tính đúng giá (base + size +
     * topping) x số lượng.
     */
    public function test_pos_order_saves_size_topping_sugar_ice_and_computes_correct_price(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('14:00:00'));

        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $product = $this->makeProduct(['base_price' => 30000]);

        \App\Models\ProductSize::create(['product_id' => $product->id, 'size_name' => 'L', 'price_adjustment' => 5000]);
        $topping = \App\Models\Topping::create(['name' => 'Trân châu đen', 'price' => 5000, 'is_available' => true]);
        DB::table('product_toppings')->insert(['product_id' => $product->id, 'topping_id' => $topping->id]);

        $this->actingAs($receptionist);
        $this->postJson('/cart/add', [
            'product_id' => $product->id,
            'quantity' => 2,
            'size_name' => 'L',
            'sugar_level' => '70',
            'ice_level' => 'less',
            'toppings' => [$topping->id],
        ])->assertOk();

        $this->post('/staff/reception/orders', ['payment_method' => 'cash']);

        $order = Order::where('created_by', $receptionist->id)->latest()->first();
        $item = $order->items()->first();

        $this->assertEquals('L', $item->size_name);
        $this->assertEquals('70', $item->sugar_level);
        $this->assertEquals('less', $item->ice_level);
        $this->assertContains('Trân châu đen', $item->options);
        $this->assertEquals(2, $item->quantity);
        // unit_price = base_price(30000) + size_adjustment(5000) + topping(5000) = 40000
        $this->assertEquals(40000, (float) $item->unit_price);
        $this->assertEquals(80000, (float) $order->total_amount);
    }

    /**
     * Loại đơn Tại quầy/Mang đi (pickup_mode): mặc định 'dine_in' nếu không gửi, lưu đúng 'takeaway'
     * khi lễ tân chọn — không ảnh hưởng đơn giao hàng (pickup_mode luôn null cho delivery_type=delivery).
     */
    public function test_pos_order_saves_pickup_mode_default_and_selected(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('14:00:00'));

        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $product = $this->makeProduct(['base_price' => 20000]);

        $this->actingAs($receptionist);

        $this->postJson('/cart/add', ['product_id' => $product->id, 'quantity' => 1])->assertOk();
        $this->post('/staff/reception/orders', ['payment_method' => 'cash']);
        // Dùng orderByDesc('id') thay vì latest() (created_at) vì thời gian đã bị đóng băng qua
        // travelTo() — 2 đơn tạo liên tiếp có cùng created_at, sắp theo created_at không đảm bảo lấy
        // đúng đơn vừa tạo sau cùng.
        $firstOrder = Order::where('created_by', $receptionist->id)->orderByDesc('id')->first();
        $this->assertEquals('dine_in', $firstOrder->pickup_mode);

        $this->postJson('/cart/add', ['product_id' => $product->id, 'quantity' => 1])->assertOk();
        $this->post('/staff/reception/orders', ['payment_method' => 'cash', 'pickup_mode' => 'takeaway']);
        $secondOrder = Order::where('created_by', $receptionist->id)->orderByDesc('id')->first();
        $this->assertEquals('takeaway', $secondOrder->pickup_mode);
    }

    /**
     * Trang "Tạo đơn tại quầy" phải cung cấp bộ lọc danh mục + dữ liệu size/topping của từng sản
     * phẩm (để modal chọn biến thể ở frontend hoạt động) — không phải chỉ tên/giá như trước.
     */
    public function test_pos_create_page_provides_category_filter_and_product_variant_data(): void
    {
        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $product = $this->makeProduct(['base_price' => 25000]);
        \App\Models\ProductSize::create(['product_id' => $product->id, 'size_name' => 'M', 'price_adjustment' => 0]);
        $topping = \App\Models\Topping::create(['name' => 'Thạch dừa', 'price' => 4000, 'is_available' => true]);
        DB::table('product_toppings')->insert(['product_id' => $product->id, 'topping_id' => $topping->id]);

        $this->actingAs($receptionist);
        $response = $this->get('/staff/reception/orders/create');

        $response->assertStatus(200);
        $response->assertSee('pos-category-chip', false);
        $response->assertSee($product->category->name);
        // Tên topping được nhúng dạng JSON trong data-attribute (escape unicode \uXXXX), không phải
        // text thuần trên trang — kiểm tra cấu trúc "toppings":[...] có mặt để xác nhận đã nạp dữ liệu.
        $response->assertSee('"toppings":[{"id":' . $topping->id, false);
    }

    /**
     * Sản phẩm hết hàng (is_active=false) vẫn phải xuất hiện trong lưới "Tạo đơn tại quầy" - đưa
     * xuống CUỐI danh sách, kèm nút "Hết hàng" bị khoá - để lễ tân biết mà báo khách, thay vì món
     * biến mất hoàn toàn khỏi màn hình như chưa từng tồn tại.
     */
    public function test_pos_create_page_shows_out_of_stock_products_at_the_end(): void
    {
        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $outOfStock = $this->makeProduct(['name' => 'Trà đào hết hàng', 'is_active' => false]);
        $active = $this->makeProduct(['name' => 'Trà sữa còn hàng', 'is_active' => true]);

        $this->actingAs($receptionist);
        $response = $this->get('/staff/reception/orders/create');

        $response->assertStatus(200);
        $response->assertSee('Trà đào hết hàng');
        $response->assertSee('Hết hàng');

        $content = $response->getContent();
        $this->assertLessThan(strpos($content, 'Trà đào hết hàng'), strpos($content, 'Trà sữa còn hàng'));
    }

    /**
     * POS Giai đoạn 2: lễ tân chọn 1 khách hàng có tài khoản qua ô tìm SĐT/tên -> đơn phải đứng tên
     * (user_id) đúng khách đó, KHÔNG phải tài khoản lễ tân — trong khi created_by vẫn ghi đúng lễ
     * tân đã xử lý đơn (để phân biệt "ai bán" khỏi "đơn của ai").
     */
    public function test_pos_order_attaches_to_selected_customer_not_receptionist(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('14:00:00'));

        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $customer = User::factory()->create(['role' => 'customer', 'name' => 'Nguyễn Văn A', 'phone' => '0911111111']);
        $product = $this->makeProduct(['base_price' => 20000]);

        $this->actingAs($receptionist);
        $this->postJson('/cart/add', ['product_id' => $product->id, 'quantity' => 1])->assertOk();
        $this->post('/staff/reception/orders', ['payment_method' => 'cash', 'customer_id' => $customer->id]);

        $order = Order::where('created_by', $receptionist->id)->orderByDesc('id')->first();
        $this->assertNotNull($order);
        $this->assertEquals($customer->id, $order->user_id);
        $this->assertEquals($receptionist->id, $order->created_by);
        $this->assertEquals('Nguyễn Văn A', $order->customer_name);
        $this->assertEquals('0911111111', $order->customer_phone);
    }

    /**
     * Không chọn khách nào (khách vãng lai) -> user_id phải là NULL, KHÔNG được ngầm định gán cho
     * tài khoản lễ tân đang thao tác như hành vi cũ trước Giai đoạn 2.
     */
    public function test_pos_order_defaults_to_walk_in_customer_when_none_selected(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('14:00:00'));

        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $product = $this->makeProduct(['base_price' => 20000]);

        $this->actingAs($receptionist);
        $this->postJson('/cart/add', ['product_id' => $product->id, 'quantity' => 1])->assertOk();
        $this->post('/staff/reception/orders', ['payment_method' => 'cash']);

        $order = Order::where('created_by', $receptionist->id)->orderByDesc('id')->first();
        $this->assertNotNull($order);
        $this->assertNull($order->user_id);
        $this->assertEquals($receptionist->id, $order->created_by);
        $this->assertEquals('Khách tại quầy', $order->customer_name);
    }

    /**
     * Giảm giá theo hạng thành viên phải tính trên hạng của KHÁCH ĐƯỢC CHỌN, không phải hạng của
     * tài khoản lễ tân (luôn là 'new' vì lễ tân không tích điểm mua hàng như khách thật).
     */
    public function test_pos_order_membership_discount_uses_selected_customers_tier(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('14:00:00'));

        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $goldCustomer = User::factory()->create(['role' => 'customer', 'membership_level' => 'gold']);
        $product = $this->makeProduct(['base_price' => 100000]);

        $this->actingAs($receptionist);
        $this->postJson('/cart/add', ['product_id' => $product->id, 'quantity' => 1])->assertOk();
        $this->post('/staff/reception/orders', ['payment_method' => 'cash', 'customer_id' => $goldCustomer->id]);

        $order = Order::where('created_by', $receptionist->id)->orderByDesc('id')->first();
        // Hạng gold = giảm 5% trên subtotal 100.000đ = 5.000đ
        $this->assertEquals(5000, (float) $order->discount_amount);
        $this->assertEquals(95000, (float) $order->final_amount);
    }

    /**
     * Dùng điểm tích lũy của khách đã chọn: điểm phải bị trừ đúng trên tài khoản KHÁCH đó, không
     * phải tài khoản lễ tân — và discount_amount phải phản ánh đúng giá trị điểm quy đổi.
     */
    public function test_pos_order_can_redeem_selected_customers_points(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('14:00:00'));

        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $customer = User::factory()->create(['role' => 'customer', 'points' => 50]);
        $product = $this->makeProduct(['base_price' => 100000]);

        $this->actingAs($receptionist);
        $this->postJson('/cart/add', ['product_id' => $product->id, 'quantity' => 1])->assertOk();
        $response = $this->post('/staff/reception/orders', [
            'payment_method' => 'cash', 'customer_id' => $customer->id, 'points_to_redeem' => 20,
        ]);
        $response->assertSessionHasNoErrors();

        $order = Order::where('created_by', $receptionist->id)->orderByDesc('id')->first();
        // loyalty_point_value mặc định 1đ/điểm -> 20 điểm giảm đúng 20đ
        $this->assertEquals(20, (float) $order->discount_amount);
        $this->assertEquals(99980, (float) $order->final_amount);
        $this->assertEquals(30, $customer->fresh()->points);
        $this->assertEquals(0, $receptionist->fresh()->points); // Điểm của lễ tân (mặc định 0) không bị đụng tới
    }

    /**
     * Không thể dùng điểm cho khách vãng lai (không có tài khoản để trừ điểm) — phải bị từ chối rõ
     * ràng, không được âm thầm bỏ qua hay trừ nhầm điểm của ai khác.
     */
    public function test_pos_order_rejects_points_redemption_for_walk_in_customer(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('14:00:00'));

        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $product = $this->makeProduct(['base_price' => 20000]);

        $this->actingAs($receptionist);
        $this->postJson('/cart/add', ['product_id' => $product->id, 'quantity' => 1])->assertOk();
        $response = $this->post('/staff/reception/orders', ['payment_method' => 'cash', 'points_to_redeem' => 20]);

        $response->assertSessionHasErrors('points_to_redeem');
        $this->assertNull(Order::where('created_by', $receptionist->id)->first());
    }

    /**
     * previewTotal() (dùng để hiển thị tổng tiền TRƯỚC khi tạo đơn ở giao diện POS) phải tính và trả
     * về đúng số tiền giảm từ điểm tích lũy khi lễ tân đã chọn khách hàng và nhập số điểm hợp lệ —
     * trước đây field này hoàn toàn không tồn tại trong phản hồi JSON, khiến giao diện tổng tiền
     * không bao giờ hiện được số tiền giảm từ điểm dù nhập số điểm hợp lệ.
     */
    public function test_pos_preview_total_includes_points_discount(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('14:00:00'));

        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $customer = User::factory()->create(['role' => 'customer', 'points' => 50]);
        $product = $this->makeProduct(['base_price' => 100000]);

        $this->actingAs($receptionist);
        $this->postJson('/cart/add', ['product_id' => $product->id, 'quantity' => 1])->assertOk();

        $response = $this->getJson("/staff/reception/orders/preview-total?customer_id={$customer->id}&points_to_redeem=20");

        $response->assertOk();
        $response->assertJson([
            'subtotal' => 100000,
            'points_discount' => 20,
            'points_error' => null,
            'final_amount' => 99980,
        ]);
    }

    /**
     * Khi số điểm nhập không hợp lệ (vượt số dư), previewTotal() phải trả về points_error mô tả rõ
     * lý do (giống hệt thông báo lúc tạo đơn thật) — để lễ tân biết ngay khi đang gõ, không cần chờ
     * tới lúc bấm "Tạo đơn" mới phát hiện ra.
     */
    public function test_pos_preview_total_reports_points_error_when_over_balance(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('14:00:00'));

        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $customer = User::factory()->create(['role' => 'customer', 'points' => 10]);
        $product = $this->makeProduct(['base_price' => 100000]);

        $this->actingAs($receptionist);
        $this->postJson('/cart/add', ['product_id' => $product->id, 'quantity' => 1])->assertOk();

        $response = $this->getJson("/staff/reception/orders/preview-total?customer_id={$customer->id}&points_to_redeem=20");

        $response->assertOk();
        $response->assertJsonPath('points_discount', 0);
        $response->assertJsonPath('points_error', 'Số điểm quy đổi vượt quá số dư hiện có.');
    }

    /**
     * Nhập số điểm dưới mức tối thiểu (mặc định 10) phải báo lỗi rõ ràng ngay ở bước xem trước.
     */
    public function test_pos_preview_total_reports_points_error_when_below_minimum(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('14:00:00'));

        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $customer = User::factory()->create(['role' => 'customer', 'points' => 50]);
        $product = $this->makeProduct(['base_price' => 100000]);

        $this->actingAs($receptionist);
        $this->postJson('/cart/add', ['product_id' => $product->id, 'quantity' => 1])->assertOk();

        $response = $this->getJson("/staff/reception/orders/preview-total?customer_id={$customer->id}&points_to_redeem=5");

        $response->assertOk();
        $response->assertJsonPath('points_discount', 0);
        $response->assertJsonPath('points_error', 'Số điểm tối thiểu để được quy đổi là 10.');
    }

    /**
     * Khi chương trình tích điểm bị quản trị viên tạm tắt (loyalty_enabled=0), preview phải báo lỗi
     * thay vì âm thầm tính discount = 0 không rõ lý do.
     */
    public function test_pos_preview_total_reports_points_error_when_loyalty_disabled(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('14:00:00'));
        \App\Models\Setting::setValue('loyalty_enabled', '0');

        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $customer = User::factory()->create(['role' => 'customer', 'points' => 50]);
        $product = $this->makeProduct(['base_price' => 100000]);

        $this->actingAs($receptionist);
        $this->postJson('/cart/add', ['product_id' => $product->id, 'quantity' => 1])->assertOk();

        $response = $this->getJson("/staff/reception/orders/preview-total?customer_id={$customer->id}&points_to_redeem=20");

        $response->assertOk();
        $response->assertJsonPath('points_discount', 0);
        $response->assertJsonPath('points_error', 'Chương trình tích điểm hiện đang tạm đóng.');

        \App\Models\Setting::setValue('loyalty_enabled', '1');
    }

    /**
     * Số điểm quy đổi vượt trần % (loyalty_max_redeem_percent) giá trị đơn phải bị từ chối kèm lý do,
     * dù khách vẫn còn đủ số dư điểm.
     */
    public function test_pos_preview_total_reports_points_error_when_over_max_redeem_percent(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('14:00:00'));
        \App\Models\Setting::setValue('loyalty_max_redeem_percent', 10);

        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        // Subtotal 100.000đ, trần 10% = 10.000đ tối đa được giảm -> 50 điểm (50đ theo point_value=1) vẫn OK,
        // nhưng 20.000 điểm (giá trị 20.000đ) sẽ vượt trần.
        $customer = User::factory()->create(['role' => 'customer', 'points' => 20000]);
        $product = $this->makeProduct(['base_price' => 100000]);

        $this->actingAs($receptionist);
        $this->postJson('/cart/add', ['product_id' => $product->id, 'quantity' => 1])->assertOk();

        $response = $this->getJson("/staff/reception/orders/preview-total?customer_id={$customer->id}&points_to_redeem=20000");

        $response->assertOk();
        $response->assertJsonPath('points_discount', 0);
        $response->assertJsonPath('points_error', 'Số điểm quy đổi vượt quá giới hạn tối đa (10%) giá trị đơn hàng.');

        \App\Models\Setting::setValue('loyalty_max_redeem_percent', 100);
    }

    /**
     * Preview phải khớp với đơn thật khi kết hợp CẢ mã khuyến mãi lẫn điểm tích lũy cùng lúc (2 loại
     * giảm giá cộng dồn) — tránh lệch giữa số hiển thị trước khi tạo đơn và số tiền thật sau khi tạo.
     */
    public function test_pos_preview_total_combines_coupon_and_points_discount(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('14:00:00'));

        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $customer = User::factory()->create(['role' => 'customer', 'points' => 50]);
        $product = $this->makeProduct(['base_price' => 100000]);

        \App\Models\Promotion::create([
            'code' => 'POSPTS10', 'type' => 'percent', 'value' => 10,
            'scope' => 'order', 'apply_for' => 'all', 'applies_to' => 'all', 'is_active' => true,
            'is_recurring' => false,
        ]);

        $this->actingAs($receptionist);
        $this->postJson('/cart/add', ['product_id' => $product->id, 'quantity' => 1])->assertOk();

        $response = $this->getJson(
            "/staff/reception/orders/preview-total?customer_id={$customer->id}&points_to_redeem=20&coupon_code=POSPTS10"
        );

        $response->assertOk();
        // Subtotal 100.000đ - 10% coupon (10.000đ) - 20đ điểm = 89.980đ
        $this->assertEquals(10000, (float) $response->json('discount'));
        $this->assertEquals(20, (float) $response->json('points_discount'));
        $this->assertEquals(89980, (float) $response->json('final_amount'));

        // Tạo đơn thật với cùng tham số phải khớp CHÍNH XÁC số preview vừa hiển thị ở trên.
        $this->post('/staff/reception/orders', [
            'payment_method' => 'cash', 'customer_id' => $customer->id,
            'points_to_redeem' => 20, 'coupon_code' => 'POSPTS10',
        ])->assertSessionHasNoErrors();

        $order = Order::where('created_by', $receptionist->id)->orderByDesc('id')->first();
        $this->assertEquals(89980, (float) $order->final_amount);
    }

    /**
     * Giao diện POS tạo đơn qua fetch (Accept: application/json) — khi tạo đơn thất bại (vd. dùng
     * điểm cho khách vãng lai), phản hồi phải là JSON 422 kèm lỗi thay vì redirect-back cổ điển, để
     * JS hiện thông báo mà KHÔNG tải lại trang (tránh mất trạng thái khách hàng đã chọn/điểm đã nhập
     * — đúng nguyên nhân gây ra triệu chứng "mất thông tin khách hàng" trước đây).
     */
    public function test_pos_store_order_returns_json_error_for_ajax_request(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('14:00:00'));

        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $product = $this->makeProduct(['base_price' => 20000]);

        $this->actingAs($receptionist);
        $this->postJson('/cart/add', ['product_id' => $product->id, 'quantity' => 1])->assertOk();
        $response = $this->postJson('/staff/reception/orders', ['payment_method' => 'cash', 'points_to_redeem' => 20]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('points_to_redeem');
        $this->assertNull(Order::where('created_by', $receptionist->id)->first());
    }

    /**
     * Ngược lại, khi tạo đơn thành công qua fetch, phản hồi JSON phải trả về redirect_url để JS tự
     * điều hướng — không phải redirect HTTP cổ điển (fetch không tự follow redirect sang trang HTML).
     */
    public function test_pos_store_order_returns_redirect_url_json_on_success(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('14:00:00'));

        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $product = $this->makeProduct(['base_price' => 20000]);

        $this->actingAs($receptionist);
        $this->postJson('/cart/add', ['product_id' => $product->id, 'quantity' => 1])->assertOk();
        $response = $this->postJson('/staff/reception/orders', ['payment_method' => 'cash']);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $order = Order::where('created_by', $receptionist->id)->orderByDesc('id')->first();
        $this->assertNotNull($order);
        $this->assertStringContainsString((string) $order->id, $response->json('redirect_url'));
    }

    /**
     * Nhân viên lễ tân đã TỰ TAY tạo đơn tại quầy (created_by) không được xóa cứng dù đơn đó đứng
     * tên khách khác hoặc khách vãng lai (user_id không còn trỏ về lễ tân nữa từ Giai đoạn 2) — vẫn
     * phải giữ dấu vết ai đã bán đơn này.
     */
    public function test_admin_cannot_delete_receptionist_who_created_pos_orders(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('14:00:00'));

        $admin = User::factory()->create(['role' => 'admin']);
        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $product = $this->makeProduct(['base_price' => 20000]);

        $this->actingAs($receptionist);
        $this->postJson('/cart/add', ['product_id' => $product->id, 'quantity' => 1])->assertOk();
        $this->post('/staff/reception/orders', ['payment_method' => 'cash']); // Khách vãng lai -> user_id null

        $this->actingAs($admin);
        $response = $this->deleteJson("/admin/staff-accounts/{$receptionist->id}");
        $response->assertStatus(422);
        $this->assertDatabaseHas('users', ['id' => $receptionist->id]);
    }

    /**
     * POS Giai đoạn 4: lễ tân nhập mã khuyến mãi tay -> ưu tiên dùng mã đó thay vì tự động chọn mã
     * tốt nhất (resolveAutoPromotion), kể cả khi mã tự động có sẵn và đủ điều kiện.
     */
    public function test_pos_manual_coupon_code_overrides_auto_promotion(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('14:00:00'));

        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $product = $this->makeProduct(['base_price' => 100000]);

        \App\Models\Promotion::create(['code' => 'AUTO5', 'type' => 'percent', 'value' => 5, 'apply_for' => 'all', 'is_active' => true, 'is_recurring' => false]);
        \App\Models\Promotion::create(['code' => 'MANUAL20', 'type' => 'percent', 'value' => 20, 'apply_for' => 'all', 'is_active' => true, 'is_recurring' => false]);

        $this->actingAs($receptionist);
        $this->postJson('/cart/add', ['product_id' => $product->id, 'quantity' => 1])->assertOk();
        $response = $this->post('/staff/reception/orders', ['payment_method' => 'cash', 'coupon_code' => 'manual20']);
        $response->assertSessionHasNoErrors();

        $order = Order::where('created_by', $receptionist->id)->orderByDesc('id')->first();
        $this->assertEquals('MANUAL20', $order->coupon_code);
        $this->assertEquals(20000, (float) $order->discount_amount);
    }

    /**
     * Mã khuyến mãi thủ công phải kiểm tra hạng thành viên/"đã dùng chưa" trên KHÁCH ĐƯỢC CHỌN,
     * không phải tài khoản lễ tân — khách vãng lai (không có tài khoản) không đủ điều kiện dùng mã
     * yêu cầu hạng thành viên, nhưng khách hạng đúng yêu cầu thì dùng được.
     */
    public function test_pos_manual_coupon_validates_against_selected_customer_not_receptionist(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('14:00:00'));

        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']); // membership_level mặc định 'new'
        $goldCustomer = User::factory()->create(['role' => 'customer', 'membership_level' => 'gold']);
        $product = $this->makeProduct(['base_price' => 100000]);
        \App\Models\Promotion::create(['code' => 'GOLDONLY', 'type' => 'percent', 'value' => 10, 'apply_for' => 'gold', 'is_active' => true, 'is_recurring' => false]);

        $this->actingAs($receptionist);

        // Khách vãng lai (không chọn ai) -> không có tài khoản để đạt hạng gold -> bị từ chối
        $this->postJson('/cart/add', ['product_id' => $product->id, 'quantity' => 1])->assertOk();
        $response = $this->post('/staff/reception/orders', ['payment_method' => 'cash', 'coupon_code' => 'GOLDONLY']);
        $response->assertSessionHasErrors('coupon_code');

        // Chọn đúng khách hạng gold -> dùng được
        $this->postJson('/cart/add', ['product_id' => $product->id, 'quantity' => 1])->assertOk();
        $response = $this->post('/staff/reception/orders', [
            'payment_method' => 'cash', 'coupon_code' => 'GOLDONLY', 'customer_id' => $goldCustomer->id,
        ]);
        $response->assertSessionHasNoErrors();

        $order = Order::where('created_by', $receptionist->id)->orderByDesc('id')->first();
        $this->assertEquals('GOLDONLY', $order->coupon_code);
    }

    /**
     * Endpoint xem trước tổng tiền (preview-total) phải báo lỗi rõ ràng khi mã khuyến mãi nhập tay
     * không tồn tại/không hợp lệ — không được âm thầm bỏ qua mã và tính như không có gì.
     */
    public function test_preview_total_endpoint_reports_error_for_invalid_manual_coupon(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('14:00:00'));

        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $product = $this->makeProduct(['base_price' => 50000]);

        $this->actingAs($receptionist);
        $this->postJson('/cart/add', ['product_id' => $product->id, 'quantity' => 1])->assertOk();

        $response = $this->getJson('/staff/reception/orders/preview-total?coupon_code=KHONGTONTAI');
        $response->assertOk();
        $response->assertJson(['discount' => 0]);
        $this->assertNotNull($response->json('coupon_error'));
    }

    /**
     * Vá lỗ hổng hoàn điểm khi hủy: đơn đã dùng điểm tích lũy giảm giá mà bị hủy sau đó phải hoàn
     * lại ĐÚNG số điểm đã dùng cho khách — trước Giai đoạn 4, hủy đơn không hoàn điểm gì cả.
     */
    public function test_cancelling_order_refunds_redeemed_points(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('14:00:00'));

        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $customer = User::factory()->create(['role' => 'customer', 'points' => 50]);
        $product = $this->makeProduct(['base_price' => 100000]);

        $this->actingAs($receptionist);
        $this->postJson('/cart/add', ['product_id' => $product->id, 'quantity' => 1])->assertOk();
        $this->post('/staff/reception/orders', [
            'payment_method' => 'cash', 'customer_id' => $customer->id, 'points_to_redeem' => 20,
        ]);

        $order = Order::where('created_by', $receptionist->id)->orderByDesc('id')->first();
        $this->assertEquals(20, $order->points_redeemed);
        $this->assertEquals(30, $customer->fresh()->points);

        // Đơn chưa thanh toán (Giai đoạn 3: cash không còn tự động paid) -> hủy được bình thường
        $response = $this->patch("/staff/reception/orders/{$order->id}/status", [
            'status' => 'cancelled', 'cancel_reason' => 'Khách đổi ý không lấy nữa',
        ]);
        $response->assertSessionHasNoErrors();

        $this->assertEquals(50, $customer->fresh()->points);
    }

    /**
     * POS chỉ tạo đơn TẠI QUẦY/MANG ĐI — không còn hỗ trợ tạo đơn GIAO HÀNG từ màn hình này nữa
     * (đã loại bỏ). Dù có ai cố gửi thêm 'delivery_type'/'address_id' vào request (form giả mạo/API
     * gọi trực tiếp), các field này không còn nằm trong rule validate nên bị Laravel tự động bỏ qua
     * — đơn tạo ra vẫn luôn là 'pickup', không có ngoại lệ.
     */
    public function test_pos_order_is_always_pickup_ignoring_delivery_fields(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('14:00:00'));

        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $customer = User::factory()->create(['role' => 'customer']);
        $address = $this->makeAddress($customer->id);
        $product = $this->makeProduct(['base_price' => 50000]);

        $this->actingAs($receptionist);
        $this->postJson('/cart/add', ['product_id' => $product->id, 'quantity' => 1])->assertOk();
        $response = $this->post('/staff/reception/orders', [
            'payment_method' => 'cash',
            'delivery_type' => 'delivery',
            'customer_id' => $customer->id,
            'address_id' => $address->id,
        ]);
        $response->assertSessionHasNoErrors();

        $order = Order::where('created_by', $receptionist->id)->orderByDesc('id')->first();
        $this->assertNotNull($order);
        $this->assertEquals('pickup', $order->delivery_type);
        $this->assertEquals('dine_in', $order->pickup_mode);
    }

    /**
     * COD chỉ dành cho đơn giao hàng — không còn tồn tại trong POS nên 'cod' không còn là giá trị
     * hợp lệ cho payment_method ở đây nữa (chỉ còn cash/momo).
     */
    public function test_pos_pickup_order_rejects_cod_payment_method(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('14:00:00'));

        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $product = $this->makeProduct(['base_price' => 50000]);

        $this->actingAs($receptionist);
        $this->postJson('/cart/add', ['product_id' => $product->id, 'quantity' => 1])->assertOk();
        $response = $this->post('/staff/reception/orders', ['payment_method' => 'cod']);

        $response->assertSessionHasErrors('payment_method');
        $this->assertNull(Order::where('created_by', $receptionist->id)->first());
    }

    /**
     * Chức năng ca làm việc đã bị loại bỏ khỏi khu vực lễ tân — đơn POS phải tạo được bình thường,
     * không phụ thuộc bất kỳ trạng thái ca nào; shift_id (cột còn lại trong DB, chưa migrate xóa)
     * luôn là null, created_by vẫn đúng lễ tân xử lý đơn.
     */
    public function test_pos_order_has_null_shift_id_and_correct_created_by(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('14:00:00'));

        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $product = $this->makeProduct(['base_price' => 20000]);

        $this->actingAs($receptionist);

        $this->postJson('/cart/add', ['product_id' => $product->id, 'quantity' => 1])->assertOk();
        $response = $this->post('/staff/reception/orders', ['payment_method' => 'cash']);
        $response->assertSessionHasNoErrors();

        $order = Order::where('created_by', $receptionist->id)->orderByDesc('id')->first();
        $this->assertNotNull($order);
        $this->assertEquals($receptionist->id, $order->created_by);
        $this->assertNull($order->shift_id);
    }

    public function test_counter_order_auto_applies_eligible_promotion(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('14:00:00'));

        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $product = $this->makeProduct(['base_price' => 100000]);

        $promotion = \App\Models\Promotion::create([
            'code' => 'AUTO10',
            'type' => 'percent',
            'value' => 10,
            'apply_for' => 'all',
            'min_order_amount' => 50000,
            'is_active' => true,
            'is_recurring' => false,
        ]);

        $this->actingAs($receptionist);
        $this->postJson('/cart/add', ['product_id' => $product->id, 'quantity' => 1])->assertOk();
        $this->post('/staff/reception/orders', ['payment_method' => 'cash']);

        $order = Order::where('created_by', $receptionist->id)->latest()->first();
        $this->assertEquals($promotion->id, $order->promotion_id);
        $this->assertEquals(10000, (float) $order->discount_amount);
        $this->assertEquals(90000, (float) $order->final_amount);
    }

    /**
     * Chưa đạt đơn tối thiểu -> không tự động áp dụng khuyến mãi, đơn vẫn tạo bình thường với giá gốc.
     */
    public function test_counter_order_does_not_apply_promotion_below_minimum(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('14:00:00'));

        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $product = $this->makeProduct(['base_price' => 30000]);

        \App\Models\Promotion::create([
            'code' => 'AUTO10B',
            'type' => 'percent',
            'value' => 10,
            'apply_for' => 'all',
            'min_order_amount' => 50000,
            'is_active' => true,
            'is_recurring' => false,
        ]);

        $this->actingAs($receptionist);
        $this->postJson('/cart/add', ['product_id' => $product->id, 'quantity' => 1])->assertOk();
        $this->post('/staff/reception/orders', ['payment_method' => 'cash']);

        $order = Order::where('created_by', $receptionist->id)->latest()->first();
        $this->assertNull($order->promotion_id);
        $this->assertEquals(0, (float) $order->discount_amount);
        $this->assertEquals(30000, (float) $order->final_amount);
    }

    /**
     * Lễ tân xác nhận đã nhận lại tiền COD cho MỘT đơn cụ thể — chỉ áp dụng cho đơn COD đã hoàn
     * thành; đơn không phải COD hoặc chưa hoàn thành thì bị từ chối.
     */
    public function test_receptionist_can_settle_single_cod_order(): void
    {
        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $delivery = User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery']);
        $order = $this->makeOrder([
            'delivery_staff_id' => $delivery->id, 'status' => 'completed',
            'payment_method' => 'cod', 'payment_status' => 'paid', 'final_amount' => 45000,
        ]);

        $this->actingAs($receptionist);
        $response = $this->post("/staff/reception/cod-settlement/orders/{$order->id}/settle");
        $response->assertSessionHasNoErrors();

        $order = $order->fresh();
        $this->assertNotNull($order->cod_settled_at);
        $this->assertEquals($receptionist->id, $order->cod_settled_by);

        // Đơn chưa hoàn thành -> không đối soát được
        $notCompletedOrder = $this->makeOrder(['delivery_staff_id' => $delivery->id, 'status' => 'shipping', 'payment_method' => 'cod']);
        $response = $this->post("/staff/reception/cod-settlement/orders/{$notCompletedOrder->id}/settle");
        $response->assertSessionHasErrors('cod');
        $this->assertNull($notCompletedOrder->fresh()->cod_settled_at);
    }

    /**
     * Nút "Nộp tất cả": đánh dấu MỌI đơn COD đã hoàn thành, chưa đối soát của một nhân viên vận
     * chuyển thành đã nộp trong 1 lần — không đụng tới đơn của nhân viên vận chuyển khác.
     */
    public function test_receptionist_can_settle_all_cod_orders_for_one_delivery_staff(): void
    {
        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $deliveryA = User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery']);
        $deliveryB = User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery']);

        $orderA1 = $this->makeOrder(['delivery_staff_id' => $deliveryA->id, 'status' => 'completed', 'payment_method' => 'cod', 'final_amount' => 30000]);
        $orderA2 = $this->makeOrder(['delivery_staff_id' => $deliveryA->id, 'status' => 'completed', 'payment_method' => 'cod', 'final_amount' => 20000]);
        $orderB1 = $this->makeOrder(['delivery_staff_id' => $deliveryB->id, 'status' => 'completed', 'payment_method' => 'cod', 'final_amount' => 99000]);

        $this->actingAs($receptionist);
        $response = $this->post("/staff/reception/cod-settlement/staff/{$deliveryA->id}/settle-all");
        $response->assertRedirect();

        $this->assertNotNull($orderA1->fresh()->cod_settled_at);
        $this->assertNotNull($orderA2->fresh()->cod_settled_at);
        // Đơn của shipper khác không bị đụng tới
        $this->assertNull($orderB1->fresh()->cod_settled_at);
    }

    /**
     * Trang đối soát COD hiển thị đúng tổng tiền chưa nộp theo từng nhân viên vận chuyển.
     */
    public function test_cod_settlement_page_shows_correct_unsettled_totals_per_staff(): void
    {
        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $delivery = User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery', 'name' => 'Vận Chuyển X']);

        $this->makeOrder(['delivery_staff_id' => $delivery->id, 'status' => 'completed', 'payment_method' => 'cod', 'final_amount' => 40000]);
        $this->makeOrder(['delivery_staff_id' => $delivery->id, 'status' => 'completed', 'payment_method' => 'cod', 'final_amount' => 25000]);
        // Đã nộp rồi -> không tính vào "chưa nộp"
        $this->makeOrder(['delivery_staff_id' => $delivery->id, 'status' => 'completed', 'payment_method' => 'cod', 'final_amount' => 60000, 'cod_settled_at' => now()]);

        $this->actingAs($receptionist);
        $response = $this->get('/staff/reception/cod-settlement');

        $response->assertStatus(200);
        $response->assertSee('Vận Chuyển X');
        $response->assertSee('65.000');
    }

    /**
     * Dashboard vận chuyển tách đúng "đã thu chưa nộp" và "đã nộp quầy" từ tổng đã thu.
     */
    public function test_delivery_dashboard_splits_settled_and_unsettled_cod(): void
    {
        $delivery = User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery']);

        $this->makeOrder(['delivery_staff_id' => $delivery->id, 'status' => 'completed', 'payment_method' => 'cod', 'final_amount' => 40000]);
        $this->makeOrder(['delivery_staff_id' => $delivery->id, 'status' => 'completed', 'payment_method' => 'cod', 'final_amount' => 60000, 'cod_settled_at' => now()]);

        $this->actingAs($delivery);
        $response = $this->get('/staff/delivery/dashboard');

        $response->assertStatus(200);
        $response->assertViewHas('codCollectedTotal', 100000.0);
        $response->assertViewHas('codUnsettledTotal', 40000.0);
        $response->assertViewHas('codSettledTotal', 60000.0);
    }

    /**
     * Sidebar chỉ được sáng đúng 1 mục tương ứng trang đang xem. Bug cũ: mục "Đơn hàng" dùng wildcard
     * routeIs('staff.reception.orders.*') nên khớp luôn cả route 'staff.reception.orders.create',
     * khiến "Đơn hàng" và "Tạo đơn" cùng sáng lúc đang ở trang Tạo đơn.
     */
    public function test_sidebar_highlights_only_current_menu_not_both_orders_and_create(): void
    {
        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $this->actingAs($receptionist);

        $response = $this->get('/staff/reception/orders/create');
        $response->assertStatus(200);

        $activeCount = substr_count($response->getContent(), 'bg-sidebar-active text-sidebar-active-text');
        $this->assertEquals(1, $activeCount, 'Chỉ đúng 1 mục sidebar được sáng khi đang ở trang Tạo đơn.');
    }

    /**
     * Trang sửa nhân viên hiển thị đúng dữ liệu hiện tại (điền sẵn form).
     */
    public function test_admin_can_view_staff_edit_page_with_prefilled_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist', 'name' => 'Lễ Tân Cần Sửa']);

        $this->actingAs($admin);
        $response = $this->get("/admin/staff-accounts/{$staff->id}/edit");

        $response->assertStatus(200);
        $response->assertSee('Lễ Tân Cần Sửa');
        $response->assertSee($staff->email);
    }

    /**
     * Admin cập nhật thông tin nhân viên (tên/email/SĐT/loại) — không đổi mật khẩu nếu để trống.
     */
    public function test_admin_can_update_staff_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist', 'password' => bcrypt('OldPassword123')]);
        $oldPasswordHash = $staff->password;

        $this->actingAs($admin);
        $response = $this->put("/admin/staff-accounts/{$staff->id}", [
            'name' => 'Tên Đã Sửa',
            'email' => 'da-sua@happytea.com',
            'phone' => '0912345678',
            'staff_type' => 'delivery',
            'is_active' => 1,
        ]);
        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $staff = $staff->fresh();
        $this->assertEquals('Tên Đã Sửa', $staff->name);
        $this->assertEquals('da-sua@happytea.com', $staff->email);
        $this->assertEquals('0912345678', $staff->phone);
        $this->assertEquals('delivery', $staff->staff_type);
        // Không nhập mật khẩu mới -> mật khẩu cũ giữ nguyên
        $this->assertEquals($oldPasswordHash, $staff->password);
    }

    /**
     * Tạo nhân viên kèm ảnh đại diện: file được lưu vào public/uploads/avatars (Railway Volume
     * bền vững) và đường dẫn kèm tiền tố 'uploads/avatars/' lưu đúng vào cột users.avatar.
     */
    public function test_admin_can_upload_avatar_when_creating_staff(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $avatar = \Illuminate\Http\UploadedFile::fake()->image('new-staff.jpg');

        $response = $this->post('/admin/staff-accounts', [
            'name' => 'NV Có Ảnh', 'email' => 'nv-avatar@happytea.com',
            'password' => 'Password123', 'password_confirmation' => 'Password123',
            'is_active' => 1, 'staff_type' => 'receptionist',
            'avatar' => $avatar,
        ]);
        $response->assertSessionHasNoErrors();

        $staff = User::where('email', 'nv-avatar@happytea.com')->firstOrFail();
        $this->assertNotNull($staff->avatar);
        $this->assertStringStartsWith('uploads/avatars/', $staff->avatar);
        $path = avatar_path($staff->avatar);
        $this->assertFileExists($path);
        @unlink($path);
    }

    /**
     * Sửa nhân viên kèm đổi ảnh đại diện: ảnh cũ bị xóa khỏi đĩa, ảnh mới được lưu và cập nhật
     * vào cột users.avatar.
     * Ảnh cũ ở đây cố tình dựng theo ĐỊNH DẠNG CŨ (tên file trần trong public/images/avatars) để
     * đồng thời kiểm tra avatar_path() vẫn xoá đúng dữ liệu có từ trước khi chuyển sang uploads/.
     */
    public function test_admin_updating_staff_avatar_replaces_old_file(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $oldAvatarName = 'old_' . time() . '.jpg';
        \Illuminate\Http\UploadedFile::fake()->image('old.jpg')->move(public_path('images/avatars'), $oldAvatarName);
        $staff = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist', 'avatar' => $oldAvatarName]);
        $oldPath = public_path('images/avatars/' . $oldAvatarName);
        $this->assertFileExists($oldPath);

        $this->actingAs($admin);
        $newAvatar = \Illuminate\Http\UploadedFile::fake()->image('new.jpg');
        $response = $this->put("/admin/staff-accounts/{$staff->id}", [
            'name' => $staff->name, 'email' => $staff->email,
            'staff_type' => 'receptionist', 'is_active' => 1,
            'avatar' => $newAvatar,
        ]);
        $response->assertSessionHasNoErrors();

        $staff = $staff->fresh();
        $this->assertNotEquals($oldAvatarName, $staff->avatar);
        $this->assertFileDoesNotExist($oldPath);
        $this->assertStringStartsWith('uploads/avatars/', $staff->avatar);
        $newPath = avatar_path($staff->avatar);
        $this->assertFileExists($newPath);
        @unlink($newPath);
    }

    /**
     * Admin đổi mật khẩu nhân viên khi có nhập; email trùng với nhân viên KHÁC bị từ chối, nhưng
     * giữ nguyên email của chính mình (không tự đụng unique rule với bản thân) thì không lỗi.
     */
    public function test_admin_updating_staff_password_and_email_uniqueness_rules(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staffA = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist', 'email' => 'a@happytea.com']);
        $staffB = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist', 'email' => 'b@happytea.com']);

        $this->actingAs($admin);

        // Giữ nguyên email của chính mình -> không lỗi unique
        $response = $this->put("/admin/staff-accounts/{$staffA->id}", [
            'name' => $staffA->name, 'email' => 'a@happytea.com', 'staff_type' => 'receptionist', 'is_active' => 1,
            'password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123',
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('NewPassword123', $staffA->fresh()->password));

        // Đổi email trùng với nhân viên khác -> bị từ chối
        $response = $this->put("/admin/staff-accounts/{$staffA->id}", [
            'name' => $staffA->name, 'email' => 'b@happytea.com', 'staff_type' => 'receptionist', 'is_active' => 1,
        ]);
        $response->assertSessionHasErrors('email');
    }

    /**
     * Xóa nhân viên: bị từ chối nếu đã có lịch sử hoạt động thật (tạo đơn/được phân công/đối soát)
     * — tránh mất dấu vết do cột tham chiếu bị SET NULL; xóa được nếu chưa từng hoạt động.
     */
    public function test_admin_cannot_delete_staff_with_order_history_but_can_delete_clean_staff(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $usedDelivery = User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery']);
        $this->makeOrder(['delivery_staff_id' => $usedDelivery->id, 'status' => 'completed']);

        $unusedReceptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);

        $this->actingAs($admin);

        $response = $this->deleteJson("/admin/staff-accounts/{$usedDelivery->id}");
        $response->assertStatus(422);
        $this->assertDatabaseHas('users', ['id' => $usedDelivery->id]);

        $response = $this->deleteJson("/admin/staff-accounts/{$unusedReceptionist->id}");
        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertDatabaseMissing('users', ['id' => $unusedReceptionist->id]);
    }

    /**
     * Không đụng được tài khoản customer/admin qua route sửa/xóa nhân viên dù trùng ID.
     */
    public function test_staff_edit_and_delete_routes_never_touch_customer_or_admin_accounts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($admin);

        $this->get("/admin/staff-accounts/{$customer->id}/edit")->assertStatus(404);
        $this->put("/admin/staff-accounts/{$customer->id}", ['name' => 'Hack', 'email' => 'hack@x.com', 'staff_type' => 'delivery', 'is_active' => 1])->assertStatus(404);

        $response = $this->deleteJson("/admin/staff-accounts/{$customer->id}");
        $response->assertStatus(404);
        $this->assertDatabaseHas('users', ['id' => $customer->id, 'role' => 'customer']);
    }

    /**
     * Để trống SĐT khi sửa nhân viên phải lưu thành NULL (không phải chuỗi rỗng ''), và HAI nhân
     * viên khác nhau cùng để trống SĐT không được đụng độ unique — tái hiện đúng bug thực tế:
     * trim(null) trả về '' khiến "nullable" bị vô hiệu, gây lỗi trùng khóa 'users.phone'.
     */
    public function test_updating_staff_with_blank_phone_saves_null_not_empty_string(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staffA = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist', 'phone' => null]);
        $staffB = User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery', 'phone' => null]);

        $this->actingAs($admin);

        // Sửa staffA, để trống SĐT -> phải lưu NULL, không lỗi
        $response = $this->put("/admin/staff-accounts/{$staffA->id}", [
            'name' => 'Nguyễn Văn D', 'email' => $staffA->email, 'phone' => '',
            'staff_type' => 'receptionist', 'is_active' => 1,
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertNull($staffA->fresh()->phone);

        // Sửa tiếp staffB, CŨNG để trống SĐT -> vẫn không lỗi trùng khóa vì cả 2 đều là NULL thật sự
        $response = $this->put("/admin/staff-accounts/{$staffB->id}", [
            'name' => $staffB->name, 'email' => $staffB->email, 'phone' => '',
            'staff_type' => 'delivery', 'is_active' => 1,
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertNull($staffB->fresh()->phone);
    }

    /**
     * Tương tự cho tạo mới: để trống SĐT khi tạo nhân viên phải lưu NULL, tạo được nhiều nhân viên
     * cùng để trống SĐT mà không lỗi trùng khóa.
     */
    public function test_creating_staff_with_blank_phone_saves_null_not_empty_string(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $payload = [
            'password' => 'Password123', 'password_confirmation' => 'Password123',
            'is_active' => 1, 'staff_type' => 'receptionist', 'phone' => '',
        ];

        $this->post('/admin/staff-accounts', $payload + ['name' => 'NV Không SĐT 1', 'email' => 'nv1-nophone@happytea.com'])
            ->assertSessionHasNoErrors();
        $this->post('/admin/staff-accounts', $payload + ['name' => 'NV Không SĐT 2', 'email' => 'nv2-nophone@happytea.com'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'nv1-nophone@happytea.com', 'phone' => null]);
        $this->assertDatabaseHas('users', ['email' => 'nv2-nophone@happytea.com', 'phone' => null]);
    }

    /**
     * Xem trước tổng tiền đơn tại quầy TRƯỚC khi tạo đơn: có khuyến mãi tự động đủ điều kiện thì
     * phải hiện đúng số tiền giảm + tổng phải trả thật, khớp chính xác với số tiền khi tạo đơn thật.
     */
    public function test_preview_total_shows_correct_subtotal_and_auto_promotion_discount(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('14:00:00'));

        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $product = $this->makeProduct(['base_price' => 100000]);

        \App\Models\Promotion::create([
            'code' => 'PREVIEW10', 'type' => 'percent', 'value' => 10, 'apply_for' => 'all',
            'min_order_amount' => 50000, 'is_active' => true, 'is_recurring' => false,
        ]);

        $this->actingAs($receptionist);

        // Giỏ trống -> tất cả bằng 0, không lỗi
        $empty = $this->getJson('/staff/reception/orders/preview-total');
        $empty->assertOk();
        $empty->assertJson(['subtotal' => 0, 'discount' => 0, 'final_amount' => 0]);

        $this->postJson('/cart/add', ['product_id' => $product->id, 'quantity' => 1])->assertOk();

        $preview = $this->getJson('/staff/reception/orders/preview-total');
        $preview->assertOk();
        $preview->assertJson([
            'subtotal' => 100000,
            'discount' => 10000,
            'promotion_code' => 'PREVIEW10',
            'final_amount' => 90000,
        ]);

        // Số xem trước phải khớp chính xác với đơn tạo thật
        $this->post('/staff/reception/orders', ['payment_method' => 'cash']);
        $order = Order::where('created_by', $receptionist->id)->latest()->first();
        $this->assertEquals(90000, (float) $order->final_amount);
    }

    /**
     * Phân loại khuyến mãi theo kênh: mã chỉ dành cho "Giao hàng" (applies_to=delivery) KHÔNG
     * được tự động áp dụng cho đơn tại quầy — dù đủ điều kiện đơn tối thiểu và apply_for=all.
     */
    public function test_counter_order_does_not_auto_apply_delivery_only_promotion(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('14:00:00'));

        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $product = $this->makeProduct(['base_price' => 100000]);

        \App\Models\Promotion::create([
            'code' => 'SHIPONLY', 'type' => 'percent', 'value' => 20, 'apply_for' => 'all',
            'applies_to' => 'delivery', 'min_order_amount' => 50000, 'is_active' => true, 'is_recurring' => false,
        ]);

        $this->actingAs($receptionist);
        $this->postJson('/cart/add', ['product_id' => $product->id, 'quantity' => 1])->assertOk();

        $preview = $this->getJson('/staff/reception/orders/preview-total');
        $preview->assertJson(['subtotal' => 100000, 'discount' => 0, 'promotion_code' => null, 'final_amount' => 100000]);

        $this->post('/staff/reception/orders', ['payment_method' => 'cash']);
        $order = Order::where('created_by', $receptionist->id)->latest()->first();
        $this->assertNull($order->promotion_id);
        $this->assertEquals(100000, (float) $order->final_amount);
    }

    /**
     * Ngược lại: mã "Tại quầy" (applies_to=pickup) không được dùng cho đơn giao hàng (khách đặt
     * online, delivery_type=delivery) — kiểm tra ở tầng OrderService::create() dùng chung cho cả
     * COD lẫn MoMo của khách hàng thường.
     */
    public function test_delivery_order_rejects_pickup_only_promotion_code(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('14:00:00'));

        $customer = User::factory()->create(['role' => 'customer']);
        $product = $this->makeProduct(['base_price' => 100000]);

        $address = \App\Models\UserAddress::create([
            'user_id' => $customer->id,
            'fullname' => 'Nguyễn Văn A', 'phone' => '0911222333',
            'province' => 'Thành phố Hồ Chí Minh', 'district' => 'Quận 8', 'ward' => 'Phường Chánh Hưng',
            'specific_address' => '218 Cao Lỗ', 'type' => 'home', 'is_default' => true,
            'latitude' => 10.7368782, 'longitude' => 106.6801247,
        ]);

        \App\Models\Promotion::create([
            'code' => 'COUNTERONLY', 'type' => 'percent', 'value' => 20, 'apply_for' => 'all',
            'applies_to' => 'pickup', 'min_order_amount' => 50000, 'is_active' => true, 'is_recurring' => false,
        ]);

        $cart = \App\Models\Cart::create(['user_id' => $customer->id]);
        \App\Models\CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1, 'unit_price' => $product->base_price]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(\App\Services\OrderService::class)->create($customer, [
            'idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
            'address_id' => $address->id,
            'coupon_code' => 'COUNTERONLY',
        ], 'cod');
    }

    /**
     * Admin tạo khuyến mãi phải chọn kênh áp dụng hợp lệ (all/pickup/delivery); giá trị lạ bị từ chối.
     */
    public function test_admin_creating_promotion_validates_applies_to_channel(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $payload = [
            'type' => 'percent', 'value' => 10, 'apply_for' => 'all',
            'description' => 'Test promo',
        ];

        // Giá trị lạ -> từ chối
        $response = $this->post('/admin/promotions', $payload + ['applies_to' => 'invalid-channel']);
        $response->assertSessionHasErrors('applies_to');

        // Giá trị hợp lệ -> tạo thành công
        $response = $this->post('/admin/promotions', $payload + ['applies_to' => 'pickup', 'code' => 'VALIDCH']);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('promotions', ['code' => 'VALIDCH', 'applies_to' => 'pickup']);
    }

    /**
     * Mã khuyến mãi có set min_quantity (vd mã combo yêu cầu mua từ N món trở lên) phải bị từ chối
     * khi nhập tay nếu giỏ hàng CHƯA đủ số lượng, dù đã đủ giá trị đơn tối thiểu — và dùng được ngay
     * khi đủ số lượng.
     */
    public function test_pos_order_rejects_manual_coupon_when_quantity_below_minimum(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('14:00:00'));

        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $product = $this->makeProduct(['base_price' => 80000]);

        \App\Models\Promotion::create([
            'code' => 'COMBO2TEST', 'type' => 'fixed', 'value' => 15000, 'apply_for' => 'all',
            'applies_to' => 'pickup', 'min_order_amount' => 70000, 'min_quantity' => 2,
            'is_active' => true, 'is_recurring' => false,
        ]);

        $this->actingAs($receptionist);

        // Chỉ 1 món (80.000đ, đủ tiền tối thiểu 70.000đ nhưng CHƯA đủ 2 món) -> bị từ chối
        $this->postJson('/cart/add', ['product_id' => $product->id, 'quantity' => 1])->assertOk();
        $response = $this->post('/staff/reception/orders', ['payment_method' => 'cash', 'coupon_code' => 'COMBO2TEST']);
        $response->assertSessionHasErrors(['coupon_code' => 'Đơn hàng cần mua tối thiểu 2 món để dùng mã này.']);
        $this->assertNull(Order::where('created_by', $receptionist->id)->first());

        // Thêm món thứ 2 (đủ 2 món) -> dùng mã thành công
        $this->postJson('/cart/add', ['product_id' => $product->id, 'quantity' => 1])->assertOk();
        $response = $this->post('/staff/reception/orders', ['payment_method' => 'cash', 'coupon_code' => 'COMBO2TEST']);
        $response->assertSessionHasNoErrors();

        $order = Order::where('created_by', $receptionist->id)->orderByDesc('id')->first();
        $this->assertEquals('COMBO2TEST', $order->coupon_code);
        $this->assertEquals(15000, (float) $order->discount_amount);
    }

    /**
     * Mã tự động chọn (không nhập mã tay) có set min_quantity cũng phải bị loại khỏi danh sách ứng
     * viên nếu giỏ hàng chưa đủ số lượng — không được tự động áp nhầm khi khách chỉ mua 1 món.
     */
    public function test_pos_auto_promotion_skips_min_quantity_code_when_cart_has_too_few_items(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('14:00:00'));

        $receptionist = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $product = $this->makeProduct(['base_price' => 80000]);

        \App\Models\Promotion::create([
            'code' => 'AUTOCOMBO', 'type' => 'fixed', 'value' => 15000, 'apply_for' => 'all',
            'applies_to' => 'pickup', 'min_quantity' => 2, 'is_active' => true, 'is_recurring' => false,
        ]);

        $this->actingAs($receptionist);

        // Chỉ 1 món -> mã combo (min_quantity=2) không đủ điều kiện, không có mã khác -> không áp dụng gì
        $this->postJson('/cart/add', ['product_id' => $product->id, 'quantity' => 1])->assertOk();
        $this->post('/staff/reception/orders', ['payment_method' => 'cash']);
        $firstOrder = Order::where('created_by', $receptionist->id)->orderByDesc('id')->first();
        $this->assertNull($firstOrder->promotion_id);

        // Đơn trước đã tạo thành công nên giỏ hàng đã bị xóa sạch — thêm lại đủ 2 món cho đơn tiếp
        // theo (không phải "thêm 1 món nữa" vào giỏ cũ, vì giỏ cũ không còn tồn tại).
        $this->postJson('/cart/add', ['product_id' => $product->id, 'quantity' => 2])->assertOk();
        $this->post('/staff/reception/orders', ['payment_method' => 'cash']);
        $secondOrder = Order::where('created_by', $receptionist->id)->orderByDesc('id')->first();
        $this->assertNotNull($secondOrder->promotion_id);
        $this->assertEquals(15000, (float) $secondOrder->discount_amount);
    }
}
