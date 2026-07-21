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
