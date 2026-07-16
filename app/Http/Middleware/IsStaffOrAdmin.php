<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class IsStaffOrAdmin
{
    /**
     * Cho phép cả nhân viên và admin vào khu vực /staff/*.
     * Cũng thiết lập sidebar riêng cho khu vực này qua View::share, để
     * layouts/app.blade.php dùng chung không cần một layout riêng cho staff.
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect('/login')->with('error', 'Vui lòng đăng nhập.');
        }

        if (!in_array(Auth::user()->role, ['staff', 'admin'], true)) {
            abort(403);
        }

        // Chia sẻ sidebar cho giao diện nhân viên
        View::share('sidebarView', 'backend.components.staff-sidebar');

        return $next($request);
    }
}
