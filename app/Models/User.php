<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    // Các traits mặc định của Laravel dành cho User
    use HasFactory, Notifiable;

    /**
     * $fillable: Danh sách các cột trong bảng `users` được phép "Gán dữ liệu hàng loạt" (Mass Assignment).
     * Ví dụ khi gọi User::create([...]), chỉ những cột được liệt kê ở đây mới được phép lưu vào Database.
     * Điều này giúp bảo mật, ngăn chặn hacker chèn thêm dữ liệu vào các cột nhạy cảm.
     */
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

    /**
     * $hidden: Danh sách các cột cần bị ẨN ĐI khi chuyển dữ liệu User thành dạng Mảng (Array) hoặc chuỗi JSON.
     * Cực kỳ quan trọng để bảo mật! Không bao giờ để lộ mật khẩu và token ra ngoài API.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * $casts: Tự động ép kiểu dữ liệu cho các cột khi lấy từ Database ra hoặc ghi vào.
     * Ở đây, cột 'password' được ép kiểu thành 'hashed', nghĩa là Laravel sẽ tự động băm (mã hoá) 
     * mật khẩu bằng Bcrypt mỗi khi bạn gán mật khẩu mới cho user.
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /**
     * HÀM CỘNG ĐIỂM TÍCH LŨY VÀ TỰ ĐỘNG NÂNG HẠNG THÀNH VIÊN
     * Tổng số tiền khách hàng đã thanh toán (đơn vị VNĐ)
     * Quy tắc: Cứ 1,000 VNĐ = 1 điểm.
     */
    public function awardPoints(int|float $amount): void
    {
        $loyaltyEnabled = (bool) \App\Models\Setting::getValue('loyalty_enabled', true);
        if (!$loyaltyEnabled)
            return;

        $moneyPerPoint = (float) \App\Models\Setting::getValue('loyalty_money_per_point', 10000);
        if ($moneyPerPoint <= 0)
            return;

        // Tính số điểm nhận được
        $earned = (int) floor($amount / $moneyPerPoint);

        // Nếu số tiền quá nhỏ không được 1 điểm nào thì thoát hàm luôn, return
        if ($earned <= 0)
            return;

        // Cộng dồn điểm mới vào tổng điểm hiện tại của user Nếu
        $total = (int) ($this->points ?? 0) + $earned;

        // TỰ ĐỘNG XẾP HẠNG THÀNH VIÊN DỰA TRÊN TỔNG ĐIỂM
        if ($total >= 5000)
            $level = 'diamond';
        elseif ($total >= 2000)
            $level = 'gold';
        elseif ($total >= 500)
            $level = 'silver';
        else
            $level = 'new';

        // Cập nhật dữ liệu mới vào đối tượng $this
        $this->points = $total;
        $this->membership_level = $level;

        // Lưu xuống Database
        $this->save();

        // Ghi lại lịch sử cộng điểm vào file log hệ thống
        \Illuminate\Support\Facades\Log::info(
            "[Points] User #{$this->id} ({$this->name}): +{$earned} điểm -> tổng {$total} điểm | Hạng: {$level}"
        );
    }

    /**
     * Mối quan hệ: Các đơn hàng đã giao thành công mà nhân viên vận chuyển (staff_type=delivery) này phụ trách.
     * Dùng để thống kê số đơn giao được theo ngày/tuần/tháng/năm.
     */
    public function completedDeliveries()
    {
        return $this->hasMany(Order::class, 'delivery_staff_id')->where('status', 'completed');
    }

    /**
     * Mối quan hệ: Các đơn hàng mà nhân viên vận chuyển này giao thất bại (đơn bị hủy do giao không thành công).
     */
    public function failedDeliveries()
    {
        return $this->hasMany(Order::class, 'delivery_staff_id')->whereNotNull('delivery_failed_at');
    }
}
