<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Đếm số lượt truy cập trang web trong ngày hôm nay.
 *
 * Mỗi phiên truy cập chỉ được tính 1 lần/ngày (dựa vào cờ lưu trong session), nên con số này là
 * "số lượt khách vào xem trong ngày" chứ không phải tổng số trang đã mở.
 */
class TrackDailyVisit
{
    public function handle(Request $request, Closure $next)
    {
        $today = today()->toDateString();

        if (session('last_visit_date') !== $today) {
            $this->increaseTodayCounter('daily_visits:' . $today);
            session(['last_visit_date' => $today]);
        }

        return $next($request);
    }

    /**
     * Cộng thêm 1 lượt truy cập.
     *
     * Cộng thẳng bằng câu lệnh UPDATE của database (COALESCE(value, 0) + 1) thay vì đọc giá trị cũ
     * ra PHP rồi ghi lại: nếu đọc trước ghi sau, hai người vào web cùng lúc sẽ cùng đọc được N rồi
     * cùng ghi N+1, làm mất 1 lượt. Để database tự cộng thì dù bao nhiêu người vào một lúc cũng
     * không sót lượt nào.
     */
    private function increaseTodayCounter(string $key): void
    {
        $updated = DB::table('settings')->where('key', $key)->update([
            'value' => DB::raw('COALESCE(value, 0) + 1'),
            'updated_at' => now(),
        ]);

        // Chưa có dòng nào cho ngày hôm nay lượt truy cập đầu
        if ($updated === 0) {
            try {
                Setting::setValue($key, 1, 'stats', 'integer');
                return;
            } catch (QueryException $e) {
                // Một request khác vừa kịp tạo dòng này trước cột key
                DB::table('settings')->where('key', $key)->update([
                    'value' => DB::raw('COALESCE(value, 0) + 1'),
                    'updated_at' => now(),
                ]);
            }
        }

        // Setting::getValue() cache vĩnh viễn nên phải xoá cache
        Cache::forget("setting.{$key}");
    }
}
