<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AdminSettingControllerTest extends TestCase
{
    use RefreshDatabase;

    private function baseStorePayload(array $overrides = []): array
    {
        return array_merge([
            'section' => 'store',
            'store_name' => 'Happy Tea',
            'store_email' => 'admin@happytea.com',
            'store_phone' => '0123456789',
            'store_address' => '180 Cao Lỗ, Phường 4, Quận 8, TP. Hồ Chí Minh',
            'store_latitude' => '10.7380',
            'store_longitude' => '106.6778',
        ], $overrides);
    }

    public function test_admin_can_save_store_section_with_full_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->put('/admin/settings', $this->baseStorePayload([
            'store_open_time' => '08:00',
            'store_close_time' => '22:00',
        ]));

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertSame('08:00', Setting::getValue('store_open_time'));
        $this->assertSame('22:00', Setting::getValue('store_close_time'));
    }

    /**
     * Trường hợp cụ thể từng bị báo lỗi: chỉ muốn sửa 1 trường khác (vd tên cửa hàng) mà KHÔNG đụng
     * tới giờ mở/đóng cửa -> gửi form với ô giờ để trống không được ép buộc phải điền, và giá trị giờ
     * hiện có trong DB phải được GIỮ NGUYÊN chứ không bị xóa/ghi đè thành rỗng.
     */
    public function test_submitting_store_section_without_hours_keeps_existing_hours_unchanged(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Setting::setValue('store_open_time', '07:30', 'store', 'string');
        Setting::setValue('store_close_time', '21:30', 'store', 'string');

        $response = $this->actingAs($admin)->put('/admin/settings', $this->baseStorePayload([
            'store_name' => 'Happy Tea (đã đổi tên)',
            'store_open_time' => '',
            'store_close_time' => '',
        ]));

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertSame('Happy Tea (đã đổi tên)', Setting::getValue('store_name'));
        $this->assertSame('07:30', Setting::getValue('store_open_time'));
        $this->assertSame('21:30', Setting::getValue('store_close_time'));
    }

    public function test_store_section_rejects_missing_required_fields(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->put('/admin/settings', [
            'section' => 'store',
            'store_name' => '',
        ]);

        $response->assertSessionHasErrors(['store_name', 'store_email', 'store_phone', 'store_address']);
    }

    public function test_uploading_store_logo_saves_to_uploads_directory_and_removes_old_file(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->put('/admin/settings', array_merge($this->baseStorePayload(), [
            'store_logo' => UploadedFile::fake()->image('logo1.png'),
        ]));
        $firstLogo = Setting::getValue('store_logo');
        $this->assertStringStartsWith('/uploads/logo/', $firstLogo);
        $this->assertFileExists(public_path($firstLogo));

        $this->actingAs($admin)->put('/admin/settings', array_merge($this->baseStorePayload(), [
            'store_logo' => UploadedFile::fake()->image('logo2.png'),
        ]));
        $secondLogo = Setting::getValue('store_logo');

        $this->assertNotSame($firstLogo, $secondLogo);
        $this->assertFileDoesNotExist(public_path($firstLogo));
        $this->assertFileExists(public_path($secondLogo));
    }

    public function test_admin_can_toggle_payment_methods(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->putJson('/admin/settings', [
            'section' => 'payment',
            'cod_enabled' => '0',
            'momo_enabled' => '1',
            'payment_environment' => 'sandbox',
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertFalse(Setting::getValue('cod_enabled'));
        $this->assertTrue(Setting::getValue('momo_enabled'));
    }

    public function test_unknown_section_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->put('/admin/settings', ['section' => 'not_a_real_section']);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_guest_and_customer_cannot_access_settings(): void
    {
        $this->get('/admin/settings')->assertRedirect(route('login'));

        $customer = User::factory()->create(['role' => 'customer']);
        $this->actingAs($customer)->get('/admin/settings')->assertStatus(403);
    }

    public function test_receptionist_and_delivery_staff_cannot_access_settings(): void
    {
        $reception = User::factory()->create(['role' => 'staff', 'staff_type' => 'receptionist']);
        $delivery = User::factory()->create(['role' => 'staff', 'staff_type' => 'delivery']);

        $this->actingAs($reception)->get('/admin/settings')->assertRedirect(route('staff.reception.dashboard'));
        $this->actingAs($delivery)->get('/admin/settings')->assertRedirect(route('staff.delivery.dashboard'));
    }
}
