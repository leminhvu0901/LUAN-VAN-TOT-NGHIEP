<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Order;
use App\Models\Material;
use App\Models\MaterialImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StaffRoleWorkflowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test phân quyền truy cập các route Staff và route Admin quản lý nhân viên.
     */
    public function test_access_permissions_across_roles(): void
    {
        $guest = null;
        $customer = User::factory()->create(['role' => 'customer']);
        $staff = User::factory()->create(['role' => 'staff']);
        $admin = User::factory()->create(['role' => 'admin']);

        $material = Material::create([
            'name' => 'Trà xanh',
            'unit' => 'g',
            'unit_price' => 100,
            'current_stock' => 0,
            'is_active' => true
        ]);

        $order = Order::create([
            'order_code' => 'HPY-12345678',
            'customer_name' => 'Khách hàng Test',
            'customer_phone' => '0901234567',
            'delivery_address' => '123 Đường Test',
            'total_amount' => 50000,
            'final_amount' => 50000,
            'payment_status' => 'unpaid',
            'payment_method' => 'cod',
            'status' => 'pending',
        ]);

        // --- 1. KIỂM TRA GUEST (Chưa đăng nhập) -> chuyển hướng về login ---
        $this->get('/staff/dashboard')->assertRedirect('/login');
        $this->get('/staff/orders')->assertRedirect('/login');
        $this->get('/admin/staff-accounts')->assertRedirect('/login');

        // --- 2. KIỂM TRA CUSTOMER -> trả về lỗi 403 Forbidden ---
        $this->actingAs($customer);
        $this->get('/staff/dashboard')->assertStatus(403);
        $this->get('/staff/orders')->assertStatus(403);
        $this->get('/admin/staff-accounts')->assertStatus(403);

        // --- 3. KIỂM TRA STAFF -> được vào staff, bị chặn admin staff-accounts (chuyển hướng về staff dashboard) ---
        $this->actingAs($staff);
        $this->get('/staff/dashboard')->assertStatus(200);
        $this->get('/staff/orders')->assertStatus(200);
        $this->get('/admin/staff-accounts')->assertRedirect(route('staff.dashboard'));

        // --- 4. KIỂM TRA ADMIN -> được vào cả hai ---
        $this->actingAs($admin);
        $this->get('/staff/dashboard')->assertStatus(200);
        $this->get('/admin/staff-accounts')->assertStatus(200);
    }

    /**
     * Test nhân viên cập nhật trạng thái đơn hàng (PATCH).
     */
    public function test_staff_can_update_order_status(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        
        $order = Order::create([
            'order_code' => 'HPY-87654321',
            'customer_name' => 'Khách Test 2',
            'customer_phone' => '0901234567',
            'delivery_address' => '456 Đường Test',
            'total_amount' => 30000,
            'final_amount' => 30000,
            'payment_status' => 'unpaid',
            'payment_method' => 'cod',
            'status' => 'pending',
        ]);

        $this->actingAs($staff);

        // Staff cập nhật trạng thái hợp lệ qua PATCH
        $response = $this->patch("/staff/orders/{$order->id}/status", [
            'status' => 'confirmed'
        ]);

        $response->assertRedirect();
        $this->assertEquals('confirmed', $order->fresh()->status);

        // Staff cập nhật trạng thái không hợp lệ -> báo lỗi validation
        $response = $this->patch("/staff/orders/{$order->id}/status", [
            'status' => 'invalid_status'
        ]);
        $response->assertSessionHasErrors('status');
    }

    /**
     * Test nhân viên nhập kho và ghi nhận audit log.
     */
    public function test_staff_can_import_stock_with_audit_logging(): void
    {
        $staff = User::factory()->create([
            'name' => 'Nguyễn Nhân Viên',
            'email' => 'nv@happytea.com',
            'role' => 'staff'
        ]);

        $material = Material::create([
            'name' => 'Sữa đặc',
            'unit' => 'hộp',
            'unit_price' => 20000,
            'current_stock' => 10,
            'is_active' => true
        ]);

        $this->actingAs($staff);

        $response = $this->post("/staff/materials/{$material->id}/imports", [
            'quantity' => 5,
            'total_price' => 110000,
            'note' => 'Nhập lô hàng sữa đặc tháng 7',
        ]);

        $response->assertRedirect();

        // Kiểm tra lô nhập kho được ghi nhận đúng thông tin và audit note
        $expectedNote = '[Nhân viên: Nguyễn Nhân Viên (nv@happytea.com)] Nhập lô hàng sữa đặc tháng 7';
        
        $this->assertDatabaseHas('material_imports', [
            'material_id' => $material->id,
            'quantity' => 5,
            'total_price' => 110000,
            'note' => $expectedNote,
        ]);

        // Kiểm tra tồn kho vật tư và đơn giá bình quân được tính lại bằng bcmath
        $material = $material->fresh();
        $this->assertEquals(15, (float) $material->current_stock);
        // Tồn kho cũ (10 * 20000 = 200000) + Nhập mới (110000) = 310000 / 15 = 20666.6666...
        $this->assertEquals(20666.6666, (float) $material->unit_price);
    }

    /**
     * Test nhân viên xuất kho sử dụng (lấy hàng ra khỏi kho để dùng tại quầy, không qua đơn hàng).
     */
    public function test_staff_can_consume_stock_manually(): void
    {
        $staff = User::factory()->create([
            'name' => 'Trần Thu Ngân',
            'email' => 'tn@happytea.com',
            'role' => 'staff',
        ]);

        $material = Material::create([
            'name' => 'Ly nhựa 500ml',
            'unit' => 'lốc',
            'unit_price' => 15000,
            'current_stock' => 0,
            'is_active' => true,
        ]);

        $lot = MaterialImport::create([
            'material_id' => $material->id,
            'quantity' => 10,
            'remaining_quantity' => 10,
            'total_price' => 150000,
            'note' => 'Nhập ban đầu',
        ]);

        // Đồng bộ current_stock với lô vừa tạo (giống hành vi InventoryService::createImportLot)
        $material->update(['current_stock' => 10]);

        $this->actingAs($staff);

        $response = $this->post("/staff/materials/{$material->id}/consume", [
            'quantity' => 1,
            'reason' => 'Hết ly tại quầy, lấy thêm để pha chế',
        ]);

        $response->assertRedirect();

        $material = $material->fresh();
        $this->assertEquals(9, (float) $material->current_stock);

        $expectedNote = '[Nhân viên: Trần Thu Ngân (tn@happytea.com)] Hết ly tại quầy, lấy thêm để pha chế';
        $this->assertDatabaseHas('material_imports', [
            'material_id' => $material->id,
            'quantity' => -1,
            'note' => $expectedNote,
        ]);

        $this->assertEquals(9, (float) $lot->fresh()->remaining_quantity);

        // Xuất vượt quá tồn kho -> phải bị chặn, không cho phép âm kho
        $response = $this->post("/staff/materials/{$material->id}/consume", [
            'quantity' => 999,
            'reason' => 'Thử vượt tồn kho',
        ]);
        $response->assertSessionHasErrors('quantity');
        $this->assertEquals(9, (float) $material->fresh()->current_stock);
    }
}
