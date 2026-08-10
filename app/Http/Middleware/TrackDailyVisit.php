<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;

/**
 * Đếm số lượt truy cập trang web trong ngày hôm nay 
 */
class TrackDailyVisit
{
    public function handle(Request $request, Closure $next)
    {
        $today = today()->toDateString();

        if (session('last_visit_date') !== $today) {
            $key = 'daily_visits:' . $today;
            $count = (int) Setting::getValue($key, 0);
            Setting::setValue($key, $count + 1, 'stats', 'integer'); // tăng lượt truy cập hôm nay
            session(['last_visit_date' => $today]);
        }

        return $next($request);
    }
}
