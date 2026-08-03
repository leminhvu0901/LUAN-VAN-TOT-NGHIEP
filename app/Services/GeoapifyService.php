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

    // Tính khoảng cách lái xe thực tế (km) giữa hai tọa độ
    public function drivingDistanceKm(float $originLat, float $originLng, float $destLat, float $destLng): ?float
    {
        $key = $this->apiKey(); // Lấy API Key phục vụ gọi API bên ngoài
        if (!$key) {
            return null;
        }

        try {
            // Gọi API tính toán tuyến đường (Routing API) của Geoapify
            $response = Http::timeout(8)->get('https://api.geoapify.com/v1/routing', [ // Gọi HTTP GET đến dịch vụ Routing của Geoapify
                'waypoints' => "{$originLat},{$originLng}|{$destLat},{$destLng}",
                'mode' => 'drive',
                'apiKey' => $key,
            ]);

            if (!$response->successful()) {
                return null;
            }

            // Lấy khoảng cách đơn vị mét từ phản hồi JSON
            $meters = $response->json('features.0.properties.distance');

            // Đổi từ mét sang km và làm tròn 1 chữ số thập phân
            if (is_numeric($meters)) {
                return round($meters / 1000, 1);
            } else {
                return null;
            }
        } catch (\Throwable) {
            return null;
        }
    }

    // Chuyển đổi địa chỉ dạng chữ thành tọa độ địa lý (vĩ độ - kinh độ)
    public function geocodeAddress(string $address, ?float $biasLat = null, ?float $biasLng = null): ?array
    {
        $key = $this->apiKey(); // Lấy API Key phục vụ gọi API bên ngoài
        if (!$key) {
            return null;
        }

        try {
            // Chuẩn bị tham số cho API tra cứu địa chỉ (Geocoding API)
            $params = [
                'text' => $address,
                'lang' => 'vi',
                'limit' => 1,
                'apiKey' => $key,
            ];

            // Thêm tham số định vị ưu tiên theo khu vực quanh tọa độ mốc nếu có
            if ($biasLat !== null && $biasLng !== null) {
                $params['bias'] = 'proximity:' . $biasLng . ',' . $biasLat;
            }

            // Gọi Geocoding API của Geoapify để tìm tọa độ
            $response = Http::timeout(8)->get('https://api.geoapify.com/v1/geocode/search', $params); // Gọi HTTP GET đến dịch vụ Geocoding của Geoapify

            // Trả về null nếu phản hồi HTTP từ server bị lỗi
            if (!$response->successful()) {
                return null;
            }

            // Lấy danh sách thuộc tính kết quả tìm kiếm địa chỉ từ JSON
            $props = $response->json('features.0.properties');

            // Bắt buộc phải có đủ tọa độ Vĩ độ (lat) và Kinh độ (lon)
            if (!isset($props['lat'], $props['lon'])) {
                return null;
            }

            // Lấy địa chỉ đã định dạng chuẩn từ kết quả API
            if (isset($props['formatted'])) {
                $formatted = $props['formatted'];
            } else {
                $formatted = '';
            }

            // Trả về mảng chứa tọa độ, độ tin cậy và địa chỉ chuẩn hóa
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
