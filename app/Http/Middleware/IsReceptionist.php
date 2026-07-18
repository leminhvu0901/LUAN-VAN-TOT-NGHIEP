<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class IsReceptionist
{
    /**
     * Cho phép admin (xem/kiểm thử) và nhân viên staff_type=receptionist vào khu vực lễ tân.
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect('/login')->with('error', 'Vui lòng đăng nhập.');
        }

        $user = Auth::user();

        if ($user->role === 'admin' || ($user->role === 'staff' && $user->staff_type === 'receptionist')) {
            View::share('sidebarView', 'backend.components.staff-reception-sidebar');
            return $next($request);
        }

        // Đã đăng nhập nhưng sai khu vực: nhân viên vận chuyển bấm nhầm -> đưa về đúng khu vực của họ
        if ($user->role === 'staff' && $user->staff_type === 'delivery') {
            return redirect()->route('staff.delivery.dashboard');
        }

        abort(403);
    }
}
