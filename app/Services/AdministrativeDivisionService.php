<?php
namespace App\Services;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class AdministrativeDivisionService
{
    //7 ngày
    private const CACHE_TTL_SECONDS = 60 * 60 * 24 * 7;

    // Đường dẫn gốc
    private function baseUrl(): string
    {
        return config('services.administrative_division.base_url', 'https://provinces.open-api.vn/api/v2');
    }

    // lấy danh sách toàn bộ Tỉnh
    public function provinces(): ?array
    {
        return Cache::remember('admin_division_provinces', self::CACHE_TTL_SECONDS, function () {
            try {
                $response = Http::timeout(8)->get($this->baseUrl() . '/p/');
                if (!$response->successful()) {
                    return null;
                }
                $data = $response->json();
                if (!is_array($data)) {
                    return null;
                }
                return collect($data)
                    ->map(fn($p) => ['code' => (int) $p['code'], 'name' => (string) $p['name']])
                    ->values()->all();
            } catch (\Throwable) {
                return null;
            }
        });
    }

    // LẤY DS PHƯỜNG XÃ DỰA THEO TỈNH
    public function wardsOf(int $provinceCode): ?array
    {
        return Cache::remember("admin_division_wards_{$provinceCode}", self::CACHE_TTL_SECONDS, function () use ($provinceCode) {
            try {
                $response = Http::timeout(8)->get($this->baseUrl() . "/p/{$provinceCode}", ['depth' => 2]);
                if (!$response->successful()) {
                    return null;
                }
                $wards = $response->json('wards');
                if (!is_array($wards)) {
                    return null;
                }
                return collect($wards)
                    ->map(fn($w) => [
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

    //Tên hàm tìm kiếm thông tin của 1 Tỉnh
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

    //tìm kiếm 1 Phường/Xã
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
