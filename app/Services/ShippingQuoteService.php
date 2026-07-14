<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Support\Facades\Http;

class ShippingQuoteService
{
    public const MAX_DELIVERY_KM = 10;

    public function quote(UserAddress $address, float $subtotal, ?User $user): array
    {
        $distance = $this->distanceFor($address);
        $threshold = match ($user?->membership_level) {
            'silver' => 120000,
            'gold' => 90000,
            'diamond' => 0,
            default => 150000,
        };
        $shippingFee = $subtotal >= $threshold ? 0 : round($distance * 3000);

        return [
            'distance_km' => $distance,
            'shipping_fee' => $shippingFee,
            'weather_fee' => $shippingFee > 0 ? $this->weatherFee($address, $shippingFee) : 0,
            'free_ship_threshold' => $threshold,
        ];
    }

    public function distanceFor(UserAddress $address): float
    {
        if ($address->latitude && $address->longitude && config('services.openroute.key')) {
            try {
                $response = Http::timeout(8)
                    ->withToken(config('services.openroute.key'))
                    ->post('https://api.openrouteservice.org/v2/directions/driving-car', [
                        'coordinates' => [[106.67812, 10.73809], [(float) $address->longitude, (float) $address->latitude]],
                    ])->throw()->json();
                if (isset($response['routes'][0]['summary']['distance'])) {
                    return round($response['routes'][0]['summary']['distance'] / 1000, 1);
                }
            } catch (\Throwable) {
                // Fall back to the deterministic district estimate below.
            }
        }

        $district = mb_strtolower((string) $address->district);
        return match (true) {
            str_contains($district, '8') => 1.5,
            str_contains($district, '5') => 2.8,
            str_contains($district, '10') => 4.5,
            str_contains($district, '1'), str_contains($district, '4') => 5.2,
            str_contains($district, '7') => 4.8,
            str_contains($district, '3') => 5.8,
            str_contains($district, 'binh thanh'), str_contains($district, 'bình thạnh') => 7.5,
            default => 3.5,
        };
    }

    private function weatherFee(UserAddress $address, float $shippingFee): float
    {
        if (!$address->latitude || !$address->longitude) return 0;

        try {
            $code = Http::timeout(5)->get('https://api.open-meteo.com/v1/forecast', [
                'latitude' => $address->latitude,
                'longitude' => $address->longitude,
                'current' => 'weather_code',
            ])->throw()->json('current.weather_code');

            $rate = match (true) {
                in_array($code, [51, 53, 55, 61, 63, 80, 81], true) => 0.05,
                in_array($code, [65, 66, 67, 82], true) => 0.10,
                in_array($code, [71, 73, 75, 77, 85, 86, 95, 96, 99], true) => 0.15,
                default => 0,
            };
            return round($shippingFee * $rate);
        } catch (\Throwable) {
            return 0;
        }
    }
}
