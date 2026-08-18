<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class IsReceptionist
{
    // Xử lý yêu cầu truy cập
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
        if ($user->role === 'staff' && $user->staff_type === 'delivery') {
            return redirect()->route('staff.delivery.dashboard');
        }
        abort(403);
    }
}
