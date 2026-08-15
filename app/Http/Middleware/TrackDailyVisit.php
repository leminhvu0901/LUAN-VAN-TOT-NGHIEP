<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TrackDailyVisit
{
    // Số ngày giữ lại số liệu, quá hạn sẽ bị dọn để bảng settings không phình ra mãi
    private const KEEP_DAYS = 30;

    public function handle(Request $request, Closure $next)
    {
        $today = today()->toDateString();

        if (session('last_visit_date') !== $today) {
            $this->increaseTodayCounter('daily_visits:' . $today);
            session(['last_visit_date' => $today]);
        }

        return $next($request);
    }

    // Cộng thêm 1 lượt truy cập
    private function increaseTodayCounter(string $key): void
    {
        $updated = DB::table('settings')->where('key', $key)->update([
            'value' => DB::raw('COALESCE(value, 0) + 1'),
            'updated_at' => now(),
        ]);

        // Chưa có dòng nào cho hôm nay
        if ($updated === 0) {
            try {
                Setting::setValue($key, 1, 'stats', 'integer');
                // Dòng của hôm nay vừa được tạo nghĩa là đã sang ngày mới, tiện thể dọn số liệu cũ
                $this->pruneOldCounters();
                Cache::forget("setting.{$key}");
                return;
            } catch (QueryException $e) {
                DB::table('settings')->where('key', $key)->update([
                    'value' => DB::raw('COALESCE(value, 0) + 1'),
                    'updated_at' => now(),
                ]);
            }
        }
        Cache::forget("setting.{$key}");
    }

    // Xoá số liệu truy cập quá hạn giữ lại.
    private function pruneOldCounters(): void
    {
        $cutoff = 'daily_visits:' . today()->subDays(self::KEEP_DAYS)->toDateString();
        $staleKeys = DB::table('settings')
            ->where('key', 'like', 'daily_visits:%')
            ->where('key', '<', $cutoff)
            ->pluck('key');
        if ($staleKeys->isEmpty()) {
            return;
        }
        DB::table('settings')->whereIn('key', $staleKeys)->delete();
        foreach ($staleKeys as $staleKey) {
            Cache::forget("setting.{$staleKey}");
        }
    }
}
