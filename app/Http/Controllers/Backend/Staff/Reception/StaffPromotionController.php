<?php

namespace App\Http\Controllers\Backend\Staff\Reception;

use App\Models\Promotion;

// Lễ tân chỉ xem khuyến mãi đang áp dụng (đọc-only) — không có store/update/destroy.
class StaffPromotionController
{
    public function index()
    {
        $now = now();

        $promotions = Promotion::query()
            ->where('is_active', true)
            // Lễ tân chỉ tạo đơn tại quầy -> ẩn khuyến mãi dành riêng cho giao hàng (không liên quan).
            ->whereIn('applies_to', ['all', 'pickup'])
            ->where(function ($query) use ($now) {
                $query->where('is_recurring', true)
                    ->orWhere(function ($q) use ($now) {
                        $q->where(function ($q2) use ($now) {
                            $q2->whereNull('start_at')->orWhere('start_at', '<=', $now);
                        })->where(function ($q2) use ($now) {
                            $q2->whereNull('end_at')->orWhere('end_at', '>=', $now);
                        });
                    });
            })
            ->orderByDesc('created_at')
            ->get();

        return view('backend.staff.reception.promotions.index', compact('promotions'));
    }
}
