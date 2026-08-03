<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectStaffFromFrontend
{
    // Tự động chuyển hướng quản trị viên và nhân viên khỏi các trang dành cho khách hàng
    public function handle(Request $request, Closure $next)
    {
        // Cho qua nếu là khách vãng lai chưa đăng nhập
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Chuyển hướng nếu là quản trị viên Admin
        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        // Chuyển hướng nhân viên về đúng Dashboard tương ứng với công việc
        if ($user->role === 'staff') {
            if ($user->staff_type === 'delivery') {
                return redirect()->route('staff.delivery.dashboard');
            } else {
                return redirect()->route('staff.reception.dashboard');
            }
        }

        return $next($request);
    }
}
