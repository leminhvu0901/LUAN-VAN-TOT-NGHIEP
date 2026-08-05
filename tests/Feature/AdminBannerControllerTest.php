<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Bộ test này xác nhận toàn bộ luồng CRUD và quy ước lưu ảnh banner dưới public/images/banners.
 */
class AdminBannerControllerTest extends TestCase
{
    use RefreshDatabase;

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
        $this->assertStringStartsWith('banners/', $banner->image_url);
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
