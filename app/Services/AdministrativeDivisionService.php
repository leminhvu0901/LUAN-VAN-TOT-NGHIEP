<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

// Danh sách tỉnh/thành + phường/xã CHÍNH THỨC (cấu trúc 2 cấp sau sáp nhập 2025, không còn quận/huyện)
// từ provinces.open-api.vn/api/v2 — API công khai, miễn phí, không cần key. Đây là NGUỒN TIN CẬY CHÍNH
// cho tỉnh/thành + phường/xã; Geoapify chỉ dùng cho tọa độ/địa chỉ tham khảo/khoảng cách (không được
// dùng để ghi trực tiếp vào 2 trường này — xem ProfileController::resolveAdministrativeArea()).
// Cache dài hạn vì ranh giới hành chính gần như không đổi giữa 2 lần deploy.
//"Dịch vụ xử lý đơn vị hành chính".
class AdministrativeDivisionService
{
    // Đường dẫn gốc (Base URL) của API công khai để lấy dữ liệu tỉnh/thành, phường/xã của Việt Nam
    private const BASE_URL = 'https://provinces.open-api.vn/api/v2';

    // Thời gian lưu trữ dữ liệu vào Cache: 60 giây * 60 phút * 24 giờ * 7 ngày = 7 ngày (tương đương 1 tuần)
    // Vì thông tin tỉnh/thành và phường/xã rất ít khi thay đổi nên cache lâu giúp tăng hiệu năng cho hệ thống, tránh gọi API ngoài liên tục
    private const CACHE_TTL_SECONDS = 60 * 60 * 24 * 7;

    /**
     * public: Cho phép gọi hàm từ bất kỳ đâu.
     * provinces(): Tên hàm lấy danh sách toàn bộ Tỉnh/Thành phố.
     * Trả về kiểu dữ liệu: ?array (Có thể trả về một mảng danh sách tỉnh, hoặc null nếu gọi API lỗi).
     */
    public function provinces(): ?array
    {
        // Cache kết quả với key 'admin_division_provinces' trong vòng 7 ngày
        return Cache::remember('admin_division_provinces', self::CACHE_TTL_SECONDS, function () {
            try {
                // Gọi API lấy danh sách tỉnh, thiết lập thời gian chờ tối đa (timeout) là 8 giây
                $response = Http::timeout(8)->get(self::BASE_URL . '/p/');

                // Nếu gọi API thất bại (status code không phải 2xx), trả về null
                if (!$response->successful()) {
                    return null;
                }

                // Lấy dữ liệu dưới dạng JSON
                $data = $response->json();
                if (!is_array($data)) {
                    return null;
                }

                // Dùng Collection để lọc và chuẩn hóa dữ liệu, chỉ giữ lại mã code (ép kiểu int) và tên tỉnh (ép kiểu string)
                return collect($data)
                    ->map(fn($p) => ['code' => (int) $p['code'], 'name' => (string) $p['name']])
                    ->values()->all();
            } catch (\Throwable) {
                // Nếu có bất kỳ lỗi nào xảy ra trong quá trình gọi API (mất mạng, API sập,...), bắt lỗi và trả về null
                return null;
            }
        });
    }

    /**
     * public: Cho phép gọi hàm từ bất kỳ đâu.
     * wardsOf(int $provinceCode): Tên hàm lấy danh sách Phường/Xã thuộc một Tỉnh/Thành cụ thể.
     * - Tham số int $provinceCode: Mã code của Tỉnh/Thành cần lấy Phường/Xã (ví dụ: 79 là TP.HCM).
     * Trả về kiểu dữ liệu: ?array (Có thể là mảng danh sách phường xã, hoặc null nếu lỗi).
     */
    public function wardsOf(int $provinceCode): ?array
    {
        // Cache kết quả theo từng tỉnh riêng biệt bằng cách đưa mã tỉnh vào key cache: 'admin_division_wards_{provinceCode}'
        return Cache::remember("admin_division_wards_{$provinceCode}", self::CACHE_TTL_SECONDS, function () use ($provinceCode) {
            try {
                // Gọi API lấy thông tin chi tiết của Tỉnh kèm theo danh sách Phường/Xã (depth=2)
                $response = Http::timeout(8)->get(self::BASE_URL . "/p/{$provinceCode}", ['depth' => 2]);
                if (!$response->successful()) {
                    return null;
                }

                // Lấy mảng 'wards' từ kết quả trả về
                $wards = $response->json('wards');
                if (!is_array($wards)) {
                    return null;
                }

                // Chuẩn hóa dữ liệu từng phường xã (mã xã, tên xã, và mã tỉnh cha của nó)
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

        // Vòng lặp duyệt qua từng tỉnh để tìm tỉnh có mã code trùng khớp
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

        // Vòng lặp duyệt qua danh sách để tìm phường/xã khớp với mã wardCode
        foreach ($wards as $w) {
            if ($w['code'] === $wardCode) {
                return $w; // Tìm thấy phường xã hợp lệ thuộc đúng tỉnh này
            }
        }

        return null; // Không tìm thấy hoặc phường xã này không thuộc tỉnh đã cho
    }
}
