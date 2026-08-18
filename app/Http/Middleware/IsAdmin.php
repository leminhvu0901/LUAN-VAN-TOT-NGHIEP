<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IsAdmin
{
    //Xử lý yêu cầu truy cập
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect('/login')->with('error', 'Vui lòng đăng nhập.');
        }
        if (Auth::user()->role === 'admin') {
            return $next($request);
        }
        if (Auth::user()->role === 'staff') {
            return redirect()->route(Auth::user()->staff_type === 'delivery' ? 'staff.delivery.dashboard' : 'staff.reception.dashboard');
        }
        abort(403);
    }
}
