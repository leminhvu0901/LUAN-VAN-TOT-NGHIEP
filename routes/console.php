<?php

use Illuminate\Support\Facades\Schedule;

// Dọn đơn MoMo "chờ thanh toán" bị treo quá 15 phút — chạy định kỳ để dọn kể cả khi không ai mở
// trang danh sách đơn (OrderController::index() cũng gọi việc này mỗi lần tải trang
// để có hiệu quả ngay, không cần đợi lịch). Cần Task Scheduler/cron thật gọi `schedule:run` mỗi
// phút để dòng này thực sự chạy tự động.
Schedule::command('orders:cancel-stale-pending')->everyFiveMinutes();
