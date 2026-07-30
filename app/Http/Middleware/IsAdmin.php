<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Lớp Middleware kiểm soát quyền truy cập của Admin
class IsAdmin
{
    /**
     * Xử lý yêu cầu truy cập (Incoming Request) gửi đến các tuyến đường bảo vệ của Admin
     */
    public function handle(Request $request, Closure $next)
    {
        // 1. Kiểm tra đăng nhập: Nếu chưa đăng nhập, chuyển hướng ngay về trang đăng nhập kèm thông báo
        if (!Auth::check()) {
            return redirect('/login')->with('error', 'Vui lòng đăng nhập.');
        }

        // 2. Kiểm tra vai trò: Nếu người dùng là Admin, cho phép tiếp tục truy cập tuyến đường tiếp theo
        if (Auth::user()->role === 'admin') {
            return $next($request);
        }

        // 3. Nếu người dùng là Nhân viên (Staff) nhưng cố tình truy cập vào trang Admin:
        // Hệ thống sẽ tự động chuyển hướng họ về đúng Dashboard của họ thay vì đẩy ra trang đăng nhập
        if (Auth::user()->role === 'staff') {
            return redirect()->route(Auth::user()->staff_type === 'delivery' ? 'staff.delivery.dashboard' : 'staff.reception.dashboard');
        }

        // 4. Các trường hợp còn lại (Ví dụ: Khách hàng thường cố truy cập link admin): Chặn lại và hiển thị lỗi 403 (Không có quyền)
        abort(403);
    }
}
