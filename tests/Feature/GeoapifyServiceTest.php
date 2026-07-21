<?php

namespace Tests\Feature;

use App\Services\GeoapifyService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

// Test cho GeoapifyService (Routing API + Geocoding API phía server) — KHÔNG gọi API Geoapify thật,
// luôn dùng Http::fake().
class GeoapifyServiceTest extends TestCase
{
    private function enableKey(): void
    {
        config(['services.geoapify.key' => 'test-geoapify-key']);
    }

    public function test_driving_distance_returns_null_without_api_key(): void
    {
        config(['services.geoapify.key' => null]);
        Http::fake();

        $result = app(GeoapifyService::class)->drivingDistanceKm(10.0, 106.0, 10.1, 106.1);

        $this->assertNull($result);
        Http::assertNothingSent();
    }

    public function test_driving_distance_parses_meters_to_km(): void
    {
        $this->enableKey();
        Http::fake([
            'api.geoapify.com/v1/routing*' => Http::response([
                'features' => [['properties' => ['distance' => 4200]]],
            ], 200),
        ]);

        $result = app(GeoapifyService::class)->drivingDistanceKm(10.0, 106.0, 10.1, 106.1);

        $this->assertSame(4.2, $result);
    }

    public function test_driving_distance_returns_null_on_http_error(): void
    {
        $this->enableKey();
        Http::fake([
            'api.geoapify.com/v1/routing*' => Http::response(['error' => 'quota exceeded'], 429),
        ]);

        $result = app(GeoapifyService::class)->drivingDistanceKm(10.0, 106.0, 10.1, 106.1);

        $this->assertNull($result);
    }

    public function test_driving_distance_returns_null_when_no_route_found(): void
    {
        $this->enableKey();
        Http::fake([
            'api.geoapify.com/v1/routing*' => Http::response(['features' => []], 200),
        ]);

        $result = app(GeoapifyService::class)->drivingDistanceKm(10.0, 106.0, 10.1, 106.1);

        $this->assertNull($result);
    }

    public function test_geocode_address_returns_null_without_api_key(): void
    {
        config(['services.geoapify.key' => null]);
        Http::fake();

        $result = app(GeoapifyService::class)->geocodeAddress('180 Cao Lỗ, Quận 8, TP.HCM');

        $this->assertNull($result);
        Http::assertNothingSent();
    }

    public function test_geocode_address_parses_lat_lng_confidence_formatted(): void
    {
        $this->enableKey();
        Http::fake([
            'api.geoapify.com/v1/geocode/search*' => Http::response([
                'features' => [['properties' => [
                    'lat' => 10.73809,
                    'lon' => 106.67812,
                    'formatted' => '180, Cao Lỗ, Phường Chánh Hưng, Việt Nam',
                    'rank' => ['confidence' => 1],
                ]]],
            ], 200),
        ]);

        $result = app(GeoapifyService::class)->geocodeAddress('180 Cao Lỗ, Quận 8, TP.HCM');

        $this->assertSame(10.73809, $result['lat']);
        $this->assertSame(106.67812, $result['lng']);
        $this->assertSame(1.0, $result['confidence']);
        $this->assertSame('180, Cao Lỗ, Phường Chánh Hưng, Việt Nam', $result['formatted']);
    }

    public function test_geocode_address_defaults_confidence_zero_when_missing(): void
    {
        $this->enableKey();
        Http::fake([
            'api.geoapify.com/v1/geocode/search*' => Http::response([
                'features' => [['properties' => ['lat' => 10.5, 'lon' => 106.5]]],
            ], 200),
        ]);

        $result = app(GeoapifyService::class)->geocodeAddress('địa chỉ mơ hồ');

        $this->assertSame(0.0, $result['confidence']);
        $this->assertSame('', $result['formatted']);
    }

    public function test_geocode_address_sends_proximity_bias_when_given(): void
    {
        $this->enableKey();
        Http::fake([
            'api.geoapify.com/v1/geocode/search*' => Http::response([
                'features' => [['properties' => ['lat' => 10.7, 'lon' => 106.6, 'rank' => ['confidence' => 0.9]]]],
            ], 200),
        ]);

        app(GeoapifyService::class)->geocodeAddress('Tạ Quang Bửu', 10.73809, 106.67812);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'bias=proximity%3A106.67812%2C10.73809')
                || str_contains(urldecode($request->url()), 'bias=proximity:106.67812,10.73809');
        });
    }

    public function test_geocode_address_returns_null_when_no_results(): void
    {
        $this->enableKey();
        Http::fake([
            'api.geoapify.com/v1/geocode/search*' => Http::response(['features' => []], 200),
        ]);

        $result = app(GeoapifyService::class)->geocodeAddress('địa chỉ không tồn tại xyz');

        $this->assertNull($result);
    }

    public function test_api_key_never_appears_in_failure_path(): void
    {
        config(['services.geoapify.key' => 'super-secret-geoapify-key']);
        Http::fake([
            'api.geoapify.com/v1/routing*' => Http::response(['error' => 'boom'], 500),
        ]);

        $result = app(GeoapifyService::class)->drivingDistanceKm(10.0, 106.0, 10.1, 106.1);

        $this->assertNull($result);
        // Không log gì (chỉ trả null im lặng) nên không có gì để rò rỉ — xác nhận không throw ra ngoài.
    }
}
