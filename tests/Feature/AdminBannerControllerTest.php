<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Bộ test này xác nhận toàn bộ luồng CRUD và quy ước lưu ảnh banner: ảnh mẫu đi kèm mã nguồn nằm ở
 * public/images/banners, ảnh admin tự tải lên nằm ở public/images/uploads/banners (thư mục được gắn
 * ổ đĩa bền vững khi chạy trên Railway).
 */
class AdminBannerControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Trang danh sách và trang sửa phải dựng đúng URL cho CẢ 2 kiểu đường dẫn.
     * Trước đây 2 view này tự cắt basename() rồi ghép cứng 'images/banners/' nên ảnh mới tải lên
     * (có thêm thư mục con 'uploads/') bị mất đường dẫn và vỡ ảnh.
     */
    public function test_banner_pages_render_correct_image_url_for_both_old_and_new_paths(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $legacy = Banner::create([
            'title' => 'Banner mẫu cũ',
            'image_url' => 'banners/slider-1.png',
            'image' => 'banners/slider-1.png',
            'position' => 'home_slider',
            'is_active' => 1,
        ]);
        $uploaded = Banner::create([
            'title' => 'Banner mới tải lên',
            'image_url' => 'uploads/banners/moi.png',
            'image' => 'uploads/banners/moi.png',
            'position' => 'home_slider',
            'is_active' => 1,
        ]);

        $list = $this->actingAs($admin)->get('/admin/banners');
        $list->assertOk();
        $list->assertSee('/images/banners/slider-1.png', false);
        $list->assertSee('/images/uploads/banners/moi.png', false);

        $this->actingAs($admin)->get("/admin/banners/{$legacy->id}/edit")
            ->assertOk()->assertSee('/images/banners/slider-1.png', false);

        $this->actingAs($admin)->get("/admin/banners/{$uploaded->id}/edit")
            ->assertOk()->assertSee('/images/uploads/banners/moi.png', false);
    }

    public function test_admin_can_create_banner_with_image_stored_in_images_directory(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $image = UploadedFile::fake()->image('banner.jpg', 1200, 400);

        $response = $this->actingAs($admin)->post('/admin/banners', [
            'title' => 'Khuyến mãi hè',
            'image' => $image,
            'position' => 'home_slider',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.banners.index'));
        $response->assertSessionHasNoErrors();

        $banner = Banner::firstOrFail();
        $this->assertStringStartsWith('uploads/banners/', $banner->image_url);
        $this->assertFileExists(upload_path($banner->image_url));
    }

    public function test_creating_banner_without_image_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/admin/banners', [
            'title' => 'Thiếu ảnh',
        ]);

        $response->assertSessionHasErrors('image');
        $this->assertSame(0, Banner::count());
    }

    public function test_updating_banner_image_deletes_the_old_file(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $image = UploadedFile::fake()->image('old.jpg');

        $this->actingAs($admin)->post('/admin/banners', [
            'title' => 'Banner gốc',
            'image' => $image,
            'is_active' => '1',
        ]);
        $banner = Banner::firstOrFail();
        $oldPath = upload_path($banner->image_url);
        $this->assertFileExists($oldPath);

        $newImage = UploadedFile::fake()->image('new.jpg');
        $response = $this->actingAs($admin)->put("/admin/banners/{$banner->id}", [
            'title' => 'Banner đã đổi ảnh',
            'image' => $newImage,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.banners.index'));
        $banner->refresh();
        $this->assertFileDoesNotExist($oldPath);
        $this->assertFileExists(upload_path($banner->image_url));
    }

    public function test_admin_can_delete_banner_and_its_image_file(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post('/admin/banners', [
            'title' => 'Banner sẽ xóa',
            'image' => UploadedFile::fake()->image('x.jpg'),
            'is_active' => '1',
        ]);
        $banner = Banner::firstOrFail();
        $path = upload_path($banner->image_url);
        $this->assertFileExists($path);

        $response = $this->actingAs($admin)->delete("/admin/banners/{$banner->id}");

        $response->assertRedirect(route('admin.banners.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('banners', ['id' => $banner->id]);
        $this->assertFileDoesNotExist($path);
    }

    public function test_admin_can_toggle_banner_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $banner = Banner::create(['title' => 'B1', 'image' => '', 'image_url' => 'banners/x.jpg', 'is_active' => true]);

        $response = $this->actingAs($admin)->post("/admin/banners/{$banner->id}/toggle-status");

        $response->assertRedirect(route('admin.banners.index'));
        $response->assertSessionHas('success');
        $this->assertSame(0, $banner->fresh()->is_active);
    }

    public function test_admin_can_bulk_delete_selected_banners(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $b1 = Banner::create(['title' => 'B1', 'image' => '', 'image_url' => 'banners/a.jpg', 'is_active' => true]);
        $b2 = Banner::create(['title' => 'B2', 'image' => '', 'image_url' => 'banners/b.jpg', 'is_active' => true]);
        $keep = Banner::create(['title' => 'B3', 'image' => '', 'image_url' => 'banners/c.jpg', 'is_active' => true]);

        $response = $this->actingAs($admin)->post('/admin/banners/bulk-delete', [
            'banner_ids' => [$b1->id, $b2->id],
        ]);

        $response->assertRedirect(route('admin.banners.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('banners', ['id' => $b1->id]);
        $this->assertDatabaseMissing('banners', ['id' => $b2->id]);
        $this->assertDatabaseHas('banners', ['id' => $keep->id]);
    }

    public function test_guest_cannot_access_banner_routes(): void
    {
        $this->get('/admin/banners')->assertRedirect(route('login'));
    }

    public function test_customer_cannot_access_banner_routes(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $this->actingAs($customer)->get('/admin/banners')->assertStatus(403);
    }

    public function test_receptionist_and_delivery_staff_cannot_access_banner_routes(): void
    {
        // IsAdmin đưa staff về ĐÚNG khu vực của họ (302) thay vì 403 chung chung — hành vi này đã có
        // sẵn và nhất quán với các middleware phân quyền khác trong dự án, không phải lỗi thiếu chặn.
        $reception = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $delivery = User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery']);

        $this->actingAs($reception)->get('/admin/banners')->assertRedirect(route('staff.reception.dashboard'));
        $this->actingAs($delivery)->get('/admin/banners')->assertRedirect(route('staff.delivery.dashboard'));
    }
}
