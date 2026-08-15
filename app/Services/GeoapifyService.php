<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeoapifyService
{
    // Lấy API Key Geoapify từ file cấu hình
    private function apiKey(): ?string
    {
        return config('services.geoapify.key'); // Đọc API key Geoapify cấu hình trong file config/services.php
    }

    // Tính khoảng cách lái xe thực tế km giữa hai tọa độ
    public function drivingDistanceKm(float $originLat, float $originLng, float $destLat, float $destLng): ?float
    {
        $key = $this->apiKey(); // Lấy API Key phục vụ gọi API bên ngoài
        if (!$key) {
            return null;
        }
        try {
            $response = Http::timeout(8)->get('https://api.geoapify.com/v1/routing', [ // Gọi HTTP GET đến dịch vụ Routing của Geoapify
                'waypoints' => "{$originLat},{$originLng}|{$destLat},{$destLng}",
                'mode' => 'drive',
                'apiKey' => $key,
            ]);
            if (!$response->successful()) {
                return null;
            }
            $meters = $response->json('features.0.properties.distance');
            if (is_numeric($meters)) {
                return round($meters / 1000, 1);
            } else {
                return null;
            }
        } catch (\Throwable) {
            return null;
        }
    }

    // Chuyển đổi địa chỉ dạng chữ thành tọa độ địa lý vĩ độ
    public function geocodeAddress(string $address, ?float $biasLat = null, ?float $biasLng = null): ?array
    {
        $key = $this->apiKey(); // Lấy API Key phục vụ gọi API bên ngoài
        if (!$key) {
            return null;
        }

        try {
            $params = [
                'text' => $address,
                'lang' => 'vi',
                'limit' => 1,
                'apiKey' => $key,
            ];
            if ($biasLat !== null && $biasLng !== null) {
                $params['bias'] = 'proximity:' . $biasLng . ',' . $biasLat;
            }
            $response = Http::timeout(8)->get('https://api.geoapify.com/v1/geocode/search', $params); // Gọi HTTP GET đến dịch vụ Geocoding của Geoapify
            if (!$response->successful()) {
                return null;
            }
            $props = $response->json('features.0.properties');
            if (!isset($props['lat'], $props['lon'])) {
                return null;
            }
            if (isset($props['formatted'])) {
                $formatted = $props['formatted'];
            } else {
                $formatted = '';
            }
            return [
                'lat' => (float) $props['lat'],
                'lng' => (float) $props['lon'],
                'confidence' => (float) ($props['rank']['confidence'] ?? 0),
                'formatted' => $formatted,
            ];
        } catch (\Throwable) {
            return null;
        }
    }
}
