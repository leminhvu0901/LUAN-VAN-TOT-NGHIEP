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
        'password',         // Mật khẩu (đã mã hoá)
        'phone',            // Số điện thoại
        'address',          // Địa chỉ
        'points',           // Điểm tích luỹ của khách hàng
        'membership_level', // Hạng thành viên (new, silver, gold, diamond)
        'role',             // Quyền hạn (admin, user...)
        'is_active',        // Trạng thái tài khoản (1: hoạt động, 0: khoá)
        'oauth_provider',   // Đăng nhập bằng mạng xã hội nào (Google, Facebook...)
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
        // Tính số điểm nhận được (Chia 1000 và làm tròn xuống bằng hàm floor)
        $earned = (int) floor($amount / 1000);
        
        // Nếu số tiền quá nhỏ không được 1 điểm nào thì thoát hàm luôn (return)
        if ($earned <= 0) return;

        // Cộng dồn điểm mới vào tổng điểm hiện tại của user (Nếu điểm hiện tại là null thì tính là 0)
        $total = (int) ($this->points ?? 0) + $earned;

        // TỰ ĐỘNG XẾP HẠNG THÀNH VIÊN DỰA TRÊN TỔNG ĐIỂM
        // - Từ 5000 điểm trở lên: Kim Cương (diamond)
        // - Từ 2000 -> 4999 điểm: Vàng (gold)
        // - Từ 500 -> 1999 điểm: Bạc (silver)
        // - Dưới 500 điểm: Mới (new)
        if ($total >= 5000)      $level = 'diamond';
        elseif ($total >= 2000)  $level = 'gold';
        elseif ($total >= 500)   $level = 'silver';
        else                     $level = 'new';

        // Cập nhật dữ liệu mới vào đối tượng $this
        $this->points           = $total;
        $this->membership_level = $level;
        
        // Lưu xuống Database
        $this->save();

        // Ghi lại lịch sử cộng điểm vào file log hệ thống (storage/logs/laravel.log) 
        // để Admin có thể tra cứu khi cần thiết.
        \Illuminate\Support\Facades\Log::info(
            "[Points] User #{$this->id} ({$this->name}): +{$earned} điểm -> tổng {$total} điểm | Hạng: {$level}"
        );
    }
}
