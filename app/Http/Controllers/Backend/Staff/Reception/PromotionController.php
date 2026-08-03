<?php

namespace App\Http\Controllers\Backend\Staff\Reception;

use App\Models\Promotion;

/**
 * Controller xem danh sách Khuyến mãi dành cho Lễ tân.
 * Lễ tân chỉ có quyền xem các chương trình đang áp dụng (Read-only) để tư vấn cho khách tại quầy.
 * Không tích hợp các chức năng chỉnh sửa/thêm/xóa (CRUD) ở đây.
 */
class PromotionController
{
    /**
     * Hiển thị danh sách các chương trình khuyến mãi còn hiệu lực.
     */
    public function index()
    {
        $now = now(); // Lấy mốc thời gian hiện tại của hệ thống

        $promotions = Promotion::query()
            ->where('is_active', true) // Chỉ lấy các chương trình đang được bật kích hoạt
            // Lễ tân bán hàng tại quầy nên lọc bỏ các mã chỉ áp dụng riêng cho giao hàng (delivery)
            ->whereIn('applies_to', ['all', 'pickup']) 
            ->where(function ($query) use ($now) { // Lọc các chương trình khuyến mãi theo ngày hiệu lực
                $query->where('is_recurring', true) // Lấy các chương trình lặp lại (luôn luôn có hiệu lực)
                    ->orWhere(function ($q) use ($now) {
                        $q->where(function ($q2) use ($now) {
                            $q2->whereNull('start_at')->orWhere('start_at', '<=', $now); // Chưa bắt đầu hoặc đã bắt đầu trước hiện tại
                        })->where(function ($q2) use ($now) {
                            $q2->whereNull('end_at')->orWhere('end_at', '>=', $now); // Chưa kết thúc hoặc kết thúc sau hiện tại
                        });
                    });
            })
            ->orderByDesc('created_at') // Sắp xếp theo ngày tạo mới nhất lên trước
            ->get(); // Thực thi câu lệnh SQL SELECT lấy dữ liệu

        return view('backend.staff.reception.promotions.index', compact('promotions')); // Trả về view HTML hiển thị danh sách (không qua AJAX)
    }
}
