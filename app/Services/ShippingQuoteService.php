<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Support\Facades\Http;

class ShippingQuoteService
{
    public const MAX_DELIVERY_KM = 15;

    public function __construct(private GeoapifyService $geoapify)
    {
    }

    public function quote(UserAddress $address, float $subtotal, ?User $user): array
    {
        $distance = $this->distanceFor($address);
        
        $baseFee = (float) \App\Models\Setting::getValue('shipping_base_fee', 15000);
        $feePerKm = (float) \App\Models\Setting::getValue('shipping_fee_per_km', 5000);
        
        $threshold = match ($user?->membership_level) {
            'silver' => 120000,
            'gold' => 90000,
            'diamond' => 0,
            default => (float) \App\Models\Setting::getValue('free_shipping_minimum', 150000),
        };
        
        if ($distance <= 2) {
            $shippingFee = $baseFee;
        } else {
            $shippingFee = $baseFee + ($distance - 2) * $feePerKm;
        }
        
        $shippingFee = $subtotal >= $threshold ? 0 : round($shippingFee);

        return [
            'distance_km' => $distance,
            'shipping_fee' => $shippingFee,
            'weather_fee' => $shippingFee > 0 ? $this->weatherFee($address, $shippingFee) : 0,
            'free_ship_threshold' => $threshold,
        ];
    }

    public function distanceFor(UserAddress $address): float
    {
        return $this->distanceForWithSource($address)['distance_km'];
    }

    // Như distanceFor() nhưng kèm 'is_mock' (false = số km thật từ Geoapify Routing API/
    // OpenRouteService, true = ước lượng cố định theo quận/huyện) — CartController dùng để hiển thị
    // đúng trạng thái cho khách ở màn hình checkout. Thứ tự nguồn: Geoapify Routing API (chính) ->
    // OpenRouteService (dự phòng, giữ lại cho đến khi Geoapify được xác nhận ổn định) -> ước lượng cố
    // định (luôn có, không bao giờ để trống).
    public function distanceForWithSource(UserAddress $address): array
    {
        $storeLat = (float) \App\Models\Setting::getValue('store_latitude', 10.73809);
        $storeLng = (float) \App\Models\Setting::getValue('store_longitude', 106.67812);

        if ($address->latitude && $address->longitude) {
            $destLat = (float) $address->latitude;
            $destLng = (float) $address->longitude;

            $distance = $this->geoapify->drivingDistanceKm($storeLat, $storeLng, $destLat, $destLng);
            if ($distance !== null) {
                return ['distance_km' => $distance, 'is_mock' => false];
            }

            if (config('services.openroute.key')) {
                try {
                    $response = Http::timeout(8)
                        ->withToken(config('services.openroute.key'))
                        ->post('https://api.openrouteservice.org/v2/directions/driving-car', [
                            'coordinates' => [[$storeLng, $storeLat], [$destLng, $destLat]],
                        ])->throw()->json();
                    if (isset($response['routes'][0]['summary']['distance'])) {
                        return ['distance_km' => round($response['routes'][0]['summary']['distance'] / 1000, 1), 'is_mock' => false];
                    }
                } catch (\Throwable) {
                    // Rơi xuống ước lượng cố định theo quận/huyện bên dưới.
                }
            }
        }

        $district = mb_strtolower((string) $address->district);
        $estimate = match (true) {
            str_contains($district, '8') => 1.5,
            str_contains($district, '5') => 2.8,
            str_contains($district, '10') => 4.5,
            str_contains($district, '1'), str_contains($district, '4') => 5.2,
            str_contains($district, '7') => 4.8,
            str_contains($district, '3') => 5.8,
            str_contains($district, 'binh thanh'), str_contains($district, 'bình thạnh') => 7.5,
            default => 3.5,
        };

        return ['distance_km' => $estimate, 'is_mock' => true];
    }

    private function weatherFee(UserAddress $address, float $shippingFee): float
    {
        if (!$address->latitude || !$address->longitude) return 0;

        $enabled = \App\Models\Setting::getValue('weather_surcharge_enabled', '0');
        if ($enabled != '1') {
            return 0;
        }

        try {
            $code = Http::timeout(5)->get('https://api.open-meteo.com/v1/forecast', [
                'latitude' => $address->latitude,
                'longitude' => $address->longitude,
                'current' => 'weather_code',
            ])->throw()->json('current.weather_code');

            $percent = match (true) {
                in_array($code, [51, 53, 55, 61, 63, 80, 81], true) => (int)\App\Models\Setting::getValue('weather_light_rain_percent', 5),
                in_array($code, [65, 66, 67, 82], true) => (int)\App\Models\Setting::getValue('weather_heavy_rain_percent', 10),
                in_array($code, [71, 73, 75, 77, 85, 86, 95, 96, 99], true) => (int)\App\Models\Setting::getValue('weather_storm_percent', 15),
                default => 0,
            };
            
            if ($percent > 0) {
                return round($shippingFee * ($percent / 100));
            }
            return 0;
        } catch (\Throwable) {
            return 0;
        }
    }
}
