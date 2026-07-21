<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

// Danh sách tỉnh/thành + phường/xã CHÍNH THỨC (cấu trúc 2 cấp sau sáp nhập 2025, không còn quận/huyện)
// từ provinces.open-api.vn/api/v2 — API công khai, miễn phí, không cần key. Đây là NGUỒN TIN CẬY CHÍNH
// cho tỉnh/thành + phường/xã; Geoapify chỉ dùng cho tọa độ/địa chỉ tham khảo/khoảng cách (không được
// dùng để ghi trực tiếp vào 2 trường này — xem ProfileController::resolveAdministrativeArea()).
// Cache dài hạn vì ranh giới hành chính gần như không đổi giữa 2 lần deploy.
class AdministrativeDivisionService
{
    private const BASE_URL = 'https://provinces.open-api.vn/api/v2';
    private const CACHE_TTL_SECONDS = 60 * 60 * 24 * 7;

    // [['code'=>79,'name'=>'Thành phố Hồ Chí Minh'], ...] hoặc null nếu API lỗi/không gọi được.
    public function provinces(): ?array
    {
        return Cache::remember('admin_division_provinces', self::CACHE_TTL_SECONDS, function () {
            try {
                $response = Http::timeout(8)->get(self::BASE_URL . '/p/');
                if (!$response->successful()) {
                    return null;
                }
                $data = $response->json();
                if (!is_array($data)) {
                    return null;
                }

                return collect($data)
                    ->map(fn ($p) => ['code' => (int) $p['code'], 'name' => (string) $p['name']])
                    ->values()->all();
            } catch (\Throwable) {
                return null;
            }
        });
    }

    // [['code'=>25747,'name'=>'Phường Thủ Dầu Một','province_code'=>79], ...] hoặc null.
    public function wardsOf(int $provinceCode): ?array
    {
        return Cache::remember("admin_division_wards_{$provinceCode}", self::CACHE_TTL_SECONDS, function () use ($provinceCode) {
            try {
                $response = Http::timeout(8)->get(self::BASE_URL . "/p/{$provinceCode}", ['depth' => 2]);
                if (!$response->successful()) {
                    return null;
                }
                $wards = $response->json('wards');
                if (!is_array($wards)) {
                    return null;
                }

                return collect($wards)
                    ->map(fn ($w) => [
                        'code' => (int) $w['code'],
                        'name' => (string) $w['name'],
                        'province_code' => $provinceCode,
                    ])
                    ->values()->all();
            } catch (\Throwable) {
                return null;
            }
        });
    }

    // Tra cứu 1 tỉnh/thành theo code — dùng để BACKEND tự lấy tên chuẩn (không tin tên frontend gửi).
    public function findProvince(int $code): ?array
    {
        $provinces = $this->provinces();
        if ($provinces === null) {
            return null;
        }

        foreach ($provinces as $p) {
            if ($p['code'] === $code) {
                return $p;
            }
        }

        return null;
    }

    // Tra cứu 1 phường/xã theo code, PHẢI thuộc đúng province_code — chặn trường hợp frontend gửi
    // ward_code hợp lệ nhưng thuộc tỉnh khác với province_code đã chọn.
    public function findWard(int $wardCode, int $provinceCode): ?array
    {
        $wards = $this->wardsOf($provinceCode);
        if ($wards === null) {
            return null;
        }

        foreach ($wards as $w) {
            if ($w['code'] === $wardCode) {
                return $w;
            }
        }

        return null;
    }
}
