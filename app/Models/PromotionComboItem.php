<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// 1 sản phẩm bắt buộc (kèm số lượng) trong danh sách combo (scope=combo) — nhiều dòng / promotion,
// khách phải mua ĐỦ TẤT CẢ các dòng của cùng 1 promotion
class PromotionComboItem extends Model
{
    // Tên bảng quản lý các sản phẩm trong combo
    protected $table = 'promotion_combo_items';

    // Cho phép điền hàng loạt cho tất cả các trường (không
    protected $guarded = [];

    /**
     * Mối quan hệ Nhiều - Một (Belongs To) với model Promotion.
     * Mỗi dòng sản phẩm yêu cầu trong combo này sẽ thuộc về một chương trình khuyến mãi (Promotion) cha.
     */
    public function promotion()
    {
        return $this->belongsTo(Promotion::class, 'promotion_id');
    }

    /**
     * Mối quan hệ Nhiều - Một (Belongs To) với model Product.
     * Xác định sản phẩm (Product) cụ thể nào bắt buộc phải có trong combo này.
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
