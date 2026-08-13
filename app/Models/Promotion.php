<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    // Bảng tương ứng trong CSDL
    protected $table = 'promotions';

    // Cho phép mass assignment (gán hàng loạt) cho tất cả các cột
    protected $guarded = [];

    // Tự động ép kiểu dữ liệu khi lấy từ CSDL ra
    protected $casts = [
        'is_recurring' => 'boolean', // Ép kiểu cột is_recurring thành boolean (true/false)
        'recurring_days' => 'array',   // Ép kiểu cột recurring_days thành mảng (Lưu JSON trong DB)
        'requires_staff_verification' => 'boolean', // Mã cần nhân viên xác nhận — không tự động áp
    ];

    /**
     * Quan hệ 1-N với bảng Order.
     * Một mã khuyến mãi có thể được áp dụng cho nhiều đơn hàng.
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'promotion_id');
    }

    // Sản phẩm được chọn khi scope='product'. Rỗng với các scope khác.
    public function products()
    {
        return $this->belongsToMany(Product::class, 'promotion_products');
    }

    // Danh mục được chọn khi scope='category'. Rỗng với các scope khác.
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'promotion_categories');
    }

    // Cấu hình thưởng khi scope='combo'. null với các scope khác.
    public function combo()
    {
        return $this->hasOne(PromotionCombo::class, 'promotion_id');
    }

    // Danh sách sản phẩm bắt buộc (kèm số lượng) của combo khi scope='combo'. Rỗng với các scope khác.
    public function comboItems()
    {
        return $this->hasMany(PromotionComboItem::class, 'promotion_id');
    }


    // KIỂM TRA MÃ GIẢM GIÁ CÓ HỢP LỆ KHÔNG
    public function checkValidity($user, $subtotal, ?string $deliveryType = null, ?int $totalQuantity = null)
    {
        // 0. KIỂM TRA KÊNH ÁP DỤNG (tại quầy / giao hàng) — mã
        if ($deliveryType && $this->applies_to && $this->applies_to !== 'all' && $this->applies_to !== $deliveryType) {
            $channelNames = ['pickup' => 'đơn tại quầy', 'delivery' => 'đơn giao hàng'];
            $requiredChannel = $channelNames[$this->applies_to] ?? $this->applies_to;
            return ['valid' => false, 'message' => "Mã giảm giá này chỉ áp dụng cho {$requiredChannel}."];
        }

        // 1. KIỂM TRA TRẠNG THÁI CHUNG
        // Nếu admin đã tắt mã này (is_active = 0) thì báo lỗi ngay
        if (!$this->is_active) {
            return ['valid' => false, 'message' => 'Mã giảm giá đã ngừng hoạt động.'];
        }

        // 2. KIỂM TRA THỜI GIAN ÁP DỤNG
        if ($this->is_recurring) {
            // TRƯỜNG HỢP A: Mã giảm giá lặp lại theo khung giờ/thứ
            $nowStr = now()->format('H:i:s');
            $currentDay = now()->dayOfWeekIso;

            // Kiểm tra xem hôm nay có nằm trong những ngày cho phép không
            if (is_array($this->recurring_days) && count($this->recurring_days) > 0) {
                if (!in_array($currentDay, $this->recurring_days)) {
                    // Chuyển mảng số thành chữ (VD: [1, 2] -> T2, T3) để
                    $days = [1 => 'T2', 2 => 'T3', 3 => 'T4', 4 => 'T5', 5 => 'T6', 6 => 'T7', 7 => 'CN'];
                    $dayLabels = array_map(function ($d) use ($days) {
                        return $days[$d] ?? '';
                    }, $this->recurring_days);
                    return ['valid' => false, 'message' => 'Mã này chỉ áp dụng vào ' . implode(', ', $dayLabels) . '.'];
                }
            }

            // Kiểm tra xem đã đến giờ bắt đầu chưa
            if ($this->recurring_start_time && $nowStr < $this->recurring_start_time) {
                return ['valid' => false, 'message' => 'Mã này chỉ áp dụng từ ' . \Carbon\Carbon::parse($this->recurring_start_time)->format('H:i') . '.'];
            }

            // Kiểm tra xem đã quá khung giờ kết thúc chưa
            if ($this->recurring_end_time && $nowStr > $this->recurring_end_time) {
                return ['valid' => false, 'message' => 'Mã này đã hết khung giờ áp dụng hôm nay (' . \Carbon\Carbon::parse($this->recurring_end_time)->format('H:i') . ').'];
            }
        } else {
            // TRƯỜNG HỢP B: Mã giảm giá áp dụng theo ngày cố định

            // Nếu đã vượt qua ngày giờ kết thúc (end_at)
            if ($this->end_at && now() > $this->end_at) {
                return ['valid' => false, 'message' => 'Mã giảm giá đã hết hạn.'];
            }

            // Nếu chưa đến ngày giờ bắt đầu (start_at)
            if ($this->start_at && now() < $this->start_at) {
                return ['valid' => false, 'message' => 'Mã giảm giá chưa đến thời gian áp dụng.'];
            }
        }

        // 3. KIỂM TRA ĐIỀU KIỆN ĐƠN TỐI THIỂU
        if ($this->min_order_amount && $subtotal < $this->min_order_amount) {
            return ['valid' => false, 'message' => 'Đơn hàng chưa đạt giá trị tối thiểu ' . number_format($this->min_order_amount, 0, ',', '.') . 'đ để dùng mã này.'];
        }

        // 3.5. KIỂM TRA SỐ LƯỢNG MÓN TỐI THIỂU (vd: mã combo yêu
        if ($this->min_quantity && (int) ($totalQuantity ?? 0) < $this->min_quantity) {
            return ['valid' => false, 'message' => "Đơn hàng cần mua tối thiểu {$this->min_quantity} món để dùng mã này."];
        }

        // 4. KIỂM TRA GIỚI HẠN TỔNG SỐ LƯỢT DÙNG TRÊN TOÀN HỆ THỐNG
        if ($this->usage_limit && $this->used_count >= $this->usage_limit) {
            return ['valid' => false, 'message' => 'Mã giảm giá đã hết lượt sử dụng.'];
        }

        // 5. KIỂM TRA ĐIỀU KIỆN HẠNG THÀNH VIÊN — mã gắn cho
        if ($this->apply_for && $this->apply_for !== 'all') {
            // Yêu cầu phải đăng nhập nếu mã này đòi hỏi hạng thành
            if (!$user) {
                return ['valid' => false, 'message' => 'Vui lòng đăng nhập để sử dụng mã ưu đãi này.'];
            }

            $userLevel = $user->membership_level ?? 'new';

            if ($userLevel !== $this->apply_for) {
                $levelNames = [
                    'new' => 'Mới',
                    'silver' => 'Bạc',
                    'gold' => 'Vàng',
                    'diamond' => 'Kim cương'
                ];
                $requiredName = $levelNames[$this->apply_for] ?? 'Cao cấp';
                return ['valid' => false, 'message' => "Mã giảm giá này chỉ dành riêng cho hạng thành viên {$requiredName}."];
            }
        }

        // 6. KIỂM TRA GIỚI HẠN SỐ LẦN DÙNG CỦA 1 TÀI KHOẢN (usage_limit_per_user, NULL = không giới hạn)
        // Đếm số đơn hàng user này đã dùng mã này mà chưa bị huỷ
        if ($user && $this->usage_limit_per_user !== null) {
            $usedCount = Order::query()
                ->where('promotion_id', $this->id)
                ->where('user_id', $user->id)
                ->where('status', '!=', 'cancelled')
                ->count();

            if ($usedCount >= $this->usage_limit_per_user) {
                $message = $this->usage_limit_per_user == 1
                    ? 'Bạn đã sử dụng mã giảm giá này rồi.'
                    : "Bạn đã dùng mã này tối đa {$this->usage_limit_per_user} lần.";
                return ['valid' => false, 'message' => $message];
            }
        }

        // NẾU VƯỢT QUA TẤT CẢ CÁC BÀI KIỂM TRA -> MÃ HỢP LỆ (PASS)
        return ['valid' => true, 'message' => 'Mã giảm giá hợp lệ.'];
    }
}
