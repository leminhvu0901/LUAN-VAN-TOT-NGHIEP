<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Cấu hình thưởng của 1 combo, scope=combo, quan hệ 1-1 với Promotion. 2 thành phần thưởng ĐỘC
// LẬP nhau: giảm giá
class PromotionCombo extends Model
{
    // Tên bảng quản lý cấu hình combo trong DB
    protected $table = 'promotion_combos';

    // Cho phép điền hàng loạt cho tất cả các trường không
    protected $guarded = [];

    // Tự động ép kiểu dữ liệu khi lấy từ DB ra
    protected $casts = [
        'discount_value' => 'float',       // Giá trị giảm giá ép thành số thực
        'max_discount_amount' => 'float',  // Giới hạn giảm giá tối đa ép thành số thực
        'auto_add_gift' => 'boolean',      // Tự động thêm quà tặng ép thành kiểu boolean, true/false
    ];

    /**
     * Mối quan hệ Nhiều - Một (Belongs To) ngược lại với model Promotion.
     * Cấu hình combo này sẽ thuộc về một chương trình khuyến mãi (Promotion) cha.
     */
    public function promotion()
    {
        return $this->belongsTo(Promotion::class, 'promotion_id');
    }

    /**
     * Mối quan hệ Nhiều - Một (Belongs To) với model Product.
     * Xác định sản phẩm quà tặng (`giftProduct`) đi kèm của combo này.
     */
    public function giftProduct()
    {
        return $this->belongsTo(Product::class, 'gift_product_id');
    }

    /**
     * Kiểm tra xem cấu hình combo này có bật tính năng giảm giá tiền hay không.
     * Trả về true nếu cột discount_type khác null.
     */
    public function hasDiscount(): bool
    {
        return !is_null($this->discount_type);
    }

    /**
     * Kiểm tra xem cấu hình combo này có tặng kèm sản phẩm hay không.
     * Trả về true nếu cột gift_product_id khác null.
     */
    public function hasGift(): bool
    {
        return !is_null($this->gift_product_id);
    }
}
