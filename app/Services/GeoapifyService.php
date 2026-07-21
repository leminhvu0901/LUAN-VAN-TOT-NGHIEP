<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

// Gọi Geoapify (Geocoding API + Routing API) phía server. Không chứa nghiệp vụ tính phí — chỉ trả
// về khoảng cách/tọa độ thô, nơi gọi (ShippingQuoteService/CartController) tự quyết định cách dùng.
// Mọi lỗi (thiếu key/timeout/HTTP lỗi/JSON không hợp lệ) đều trả về null một cách im lặng, nơi gọi
// tự rơi về dự phòng của chính nó — không bao giờ ném exception ra ngoài.
class GeoapifyService
{
    private function apiKey(): ?string
    {
        return config('services.geoapify.key');
    }

    // Khoảng cách lái xe thật (km, làm tròn 1 chữ số thập phân) giữa 2 tọa độ qua Geoapify Routing
    // API. null nếu thiếu key/lỗi mạng/HTTP lỗi/không có route.
    public function drivingDistanceKm(float $originLat, float $originLng, float $destLat, float $destLng): ?float
    {
        $key = $this->apiKey();
        if (!$key) {
            return null;
        }

        try {
            $response = Http::timeout(8)->get('https://api.geoapify.com/v1/routing', [
                'waypoints' => "{$originLat},{$originLng}|{$destLat},{$destLng}",
                'mode' => 'drive',
                'apiKey' => $key,
            ]);

            if (!$response->successful()) {
                return null;
            }

            $meters = $response->json('features.0.properties.distance');
            return is_numeric($meters) ? round($meters / 1000, 1) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    // Chuyển địa chỉ dạng chữ -> tọa độ thật qua Geoapify Geocoding API (forward). Trả về mảng
    // ['lat','lng','confidence','formatted'] hoặc null nếu thiếu key/lỗi mạng/HTTP lỗi/không tìm thấy.
    //   - confidence (0..1, từ rank.confidence): nơi gọi tự quyết ngưỡng để coi kết quả là "mơ hồ".
    //   - formatted: địa chỉ tham khảo Geoapify chuẩn hóa (chỉ để hiển thị/đối chiếu, KHÔNG ghi đè
    //     tỉnh/thành, phường/xã khách đã nhập).
    //   - $biasLat/$biasLng (tùy chọn): proximity bias — ưu tiên kết quả gần điểm này (vd quanh cửa
    //     hàng) để tên đường trùng ở nhiều tỉnh không nhảy nhầm ra tỉnh khác.
    public function geocodeAddress(string $address, ?float $biasLat = null, ?float $biasLng = null): ?array
    {
        $key = $this->apiKey();
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

            $response = Http::timeout(8)->get('https://api.geoapify.com/v1/geocode/search', $params);

            if (!$response->successful()) {
                return null;
            }

            $props = $response->json('features.0.properties');
            if (!isset($props['lat'], $props['lon'])) {
                return null;
            }

            return [
                'lat' => (float) $props['lat'],
                'lng' => (float) $props['lon'],
                'confidence' => (float) ($props['rank']['confidence'] ?? 0),
                'formatted' => $props['formatted'] ?? '',
            ];
        } catch (\Throwable) {
            return null;
        }
    }
}
