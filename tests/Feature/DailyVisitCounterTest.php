<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

// Bộ đếm "Lượt truy cập hôm nay" hiển thị ở trang chủ.
// Mỗi phiên chỉ được tính 1 lượt/ngày, và con số phải thật sự tăng (trước đây middleware đếm lượt
// có tồn tại nhưng chưa được đăng ký nên không bao giờ chạy, số luôn đứng yên ở 0).
class DailyVisitCounterTest extends TestCase
{
    use RefreshDatabase;

    private function todayKey(): string
    {
        return 'daily_visits:' . today()->toDateString();
    }

    private function currentCount(): int
    {
        Cache::forget('setting.' . $this->todayKey());
        return (int) Setting::getValue($this->todayKey(), 0);
    }

    public function test_visiting_home_page_increases_today_counter(): void
    {
        $this->assertSame(0, $this->currentCount());

        $this->get('/')->assertOk();

        $this->assertSame(1, $this->currentCount());
    }

    public function test_same_session_is_counted_only_once_per_day(): void
    {
        $this->get('/')->assertOk();
        $this->get('/')->assertOk();
        $this->get('/products')->assertOk();

        $this->assertSame(1, $this->currentCount(), 'Cùng một phiên truy cập nhiều trang chỉ tính 1 lượt');
    }

    public function test_each_new_session_adds_one_visit(): void
    {
        $this->get('/')->assertOk();

        $this->flushSession(); // giả lập một người dùng khác (phiên mới)
        $this->get('/')->assertOk();

        $this->assertSame(2, $this->currentCount());
    }

    public function test_home_page_shows_the_counter_value(): void
    {
        Setting::setValue($this->todayKey(), 41, 'stats', 'integer');

        // Lượt truy cập của chính request này được cộng thêm -> hiển thị 42
        $this->get('/')->assertOk()->assertSee('42', false);
    }

    // Cộng dồn phải do database tự thực hiện, không đọc-rồi-ghi ở PHP. Nếu đọc trước ghi sau thì
    // hai người vào cùng lúc sẽ cùng đọc N rồi cùng ghi N+1 -> mất 1 lượt.
    public function test_counter_uses_atomic_database_increment(): void
    {
        Setting::setValue($this->todayKey(), 10, 'stats', 'integer');

        // Ghi đè trực tiếp trong DB (không qua Setting::setValue) để cache vẫn giữ giá trị cũ là 10.
        DB::table('settings')->where('key', $this->todayKey())->update(['value' => '99']);

        $this->get('/')->assertOk();

        // Cộng dồn đọc từ DB nên phải ra 100. Nếu đọc từ cache (10) rồi ghi lại sẽ chỉ ra 11.
        $this->assertSame(100, $this->currentCount());
    }

    private function seedVisitRow(string $date, int $value = 99): void
    {
        DB::table('settings')->insert([
            'key' => 'daily_visits:' . $date,
            'value' => $value,
            'type' => 'integer',
            'group' => 'stats',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // Số liệu quá hạn giữ lại bị dọn ngay lúc tạo dòng đếm cho ngày mới, nhờ vậy không cần cron
    // mà bảng settings vẫn không phình ra vô hạn theo thời gian.
    public function test_old_counters_are_pruned_when_a_new_day_starts(): void
    {
        $expired = today()->subDays(45)->toDateString();
        $kept = today()->subDays(10)->toDateString();
        $this->seedVisitRow($expired);
        $this->seedVisitRow($kept);

        $this->get('/')->assertOk();

        $this->assertDatabaseMissing('settings', ['key' => 'daily_visits:' . $expired]);
        $this->assertDatabaseHas('settings', ['key' => 'daily_visits:' . $kept]);
        $this->assertSame(1, $this->currentCount());
    }

    // Việc dọn chỉ được đụng vào khoá daily_visits, các thiết lập vận hành phải còn nguyên.
    public function test_pruning_does_not_touch_other_settings(): void
    {
        Setting::setValue('store_open_time', '08:00', 'general', 'string');
        $this->seedVisitRow(today()->subDays(45)->toDateString());

        $this->get('/')->assertOk();

        $this->assertDatabaseHas('settings', ['key' => 'store_open_time']);
    }
}
