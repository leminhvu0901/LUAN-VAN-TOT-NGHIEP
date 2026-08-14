<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Support\Facades\Http;

class ShippingQuoteService
{
    public const MAX_DELIVERY_KM = 15;

    public function __construct(private GeoapifyService $geoapify)
    {
    }

    // tinh tien ship theo khoang cach
    public function quote(UserAddress $address, float $subtotal, ?User $user): array
    {
        $distance = $this->distanceFor($address);

        $baseFee = (float) Setting::getValue('shipping_base_fee', 15000);
        $feePerKm = (float) Setting::getValue('shipping_fee_per_km', 5000);

        $threshold = match ($user?->membership_level) {
            'silver' => 120000,
            'gold' => 90000,
            'diamond' => 0,
            default => (float) Setting::getValue('free_shipping_minimum', 150000),
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

    // Tính khoảng cách giao hàng thật, có 3 tầng dự phòng
    public function distanceForWithSource(UserAddress $address): array
    {
        $storeLat = (float) Setting::getValue('store_latitude', 10.73809);
        $storeLng = (float) Setting::getValue('store_longitude', 106.67812);

        // Chỉ gọi API định vị nếu địa chỉ khách đã có sẵn tọa độ
        if ($address->latitude && $address->longitude) {
            $destLat = (float) $address->latitude;
            $destLng = (float) $address->longitude;

            // Ưu tiên 1: Geoapify Routing API, khoảng cách lái xe thực tế
            $distance = $this->geoapify->drivingDistanceKm($storeLat, $storeLng, $destLat, $destLng);
            if ($distance !== null) {
                return ['distance_km' => $distance, 'is_mock' => false];
            }

            // Ưu tiên 2: Geoapify lỗi thì thử OpenRouteService chỉ
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

        // Ưu tiên 3, luôn có: chưa có tọa độ hoặc cả 2 API đều
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

    // Nhãn hiển thị cho từng nhóm thời tiết dùng chung cho
    public const WEATHER_LABELS = [
        'none' => 'Bình thường',
        'light_rain' => 'Mưa nhỏ',
        'heavy_rain' => 'Mưa to',
        'storm' => 'Giông bão',
    ];

    // Quy mã thời tiết WMO của Open-Meteo về nhóm nội bộ.
    private function groupForCode($code): string
    {
        return match (true) {
            in_array($code, [51, 53, 55, 61, 63, 80, 81], true) => 'light_rain',
            in_array($code, [65, 66, 67, 82], true) => 'heavy_rain',
            in_array($code, [71, 73, 75, 77, 85, 86, 95, 96, 99], true) => 'storm',
            default => 'none',
        };
    }

    // Mức phụ thu, % của từng nhóm, lấy từ Cài đặt để admin
    private function percentForGroup(string $group): int
    {
        return match ($group) {
            'light_rain' => (int) Setting::getValue('weather_light_rain_percent', 5),
            'heavy_rain' => (int) Setting::getValue('weather_heavy_rain_percent', 10),
            'storm' => (int) Setting::getValue('weather_storm_percent', 15),
            default => 0,
        };
    }

    // thời tiết hiện tại đang được áp dụng
    public function currentWeatherGroup(?float $lat, ?float $lng): string
    {
        $override = (string) Setting::getValue('weather_override', 'auto');
        if ($override !== '' && $override !== 'auto') {
            return array_key_exists($override, self::WEATHER_LABELS) ? $override : 'none';
        }

        if (!$lat || !$lng) {
            return 'none';
        }

        try {
            $code = Http::timeout(5)->get('https://api.open-meteo.com/v1/forecast', [
                'latitude' => $lat,
                'longitude' => $lng,
                'current' => 'weather_code',
            ])->throw()->json('current.weather_code');

            return $this->groupForCode($code);
        } catch (\Throwable) {
            return 'none';
        }
    }

    // tính tiền thật
    public function weatherSurcharge(float $shippingFee, ?float $lat, ?float $lng): array
    {
        $none = ['fee' => 0.0, 'group' => 'none', 'label' => self::WEATHER_LABELS['none']];

        // Miễn phí giao hàng thì không thu phụ thu phụ thu tính
        if ($shippingFee <= 0) {
            return $none;
        }

        if (Setting::getValue('weather_surcharge_enabled', '0') != '1') {
            return $none;
        }

        $group = $this->currentWeatherGroup($lat, $lng);//thoi tiet hien tai dược ap dung
        $percent = $this->percentForGroup($group);//muc phu thu thoi tiet
        if ($percent <= 0) {
            return $none;
        }

        return [
            'fee' => round($shippingFee * ($percent / 100)),
            'group' => $group,
            'label' => self::WEATHER_LABELS[$group] ?? self::WEATHER_LABELS['none'],
        ];
    }

    // tinh phí thời tiết
    private function weatherFee(UserAddress $address, float $shippingFee): float
    {
        return (float) $this->weatherSurcharge(
            $shippingFee,
            $address->latitude ? (float) $address->latitude : null,
            $address->longitude ? (float) $address->longitude : null,
        )['fee'];
    }
}
