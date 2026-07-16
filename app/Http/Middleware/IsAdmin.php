<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect('/login')->with('error', 'Vui lòng đăng nhập.');
        }

        if (Auth::user()->role === 'admin') {
            return $next($request);
        }

        // Đã đăng nhập nhưng không đủ quyền: đưa về đúng khu vực của họ thay vì văng về login
        if (Auth::user()->role === 'staff') {
            return redirect()->route('staff.dashboard');
        }

        abort(403);
    }
}
