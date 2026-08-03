<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Bổ sung test CRUD khách hàng (admin) - trước đây chưa có. Bao gồm xác nhận migration
 * 2026_07_29_150632_fix_reviews_user_id_cascade_on_delete (reviews.user_id ON DELETE CASCADE):
 * khách hàng chỉ có review (không có đơn hàng) giờ xóa cứng được, kèm review của họ bị xóa theo.
 */
class AdminCustomerControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeCategory(): int
    {
        return DB::table('categories')->insertGetId([
            'name' => 'Trà sữa', 'slug' => 'tra-sua-' . uniqid(), 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_admin_can_create_customer_with_avatar(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/admin/customers', [
            'name' => 'Nguyễn Văn A',
            'email' => 'khachhang.moi@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '0912345678',
            'membership_level' => 'new',
            'points' => 0,
            'is_active' => 1,
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
        ]);

        $response->assertRedirect(route('admin.customers.index'));
        $response->assertSessionHasNoErrors();

        $customer = User::where('email', 'khachhang.moi@example.com')->firstOrFail();
        $this->assertSame('customer', $customer->role);
        $this->assertStringNotContainsString('/', $customer->avatar);
        $this->assertFileExists(avatar_path($customer->avatar));
    }

    public function test_creating_customer_rejects_duplicate_email(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'customer', 'email' => 'trung@example.com']);

        $response = $this->actingAs($admin)->post('/admin/customers', [
            'name' => 'Trùng email',
            'email' => 'trung@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'membership_level' => 'new',
            'points' => 0,
            'is_active' => 1,
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_updating_customer_avatar_deletes_old_file(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post('/admin/customers', [
            'name' => 'Nguyễn Văn B',
            'email' => 'khb@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'membership_level' => 'new',
            'points' => 0,
            'is_active' => 1,
            'avatar' => UploadedFile::fake()->image('old.jpg'),
        ]);
        $customer = User::where('email', 'khb@example.com')->firstOrFail();
        $oldPath = avatar_path($customer->avatar);
        $this->assertFileExists($oldPath);

        $response = $this->actingAs($admin)->put("/admin/customers/{$customer->id}", [
            'name' => 'Nguyễn Văn B (đã đổi ảnh)',
            'email' => 'khb@example.com',
            'membership_level' => 'new',
            'points' => 0,
            'is_active' => 1,
            'avatar' => UploadedFile::fake()->image('new.jpg'),
        ]);

        $response->assertRedirect(route('admin.customers.index'));
        $customer->refresh();
        $this->assertFileDoesNotExist($oldPath);
        $this->assertFileExists(avatar_path($customer->avatar));
    }

    public function test_admin_can_lock_and_unlock_customer_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => 1]);

        $lockResponse = $this->actingAs($admin)->postJson("/admin/customers/{$customer->id}/toggle-status", [
            'is_active' => 0,
            'lock_reason' => 'Vi phạm điều khoản sử dụng',
        ]);
        $lockResponse->assertOk()->assertJson(['success' => true]);
        $customer->refresh();
        $this->assertSame(0, (int) $customer->is_active);
        $this->assertSame('Vi phạm điều khoản sử dụng', $customer->lock_reason);

        $unlockResponse = $this->actingAs($admin)->postJson("/admin/customers/{$customer->id}/toggle-status", [
            'is_active' => 1,
        ]);
        $unlockResponse->assertOk()->assertJson(['success' => true]);
        $customer->refresh();
        $this->assertSame(1, (int) $customer->is_active);
        $this->assertNull($customer->lock_reason);
    }

    public function test_deleting_customer_with_only_reviews_hard_deletes_and_cascades_review(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $categoryId = $this->makeCategory();
        $product = Product::create([
            'name' => 'Trà sữa trân châu', 'slug' => 'tra-sua-' . uniqid(), 'sku' => 'SKU-' . strtoupper(uniqid()),
            'base_price' => 35000, 'category_id' => $categoryId, 'is_active' => true,
        ]);
        $review = Review::create([
            'user_id' => $customer->id, 'product_id' => $product->id, 'rating' => 5,
            'comment' => 'Ngon lắm!', 'is_visible' => true,
        ]);

        $response = $this->actingAs($admin)->delete("/admin/customers/{$customer->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('users', ['id' => $customer->id]);
        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
    }

    /**
     * LƯU Ý: orders.user_id là nullOnDelete() (không phải restrictOnDelete), nên xóa khách hàng có
     * lịch sử đơn hàng KHÔNG bị chặn bởi khóa ngoại như comment trong CustomerController::destroy()
     * mô tả ("sẽ bị lỗi foreign key nếu có Order") - đơn hàng vẫn còn (dữ liệu snapshot customer_name/
     * customer_phone không đổi) nhưng liên kết user_id bị NULL hóa. Test này xác nhận hành vi THỰC TẾ
     * của schema hiện tại, không phải hành vi comment mô tả (nhánh "khóa thay vì xóa" chỉ chạy nếu có
     * bảng khác thực sự chặn - hiện không còn bảng user_id nào dùng restrictOnDelete).
     */
    public function test_deleting_customer_with_order_history_hard_deletes_and_nulls_order_owner(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => 1]);
        $order = Order::create([
            'user_id' => $customer->id, 'order_code' => 'HPY-' . strtoupper(uniqid()),
            'customer_name' => $customer->name, 'customer_phone' => '0900000000',
            'delivery_address' => 'Test address', 'total_amount' => 35000, 'discount_amount' => 0,
            'final_amount' => 35000, 'payment_status' => 'paid', 'payment_method' => 'cash',
            'status' => 'completed', 'delivery_type' => 'pickup',
        ]);

        $response = $this->actingAs($admin)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->delete("/admin/customers/{$customer->id}");

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseMissing('users', ['id' => $customer->id]);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'user_id' => null]);
    }

    public function test_bulk_delete_hard_deletes_customers_including_those_with_order_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $freeCustomer = User::factory()->create(['role' => 'customer']);
        $customerWithOrder = User::factory()->create(['role' => 'customer', 'is_active' => 1]);
        Order::create([
            'user_id' => $customerWithOrder->id, 'order_code' => 'HPY-' . strtoupper(uniqid()),
            'customer_name' => $customerWithOrder->name, 'customer_phone' => '0900000000',
            'delivery_address' => 'Test address', 'total_amount' => 35000, 'discount_amount' => 0,
            'final_amount' => 35000, 'payment_status' => 'paid', 'payment_method' => 'cash',
            'status' => 'completed', 'delivery_type' => 'pickup',
        ]);

        $response = $this->actingAs($admin)->postJson('/admin/customers/bulk-delete', [
            'ids' => [$freeCustomer->id, $customerWithOrder->id],
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseMissing('users', ['id' => $freeCustomer->id]);
        $this->assertDatabaseMissing('users', ['id' => $customerWithOrder->id]);
    }

    public function test_guest_and_customer_cannot_access_customer_management_routes(): void
    {
        $this->get('/admin/customers')->assertRedirect(route('login'));

        $customer = User::factory()->create(['role' => 'customer']);
        $this->actingAs($customer)->get('/admin/customers')->assertStatus(403);
    }

    public function test_receptionist_and_delivery_staff_cannot_access_customer_management_routes(): void
    {
        $reception = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $delivery = User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery']);

        $this->actingAs($reception)->get('/admin/customers')->assertRedirect(route('staff.reception.dashboard'));
        $this->actingAs($delivery)->get('/admin/customers')->assertRedirect(route('staff.delivery.dashboard'));
    }
}
