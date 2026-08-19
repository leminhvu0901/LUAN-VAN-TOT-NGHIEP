<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;

class User extends Authenticatable
{
    // Các traits mặc định của Laravel dành cho User
    use HasFactory, Notifiable;

    // $fillable: Danh sách các cột trong bảng `users` được phép "Gán dữ liệu hàng loạt" (Mass Assignment), ví dụ khi gọi User::create([...]), chỉ những cột được liệt kê ở đây mới được phép lưu vào Database, Điều này giúp bảo mật, ngăn chặn hacker chèn thêm dữ liệu vào các cột nhạy cảm
    protected $fillable = [
        'name',             // Tên người dùng
        'email',            // Email
        'avatar',           // Ảnh đại diện
        'password',         // Mật khẩu, đã mã hoá
        'phone',            // Số điện thoại
        'address',          // Địa chỉ
        'points',           // Điểm tích luỹ của khách hàng
        'membership_level', // Hạng thành viên, new, silver, gold, diamond
        'role',             // Quyền hạn, admin, user...
        'staff_type',       // Loại nhân viên khi role=staff, receptionist, delivery
        'is_active',        // Trạng thái tài khoản, 1: hoạt động, 0: khoá
        'lock_reason',      // Lý do khóa tài khoản
        'oauth_provider',   // Đăng nhập bằng mạng xã hội nào, Google, Facebook...
        'oauth_id',         // ID từ mạng xã hội
        'google_id',        // ID riêng của Google
    ];

    // $hidden: Danh sách các cột cần bị ẨN ĐI khi chuyển dữ liệu User thành dạng Mảng (Array) hoặc chuỗi JSON, Cực kỳ quan trọng để bảo mật! Không bao giờ để lộ mật khẩu và token ra ngoài API
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // $casts: Tự động ép kiểu dữ liệu cho các cột khi lấy từ Database ra hoặc ghi vào, Ở đây, cột 'password' được ép kiểu thành 'hashed', nghĩa là Laravel sẽ tự động băm (mã hoá), mật khẩu bằng Bcrypt mỗi khi bạn gán mật khẩu mới cho user
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    // Xác định hạng thành viên theo mốc điểm cấu hình hiện tại trong Setting
    public static function determineMembershipLevel(int $points): string
    {
        $diamondPoints = (int) Setting::getValue('membership_points_diamond', 5000);
        $goldPoints = (int) Setting::getValue('membership_points_gold', 2000);
        $silverPoints = (int) Setting::getValue('membership_points_silver', 500);

        if ($points >= $diamondPoints) {
            return 'diamond';
        } elseif ($points >= $goldPoints) {
            return 'gold';
        } elseif ($points >= $silverPoints) {
            return 'silver';
        }

        return 'new';
    }

    // Đồng bộ lại hạng thành viên cho toàn bộ tài khoản trong Database theo mốc điểm Setting mới
    public static function syncAllMembershipLevels(): void
    {
        $diamond = (int) Setting::getValue('membership_points_diamond', 5000);
        $gold = (int) Setting::getValue('membership_points_gold', 2000);
        $silver = (int) Setting::getValue('membership_points_silver', 500);

        self::query()->where('points', '>=', $diamond)->update(['membership_level' => 'diamond']);
        self::query()->where('points', '>=', $gold)->where('points', '<', $diamond)->update(['membership_level' => 'gold']);
        self::query()->where('points', '>=', $silver)->where('points', '<', $gold)->update(['membership_level' => 'silver']);
        self::query()->where('points', '<', $silver)->update(['membership_level' => 'new']);
    }

    // HÀM CỘNG ĐIỂM TÍCH LŨY VÀ TỰ ĐỘNG NÂNG HẠNG THÀNH VIÊN
    public function awardPoints(int|float $amount): void
    {
        $loyaltyEnabled = (bool) Setting::getValue('loyalty_enabled', true);
        if (!$loyaltyEnabled)
            return;

        $moneyPerPoint = (float) Setting::getValue('loyalty_money_per_point', 10000);
        if ($moneyPerPoint <= 0)
            return;

        $earned = (int) floor($amount / $moneyPerPoint);
        if ($earned <= 0)
            return;
        $total = (int) ($this->points ?? 0) + $earned;

        $level = self::determineMembershipLevel($total);

        // Cập nhật dữ liệu mới vào đối tượng $this
        $this->points = $total;
        $this->membership_level = $level;

        // Lưu xuống Database
        $this->save();

        // Ghi lại lịch sử cộng điểm vào file log hệ thống
        Log::info(
            "[Points] User #{$this->id} ({$this->name}): +{$earned} điểm -> tổng {$total} điểm | Hạng: {$level}"
        );
    }

    // Mối quan hệ: Các đơn hàng đã giao thành công mà nhân viên vận chuyển (staff_type=delivery) này phụ trách, dùng để thống kê số đơn giao được theo ngày/tuần/tháng/năm
    public function completedDeliveries()
    {
        return $this->hasMany(Order::class, 'delivery_staff_id')->where('status', 'completed');
    }

    // Mối quan hệ: Các đơn hàng mà nhân viên vận chuyển này giao thất bại (đơn bị hủy do giao không thành công).
    public function failedDeliveries()
    {
        return $this->hasMany(Order::class, 'delivery_staff_id')->whereNotNull('delivery_failed_at');
    }
}
