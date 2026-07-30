<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

// Lớp Middleware kiểm soát quyền truy cập vào khu vực Lễ tân/Thủ kho (Receptionist)
class IsReceptionist
{
    /**
     * Xử lý yêu cầu truy cập gửi đến phân hệ của nhân viên lễ tân/thủ kho
     */
    public function handle(Request $request, Closure $next)
    {
        // 1. Kiểm tra đăng nhập: Nếu chưa đăng nhập, chuyển hướng về trang đăng nhập ngay
        if (!Auth::check()) {
            return redirect('/login')->with('error', 'Vui lòng đăng nhập.');
        }

        $user = Auth::user();

        // 2. Kiểm tra vai trò: Cho phép Admin (để quản lý/test) hoặc Nhân viên lễ tân (staff + staff_type=receptionist) đi tiếp
        if ($user->role === 'admin' || ($user->role === 'staff' && $user->staff_type === 'receptionist')) {
            // Chia sẻ biến 'sidebarView' ra toàn bộ View để nạp đúng thanh Sidebar bên trái của nhân viên lễ tân
            View::share('sidebarView', 'backend.components.staff-reception-sidebar');
            return $next($request);
        }

        // 3. Nếu người dùng là Nhân viên giao hàng (delivery) cố truy cập nhầm vào khu vực lễ tân:
        // Tự động điều hướng họ về đúng trang Dashboard Giao hàng của họ
        if ($user->role === 'staff' && $user->staff_type === 'delivery') {
            return redirect()->route('staff.delivery.dashboard');
        }

        // 4. Các trường hợp còn lại (Ví dụ: Khách hàng thông thường): Chặn lại bằng lỗi 403
        abort(403);
    }
}
