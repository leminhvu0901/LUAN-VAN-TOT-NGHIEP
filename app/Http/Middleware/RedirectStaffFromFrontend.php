<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Admin/nhân viên đăng nhập rồi vẫn xem được giao diện khách hàng (trang chủ, sản phẩm, giỏ hàng,
// checkout...) vì các route này không có middleware chặn theo role — chỉ khách/thường dân mới nên
// đứng ở đây. Chặn tại đây, tự đẩy về đúng khu vực quản trị của họ. Khách vãng lai (chưa đăng nhập)
// và khách hàng (role=customer) không bị ảnh hưởng.
class RedirectStaffFromFrontend
{
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
            return redirect()->route($user->staff_type === 'delivery' ? 'staff.delivery.dashboard' : 'staff.reception.dashboard');
        }

        return $next($request);
    }
}
