<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectStaffFromFrontend
{
    // Tự động chuyển hướng quản trị viên và nhân viên khỏi
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return $next($request);
        }
        $user = Auth::user();
        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }
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
