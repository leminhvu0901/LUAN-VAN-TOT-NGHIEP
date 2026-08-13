<?php
namespace App\Services;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class AdministrativeDivisionService
{
    // Thời gian lưu trữ dữ liệu vào Cache: 60 giây * 60 phút * 24 giờ * 7 ngày = 7 ngày (tương đương 1 tuần)
    // Vì thông tin tỉnh/thành và phường/xã rất ít khi thay
    private const CACHE_TTL_SECONDS = 60 * 60 * 24 * 7;

    // Đường dẫn gốc (Base URL) của API công khai để lấy dữ
    private function baseUrl(): string
    {
        return config('services.administrative_division.base_url', 'https://provinces.open-api.vn/api/v2');
    }

    // lấy danh sách toàn bộ Tỉnh/Thành phố.
    public function provinces(): ?array
    {
        // Cache kết quả với key 'admin_division_provinces' trong
        return Cache::remember('admin_division_provinces', self::CACHE_TTL_SECONDS, function () {
            try {
                // Gọi API lấy danh sách tỉnh, thiết lập thời gian chờ
                $response = Http::timeout(8)->get($this->baseUrl() . '/p/');

                // Nếu gọi API thất bại (status code không phải 2xx), trả
                if (!$response->successful()) {
                    return null;
                }

                // Lấy dữ liệu dưới dạng JSON
                $data = $response->json();
                if (!is_array($data)) {
                    return null;
                }

                // Dùng Collection để lọc và chuẩn hóa dữ liệu, chỉ giữ
                return collect($data)
                    ->map(fn($p) => ['code' => (int) $p['code'], 'name' => (string) $p['name']])
                    ->values()->all();
            } catch (\Throwable) {
                // Nếu có bất kỳ lỗi nào xảy ra trong quá trình gọi API
                return null;
            }
        });
    }

    // LẤY DS PHƯỜNG XÃ DỰA THEO TỈNH
    public function wardsOf(int $provinceCode): ?array
    {
        // Cache kết quả theo từng tỉnh riêng biệt bằng cách đưa
        return Cache::remember("admin_division_wards_{$provinceCode}", self::CACHE_TTL_SECONDS, function () use ($provinceCode) {
            try {
                // Gọi API lấy thông tin chi tiết của Tỉnh kèm theo danh sách Phường/Xã (depth=2)
                $response = Http::timeout(8)->get($this->baseUrl() . "/p/{$provinceCode}", ['depth' => 2]);
                if (!$response->successful()) {
                    return null;
                }

                // Lấy mảng 'wards' từ kết quả trả về
                $wards = $response->json('wards');
                if (!is_array($wards)) {
                    return null;
                }

                // Chuẩn hóa dữ liệu từng phường xã (mã xã, tên xã, và mã
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

    /**
     * public: Cho phép gọi hàm từ bên ngoài.
     * findProvince(int $code): Tên hàm tìm kiếm thông tin của 1 Tỉnh/Thành cụ thể dựa vào mã code.
     * - Tham số int $code: Mã code của Tỉnh/Thành cần tìm.
     * Trả về kiểu dữ liệu: ?array (Mảng thông tin tỉnh gồm code và name, hoặc null nếu không tìm thấy).
     */
    public function findProvince(int $code): ?array
    {
        // Lấy danh sách toàn bộ các tỉnh (từ cache hoặc API)
        $provinces = $this->provinces(); // Gọi hàm provinces() lấy danh sách toàn bộ các tỉnh thành
        if ($provinces === null) {
            return null;
        }

        // Vòng lặp duyệt qua từng tỉnh để tìm tỉnh có mã code
        foreach ($provinces as $p) {
            if ($p['code'] === $code) {
                return $p; // Tìm thấy thì trả về ngay lập tức
            }
        }

        return null; // Không tìm thấy tỉnh nào có mã code này
    }

    /**
     * public: Cho phép gọi hàm từ bên ngoài.
     * findWard(int $wardCode, int $provinceCode): Tên hàm tìm kiếm 1 Phường/Xã và kiểm tra xem nó có thuộc đúng Tỉnh/Thành đó không.
     * - Tham số int $wardCode: Mã code của Phường/Xã cần tìm.
     * - Tham số int $provinceCode: Mã code của Tỉnh/Thành mà Phường/Xã đó phải thuộc về.
     * Trả về kiểu dữ liệu: ?array (Mảng thông tin phường xã nếu hợp lệ, ngược lại trả về null).
     */
    public function findWard(int $wardCode, int $provinceCode): ?array
    {
        // Lấy danh sách toàn bộ các phường/xã của Tỉnh/Thành đó
        $wards = $this->wardsOf($provinceCode); // Gọi hàm wardsOf() lấy danh sách phường xã của tỉnh
        if ($wards === null) {
            return null;
        }

        // Vòng lặp duyệt qua danh sách để tìm phường/xã khớp với
        foreach ($wards as $w) {
            if ($w['code'] === $wardCode) {
                return $w; // Tìm thấy phường xã hợp lệ thuộc đúng tỉnh này
            }
        }

        return null; // Không tìm thấy hoặc phường xã này không thuộc tỉnh đã cho
    }
}
