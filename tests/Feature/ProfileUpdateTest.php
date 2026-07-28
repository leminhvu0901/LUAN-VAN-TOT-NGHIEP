<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

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
}
