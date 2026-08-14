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
        // Kiểm tra đăng nhập: Nếu chưa đăng nhập, chuyển
        if (!Auth::check()) {
            return redirect('/login')->with('error', 'Vui lòng đăng nhập.');
        }

        // Kiểm tra vai trò: Nếu người dùng là Admin, cho phép
        if (Auth::user()->role === 'admin') {
            return $next($request);
        }

        // Nếu người dùng là Nhân viên, Staff nhưng cố tình
        if (Auth::user()->role === 'staff') {
            return redirect()->route(Auth::user()->staff_type === 'delivery' ? 'staff.delivery.dashboard' : 'staff.reception.dashboard');
        }

        // Các trường hợp còn lại Ví dụ: Khách hàng thường cố
        abort(403);
    }
}
