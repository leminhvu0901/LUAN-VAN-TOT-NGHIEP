<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserAddress;
use App\Services\GeoapifyService;
use App\Services\ShippingQuoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

// Test cho ShippingQuoteService — luôn mock GeoapifyService (KHÔNG gọi API Geoapify thật). Xác nhận
// đúng thứ tự nguồn: Geoapify Routing API -> OpenRouteService (dự phòng) -> ước lượng theo quận/huyện,
// và công thức tính phí ship KHÔNG đổi so với trước khi thêm Geoapify Routing API.
class ShippingQuoteServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeAddress(array $overrides = []): UserAddress
    {
        return UserAddress::create(array_merge([
            'user_id' => User::factory()->create()->id,
            'fullname' => 'Nguyễn Văn A',
            'phone' => '0900000000',
            'province' => 'TP.HCM',
            'district' => 'Quận 8',
            'ward' => 'Phường 8',
            'specific_address' => '180 Cao Lỗ',
            'latitude' => 10.75,
            'longitude' => 106.68,
        ], $overrides));
    }

    public function test_distance_uses_google_routes_api_when_available(): void
    {
        $address = $this->makeAddress();
        $this->mock(GeoapifyService::class, function ($mock) {
            $mock->shouldReceive('drivingDistanceKm')->once()->andReturn(6.3);
        });

        $result = app(ShippingQuoteService::class)->distanceForWithSource($address);

        $this->assertSame(6.3, $result['distance_km']);
        $this->assertFalse($result['is_mock']);
    }

    public function test_distance_falls_back_to_openroute_when_google_fails(): void
    {
        config(['services.openroute.key' => 'test-ors-key']);
        $address = $this->makeAddress();
        $this->mock(GeoapifyService::class, function ($mock) {
            $mock->shouldReceive('drivingDistanceKm')->once()->andReturn(null);
        });
        Http::fake([
            'api.openrouteservice.org/*' => Http::response(['routes' => [['summary' => ['distance' => 8100]]]], 200),
        ]);

        $result = app(ShippingQuoteService::class)->distanceForWithSource($address);

        $this->assertSame(8.1, $result['distance_km']);
        $this->assertFalse($result['is_mock']);
    }

    public function test_distance_falls_back_to_district_estimate_when_both_apis_fail(): void
    {
        config(['services.openroute.key' => null]);
        $address = $this->makeAddress(['district' => 'Quận 8']);
        $this->mock(GeoapifyService::class, function ($mock) {
            $mock->shouldReceive('drivingDistanceKm')->once()->andReturn(null);
        });

        $result = app(ShippingQuoteService::class)->distanceForWithSource($address);

        $this->assertSame(1.5, $result['distance_km']); // ước lượng cố định cho Quận 8
        $this->assertTrue($result['is_mock']);
    }

    public function test_distance_skips_google_entirely_when_address_has_no_coordinates(): void
    {
        $address = $this->makeAddress(['latitude' => null, 'longitude' => null, 'district' => 'Quận 5']);
        $this->mock(GeoapifyService::class, function ($mock) {
            $mock->shouldNotReceive('drivingDistanceKm');
        });

        $result = app(ShippingQuoteService::class)->distanceForWithSource($address);

        $this->assertSame(2.8, $result['distance_km']); // ước lượng cố định cho Quận 5
        $this->assertTrue($result['is_mock']);
    }

    // distanceFor() (dùng bởi OrderService cho đơn hàng thật) phải trả ĐÚNG giá trị distance_km,
    // không đổi hành vi/chữ ký so với trước khi thêm Geoapify Routing API.
    public function test_distance_for_returns_plain_float_for_order_service(): void
    {
        $address = $this->makeAddress();
        $this->mock(GeoapifyService::class, function ($mock) {
            $mock->shouldReceive('drivingDistanceKm')->once()->andReturn(4.4);
        });

        $distance = app(ShippingQuoteService::class)->distanceFor($address);

        $this->assertIsFloat($distance);
        $this->assertSame(4.4, $distance);
    }

    // Công thức tính phí ship KHÔNG đổi: baseFee cho 2km đầu, + feePerKm cho mỗi km vượt quá.
    public function test_quote_fee_formula_unchanged(): void
    {
        \App\Models\Setting::setValue('shipping_base_fee', '15000', 'shipping', 'decimal');
        \App\Models\Setting::setValue('shipping_fee_per_km', '5000', 'shipping', 'decimal');
        \App\Models\Setting::setValue('free_shipping_minimum', '150000', 'shipping', 'decimal');

        $address = $this->makeAddress();
        $this->mock(GeoapifyService::class, function ($mock) {
            $mock->shouldReceive('drivingDistanceKm')->once()->andReturn(5.0); // > 2km
        });

        $quote = app(ShippingQuoteService::class)->quote($address, 50000, null);

        // 15.000 (2km đầu) + (5 - 2) * 5.000 = 30.000
        $this->assertSame(30000.0, $quote['shipping_fee']);
        $this->assertSame(5.0, $quote['distance_km']);
    }

    // Ép thời tiết (dùng khi trình diễn): KHÔNG gọi API Open-Meteo, áp thẳng mức % của nhóm đã chọn.
    public function test_weather_override_applies_without_calling_api(): void
    {
        \App\Models\Setting::setValue('weather_surcharge_enabled', '1', 'shipping', 'boolean');
        \App\Models\Setting::setValue('weather_override', 'heavy_rain', 'shipping', 'string');
        \App\Models\Setting::setValue('weather_heavy_rain_percent', '10', 'shipping', 'integer');
        Http::fake();

        $result = app(ShippingQuoteService::class)->weatherSurcharge(30000, 10.75, 106.68);

        $this->assertSame(3000.0, $result['fee']);   // 10% của 30.000
        $this->assertSame('heavy_rain', $result['group']);
        $this->assertSame('Mưa to', $result['label']);
        Http::assertNothingSent();                   // đã ép -> không hỏi dịch vụ thời tiết
    }

    // Chế độ tự động: đọc mã thời tiết thật rồi quy ra nhóm tương ứng.
    public function test_weather_auto_mode_reads_real_weather_code(): void
    {
        \App\Models\Setting::setValue('weather_surcharge_enabled', '1', 'shipping', 'boolean');
        \App\Models\Setting::setValue('weather_override', 'auto', 'shipping', 'string');
        \App\Models\Setting::setValue('weather_light_rain_percent', '5', 'shipping', 'integer');
        Http::fake(['api.open-meteo.com/*' => Http::response(['current' => ['weather_code' => 61]], 200)]);

        $result = app(ShippingQuoteService::class)->weatherSurcharge(20000, 10.75, 106.68);

        $this->assertSame(1000.0, $result['fee']);   // 5% của 20.000
        $this->assertSame('light_rain', $result['group']);
    }

    // Tắt phụ thu -> luôn bằng 0 dù đang ép mưa bão.
    public function test_weather_surcharge_disabled_returns_zero(): void
    {
        \App\Models\Setting::setValue('weather_surcharge_enabled', '0', 'shipping', 'boolean');
        \App\Models\Setting::setValue('weather_override', 'storm', 'shipping', 'string');
        Http::fake();

        $result = app(ShippingQuoteService::class)->weatherSurcharge(30000, 10.75, 106.68);

        $this->assertSame(0.0, $result['fee']);
        $this->assertSame('none', $result['group']);
    }

    // Miễn phí giao hàng (phí ship = 0) -> không thu phụ thu, vì phụ thu tính theo % của phí ship.
    public function test_no_weather_surcharge_when_shipping_is_free(): void
    {
        \App\Models\Setting::setValue('weather_surcharge_enabled', '1', 'shipping', 'boolean');
        \App\Models\Setting::setValue('weather_override', 'storm', 'shipping', 'string');
        Http::fake();

        $result = app(ShippingQuoteService::class)->weatherSurcharge(0, 10.75, 106.68);

        $this->assertSame(0.0, $result['fee']);
    }

    public function test_quote_is_free_shipping_above_threshold(): void
    {
        \App\Models\Setting::setValue('free_shipping_minimum', '150000', 'shipping', 'decimal');
        $address = $this->makeAddress();
        $this->mock(GeoapifyService::class, function ($mock) {
            $mock->shouldReceive('drivingDistanceKm')->once()->andReturn(5.0);
        });

        $quote = app(ShippingQuoteService::class)->quote($address, 200000, null);

        $this->assertEquals(0, $quote['shipping_fee']);
    }
}
