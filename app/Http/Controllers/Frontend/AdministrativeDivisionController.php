<?php

namespace App\Http\Controllers\Frontend;

use App\Services\AdministrativeDivisionService;

class AdministrativeDivisionController
{
    // Inject service xử lý dữ liệu địa chính vào controller
    public function __construct(private AdministrativeDivisionService $service)
    {
    }

    // LẤY TỈNH THÀNH PHỐ
    public function provinces()
    {
        $provinces = $this->service->provinces(); // Gọi service lấy danh sách tỉnh

        // Nếu service trả về null, lỗi nguồn dữ liệu
        if ($provinces === null) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể tải dữ liệu địa chỉ. Vui lòng thử lại.',
            ], 503);
        }

        return response()->json(['success' => true, 'data' => $provinces]);
    }

   // LẤY DS QUẬN HUYỆN
    public function wards(int $provinceCode)
    {
        $wards = $this->service->wardsOf($provinceCode); // Lọc phường/xã theo mã tỉnh

        // Nếu service trả về null mã tỉnh không hợp lệ hoặc lỗi
        if ($wards === null) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể tải dữ liệu địa chỉ. Vui lòng thử lại.',
            ], 503);
        }

        return response()->json(['success' => true, 'data' => $wards]);
    }
}
