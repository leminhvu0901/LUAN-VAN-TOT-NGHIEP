<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

// Lớp Middleware kiểm soát quyền truy cập vào khu vực Vận chuyển (Shipper)
class IsDelivery
{
    /**
     * Xử lý yêu cầu truy cập gửi đến phân hệ của nhân viên giao hàng (delivery)
     */
    public function handle(Request $request, Closure $next)
    {
        // 1. Kiểm tra đăng nhập: Nếu chưa đăng nhập, chuyển ngay về trang đăng nhập
        if (!Auth::check()) {
            return redirect('/login')->with('error', 'Vui lòng đăng nhập.');
        }

        $user = Auth::user();

        // 2. Kiểm tra vai trò: Cho phép Admin (để quản lý/test) hoặc Nhân viên giao hàng (staff + staff_type=delivery) đi tiếp
        if ($user->role === 'admin' || ($user->role === 'staff' && $user->staff_type === 'delivery')) {
            // Chia sẻ biến 'sidebarView' ra toàn bộ View để nạp đúng thanh Sidebar bên trái của nhân viên giao hàng
            View::share('sidebarView', 'backend.components.staff-delivery-sidebar');
            return $next($request);
        }

        // 3. Nếu người dùng là Lễ tân/Thủ kho (receptionist) cố truy cập nhầm vào khu giao hàng:
        // Tự động chuyển hướng họ về đúng trang Dashboard Lễ tân của họ
        if ($user->role === 'staff' && $user->staff_type === 'receptionist') {
            return redirect()->route('staff.reception.dashboard');
        }

        // 4. Các trường hợp còn lại: Chặn quyền truy cập bằng lỗi 403
        abort(403);
    }
}
