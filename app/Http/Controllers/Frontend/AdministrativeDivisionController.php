<?php

namespace App\Http\Controllers\Frontend;

use App\Services\AdministrativeDivisionService;

// Cấp dữ liệu tỉnh/thành + phường/xã cho form địa chỉ (checkout.blade.php) — AJAX, KHÔNG hard-code
// danh sách ở Blade/JS. Chỉ đọc, không cần CSRF riêng (route GET trong nhóm auth có sẵn).
class AdministrativeDivisionController
{
    public function __construct(private AdministrativeDivisionService $service)
    {
    }

    public function provinces()
    {
        $provinces = $this->service->provinces();
        if ($provinces === null) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể tải dữ liệu địa chỉ. Vui lòng thử lại.',
            ], 503);
        }

        return response()->json(['success' => true, 'data' => $provinces]);
    }

    public function wards(int $provinceCode)
    {
        $wards = $this->service->wardsOf($provinceCode);
        if ($wards === null) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể tải dữ liệu địa chỉ. Vui lòng thử lại.',
            ], 503);
        }

        return response()->json(['success' => true, 'data' => $wards]);
    }
}
