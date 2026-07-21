<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdministrativeDivisionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_provinces_endpoint_returns_json(): void
    {
        Http::fake([
            'https://provinces.open-api.vn/api/v2/p/' => Http::response([
                ['code' => 79, 'name' => 'Thành phố Hồ Chí Minh', 'division_type' => 'thành phố trung ương', 'codename' => 'ho_chi_minh', 'phone_code' => 28, 'wards' => []],
                ['code' => 1, 'name' => 'Thành phố Hà Nội', 'division_type' => 'thành phố trung ương', 'codename' => 'ha_noi', 'phone_code' => 24, 'wards' => []],
            ], 200),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/administrative/provinces');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    ['code' => 79, 'name' => 'Thành phố Hồ Chí Minh'],
                    ['code' => 1, 'name' => 'Thành phố Hà Nội'],
                ],
            ]);
    }

    public function test_wards_endpoint_returns_json(): void
    {
        Http::fake([
            'https://provinces.open-api.vn/api/v2/p/79?depth=2' => Http::response([
                'code' => 79,
                'name' => 'Thành phố Hồ Chí Minh',
                'wards' => [
                    ['code' => 25747, 'name' => 'Phường Thủ Dầu Một', 'division_type' => 'phường', 'codename' => 'phuong_thu_dau_mot', 'province_code' => 79],
                    ['code' => 25750, 'name' => 'Phường Phú Lợi', 'division_type' => 'phường', 'codename' => 'phuong_phu_loi', 'province_code' => 79],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/administrative/provinces/79/wards');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    ['code' => 25747, 'name' => 'Phường Thủ Dầu Một', 'province_code' => 79],
                    ['code' => 25750, 'name' => 'Phường Phú Lợi', 'province_code' => 79],
                ],
            ]);
    }

    public function test_unauthenticated_user_cannot_access(): void
    {
        $response = $this->getJson('/administrative/provinces');
        $response->assertUnauthorized();
    }
}
