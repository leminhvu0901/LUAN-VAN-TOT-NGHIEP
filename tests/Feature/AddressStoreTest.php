<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserAddress;
use App\Services\AdministrativeDivisionService;
use App\Services\GeoapifyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

// Test luồng LƯU địa chỉ (ProfileController::storeAddress) cho các phương thức xác định vị trí.
// Mock AdministrativeDivisionService và GeoapifyService — KHÔNG gọi API thật.
class AddressStoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Http::fake([
            'https://provinces.open-api.vn/api/v2/p/' => Http::response([
                ['code' => 79, 'name' => 'Thành phố Hồ Chí Minh', 'division_type' => 'thành phố trung ương', 'codename' => 'ho_chi_minh', 'phone_code' => 28, 'wards' => []],
            ], 200),
            'https://provinces.open-api.vn/api/v2/p/79?depth=2' => Http::response([
                'code' => 79,
                'name' => 'Thành phố Hồ Chí Minh',
                'wards' => [
                    ['code' => 25747, 'name' => 'Phường Chánh Hưng', 'division_type' => 'phường', 'codename' => 'phuong_chanh_hung', 'province_code' => 79],
                ],
            ], 200),
        ]);
    }

    private function basePayload(array $overrides = []): array
    {
        return array_merge([
            'fullname' => 'Nguyễn Văn A',
            'phone' => '0912345678',
            'province_code' => 79,
            'ward_code' => 25747,
            'specific_address' => '180 Cao Lỗ',
            'type' => 'home',
        ], $overrides);
    }

    // gps/map: có sẵn lat/lng từ frontend -> lưu thẳng, KHÔNG gọi geocode.
    public function test_map_mode_saves_provided_coordinates_without_geocoding(): void
    {
        $user = User::factory()->create();
        $this->mock(GeoapifyService::class, function ($mock) {
            $mock->shouldNotReceive('geocodeAddress');
        });

        $response = $this->actingAs($user)->postJson('/profile/address', $this->basePayload([
            'latitude' => 10.7383043,
            'longitude' => 106.6788227,
            'location_method' => 'map',
        ]));

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('user_addresses', [
            'user_id' => $user->id,
            'province' => 'Thành phố Hồ Chí Minh',
            'ward' => 'Phường Chánh Hưng',
            'latitude' => 10.7383043,
            'longitude' => 106.6788227,
            'location_method' => 'map',
        ]);
    }

    public function test_gps_mode_saves_coordinates_and_method(): void
    {
        $user = User::factory()->create();
        $this->mock(GeoapifyService::class, function ($mock) {
            $mock->shouldNotReceive('geocodeAddress');
        });

        $response = $this->actingAs($user)->postJson('/profile/address', $this->basePayload([
            'latitude' => 10.75,
            'longitude' => 106.68,
            'location_method' => 'gps',
        ]));

        $response->assertOk();
        $this->assertDatabaseHas('user_addresses', ['user_id' => $user->id, 'location_method' => 'gps']);
    }

    // manual: thiếu lat/lng -> backend forward-geocode. Confidence đủ cao -> lưu kèm tọa độ + formatted.
    public function test_manual_mode_geocodes_on_backend_when_no_coordinates(): void
    {
        $user = User::factory()->create();
        $this->mock(GeoapifyService::class, function ($mock) {
            $mock->shouldReceive('geocodeAddress')->once()->andReturn([
                'lat' => 10.7383043,
                'lng' => 106.6788227,
                'confidence' => 1.0,
                'formatted' => '180, Cao Lỗ, Phường Chánh Hưng, Việt Nam',
            ]);
        });

        $response = $this->actingAs($user)->postJson('/profile/address', $this->basePayload([
            'location_method' => 'manual',
        ]));

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('user_addresses', [
            'user_id' => $user->id,
            'latitude' => 10.7383043,
            'longitude' => 106.6788227,
            'location_method' => 'manual',
            'formatted_address' => '180, Cao Lỗ, Phường Chánh Hưng, Việt Nam',
        ]);
    }

    // manual: geocode confidence quá thấp -> 422, KHÔNG lưu.
    public function test_manual_mode_rejects_low_confidence_result(): void
    {
        $user = User::factory()->create();
        $this->mock(GeoapifyService::class, function ($mock) {
            $mock->shouldReceive('geocodeAddress')->once()->andReturn([
                'lat' => 21.0, 'lng' => 106.0, 'confidence' => 0.1, 'formatted' => 'đâu đó',
            ]);
        });

        $response = $this->actingAs($user)->postJson('/profile/address', $this->basePayload([
            'location_method' => 'manual',
        ]));

        $response->assertStatus(422)->assertJson(['success' => false]);
        $this->assertDatabaseCount('user_addresses', 0);
    }

    // manual: geocode không tìm thấy (null) -> 422, KHÔNG lưu.
    public function test_manual_mode_rejects_when_geocode_returns_null(): void
    {
        $user = User::factory()->create();
        $this->mock(GeoapifyService::class, function ($mock) {
            $mock->shouldReceive('geocodeAddress')->once()->andReturn(null);
        });

        $response = $this->actingAs($user)->postJson('/profile/address', $this->basePayload([
            'location_method' => 'manual',
        ]));

        $response->assertStatus(422);
        $this->assertDatabaseCount('user_addresses', 0);
    }

    // district tự sao từ ward (cột district NOT NULL vẫn được điền).
    public function test_district_is_copied_from_ward(): void
    {
        $user = User::factory()->create();
        $this->mock(GeoapifyService::class, function ($mock) {
            $mock->shouldNotReceive('geocodeAddress');
        });

        $this->actingAs($user)->postJson('/profile/address', $this->basePayload([
            'latitude' => 10.75,
            'longitude' => 106.68,
            'location_method' => 'map',
        ]))->assertOk();

        $this->assertDatabaseHas('user_addresses', [
            'user_id' => $user->id,
            'ward' => 'Phường Chánh Hưng',
            'district' => 'Phường Chánh Hưng',
        ]);
    }
}
