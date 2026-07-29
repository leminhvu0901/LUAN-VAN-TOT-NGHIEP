<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    private const TINY_PNG_BASE64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

    public function test_profile_update_rejects_phone_with_letters(): void
    {
        $user = User::create([
            'name' => 'Test User', 'email' => 'test@example.com', 'password' => bcrypt('password'),
            'role' => 'customer', 'is_active' => 1,
        ]);

        $this->actingAs($user);
        $page = $this->from('/profile')->followingRedirects()
            ->post('/profile', ['name' => 'Test User', 'phone' => '0388359vsdasd']);

        $page->assertSee('Số điện thoại không đúng định dạng.');
        $this->assertNull($user->fresh()->phone);
    }

    public function test_profile_update_saves_name_and_phone(): void
    {
        $user = User::factory()->create(['role' => 'customer', 'name' => 'Ten Cu', 'phone' => null]);

        $response = $this->actingAs($user)->postJson('/profile', [
            'name' => 'Ten Moi',
            'phone' => '0912345678',
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        $fresh = $user->fresh();
        $this->assertEquals('Ten Moi', $fresh->name);
        $this->assertEquals('0912345678', $fresh->phone);
    }

    /**
     * Trực tiếp mô phỏng field cropped_avatar mà form (cả desktop lẫn mobile, sau khi fix) đều gửi —
     * đảm bảo backend xử lý đúng khi field này CÓ mặt trong request. Bug thật đã gặp: trang profile có
     * 2 <form> HTML riêng biệt (desktop + mobile, ẩn/hiện qua CSS theo viewport) cùng POST tới cùng
     * route profile.update — input ẩn cropped_avatar trước đây chỉ khai báo 1 lần trong form desktop,
     * nên sửa ảnh đại diện ở giao diện MOBILE rồi lưu thì ảnh không hề được gửi lên server (name/phone
     * vẫn lưu bình thường, hiện "thành công" gây hiểu lầm ảnh cũng đã lưu). Xem fix:
     * resources/views/frontend/profile.blade.php (thêm #croppedAvatarInputMobile) +
     * public/js/frontend/profile.js (cropImage() đồng bộ cả 2 input).
     */
    public function test_profile_update_saves_cropped_avatar(): void
    {
        $user = User::factory()->create(['role' => 'customer', 'avatar' => null]);

        $response = $this->actingAs($user)->postJson('/profile', [
            'name' => $user->name,
            'cropped_avatar' => self::TINY_PNG_BASE64,
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        $fresh = $user->fresh();
        $this->assertNotNull($fresh->avatar);
        // avatar_path(): ảnh tải lên giờ nằm ở public/uploads/ (Railway Volume bền vững) chứ không
        // còn ở public/images/ nữa - xem app/helpers.php.
        $this->assertStringStartsWith('uploads/avatars/', $fresh->avatar);
        $this->assertFileExists(avatar_path($fresh->avatar));
        $this->assertStringContainsString($fresh->avatar, $response->json('user.avatar_url'));

        @unlink(avatar_path($fresh->avatar));
    }

    /**
     * Regression test cho đúng bug đã gặp: trang profile PHẢI render input ẩn cropped_avatar ở CẢ 2
     * form (desktop và mobile) — nếu ai đó lỡ xóa mất input của form mobile trong tương lai, test này
     * sẽ đỏ ngay thay vì phải đợi người dùng report "lưu ảnh không thành công".
     */
    public function test_profile_page_renders_cropped_avatar_input_in_both_desktop_and_mobile_forms(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
        $response->assertSee('id="croppedAvatarInput"', false);
        $response->assertSee('id="croppedAvatarInputMobile"', false);
    }

    public function test_profile_update_rejects_name_longer_than_30_characters(): void
    {
        $user = User::factory()->create(['role' => 'customer', 'name' => 'Ten Cu']);
        $tooLongName = str_repeat('a', 31);

        $response = $this->actingAs($user)->postJson('/profile', ['name' => $tooLongName]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('name');
        $this->assertEquals('Họ và tên tối đa 30 ký tự.', $response->json('errors.name.0'));
        $this->assertEquals('Ten Cu', $user->fresh()->name);
    }

    public function test_profile_update_accepts_name_exactly_30_characters(): void
    {
        $user = User::factory()->create(['role' => 'customer', 'name' => 'Ten Cu']);
        $exactly30 = str_repeat('a', 30);

        $response = $this->actingAs($user)->postJson('/profile', ['name' => $exactly30]);

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertEquals($exactly30, $user->fresh()->name);
    }

    /**
     * Trang profile phải render sẵn (ẩn) đúng 4 thẻ <small> hiện lỗi tên/SĐT ở cả 2 form — cần thiết
     * để profile.js (showProfileFieldErrors) tìm thấy và điền lỗi vào khi submit qua AJAX, vì @error
     * của Blade chỉ có tác dụng lúc tải trang lại (submit cổ điển không JS), không tự kích hoạt được
     * khi form submit qua fetch (không tải lại trang).
     */
    public function test_profile_page_renders_inline_error_placeholders_for_name_and_phone(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
        foreach (['name-error-desktop', 'name-error-mobile', 'phone-error-desktop', 'phone-error-mobile'] as $id) {
            $response->assertSee('id="' . $id . '"', false);
        }
    }

    // ───────────────────────── Kiểm tra "ngầm" SĐT qua AJAX (profile.check_phone) ─────────────────────────

    public function test_check_phone_returns_valid_for_empty_value(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($user)->getJson('/profile/check-phone?phone=');

        $response->assertOk()->assertJson(['valid' => true]);
    }

    public function test_check_phone_returns_invalid_for_bad_format(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($user)->getJson('/profile/check-phone?phone=0388359vsdasd');

        $response->assertOk()->assertJson(['valid' => false]);
        $this->assertEquals('Số điện thoại không đúng định dạng.', $response->json('message'));
    }

    public function test_check_phone_returns_invalid_when_taken_by_another_account(): void
    {
        User::factory()->create(['role' => 'customer', 'phone' => '0912345678']);
        $me = User::factory()->create(['role' => 'customer', 'phone' => null]);

        $response = $this->actingAs($me)->getJson('/profile/check-phone?phone=0912345678');

        $response->assertOk()->assertJson(['valid' => false]);
        $this->assertEquals('Số điện thoại này đã được đăng ký bởi tài khoản khác.', $response->json('message'));
    }

    public function test_check_phone_returns_valid_for_own_unchanged_phone(): void
    {
        $user = User::factory()->create(['role' => 'customer', 'phone' => '0912345678']);

        $response = $this->actingAs($user)->getJson('/profile/check-phone?phone=0912345678');

        $response->assertOk()->assertJson(['valid' => true]);
    }

    public function test_check_phone_returns_valid_for_available_number(): void
    {
        $user = User::factory()->create(['role' => 'customer', 'phone' => null]);

        $response = $this->actingAs($user)->getJson('/profile/check-phone?phone=0912345678');

        $response->assertOk()->assertJson(['valid' => true]);
    }

    public function test_check_phone_requires_login(): void
    {
        $response = $this->getJson('/profile/check-phone?phone=0912345678');

        $response->assertStatus(401);
    }
}
