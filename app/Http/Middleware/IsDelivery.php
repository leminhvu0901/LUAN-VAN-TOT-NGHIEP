<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class IsDelivery
{
    /**
     * Cho phép admin (xem/kiểm thử) và nhân viên staff_type=delivery vào khu vực vận chuyển.
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect('/login')->with('error', 'Vui lòng đăng nhập.');
        }

        $user = Auth::user();

        if ($user->role === 'admin' || ($user->role === 'staff' && $user->staff_type === 'delivery')) {
            View::share('sidebarView', 'backend.components.staff-delivery-sidebar');
            return $next($request);
        }

        // Đã đăng nhập nhưng sai khu vực: lễ tân bấm nhầm -> đưa về đúng khu vực của họ
        if ($user->role === 'staff' && $user->staff_type === 'receptionist') {
            return redirect()->route('staff.reception.dashboard');
        }

        abort(403);
    }
}
