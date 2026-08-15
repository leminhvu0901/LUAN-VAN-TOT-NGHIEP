<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes; // Cho phép xóa mềm, không xóa hẳn khỏi DB, chỉ đánh dấu deleted_at
    protected $table = 'orders';
    protected $guarded = []; // Cho phép điền hàng loạt cho tất cả các trường, không khóa trường nào

    // Tự động ép kiểu các cột lưu ngày giờ từ chuỗi, string
    protected $casts = [
        'paid_at' => 'datetime',                // Thời điểm thanh toán thành công
        'inventory_reserved_at' => 'datetime',  // Thời điểm khóa/giữ hàng trong kho tạm thời
        'inventory_released_at' => 'datetime',  // Thời điểm hoàn trả lại kho, nếu đơn bị hủy
        'cod_settled_at' => 'datetime',         // Thời điểm bàn giao tiền COD giữa shipper và lễ tân
        'refunded_at' => 'datetime',            // Thời điểm hoàn tiền cho khách, hoàn qua VNPay
        'completed_at' => 'datetime',           // Thời điểm đơn hàng chuyển sang trạng thái hoàn thành
    ];

    /**
     * Mối quan hệ: Một đơn hàng chứa nhiều Chi tiết dòng sản phẩm (OrderItem - trà sữa, topping, size...)
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    /**
     * Mối quan hệ: Đơn hàng thuộc về một Khách hàng đặt mua (User)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Mối quan hệ: Đơn hàng được phân công cho một Nhân viên vận chuyển đi giao (User vai trò delivery)
     */
    public function deliveryStaff()
    {
        return $this->belongsTo(User::class, 'delivery_staff_id');
    }
}
