<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// 1 sản phẩm bắt buộc (kèm số lượng) trong danh sách combo (scope=combo) — nhiều dòng / promotion,
// khách phải mua ĐỦ TẤT CẢ các dòng của cùng 1 promotion mới tính là đã mua đủ combo đó.
class PromotionComboItem extends Model
{
    protected $table = 'promotion_combo_items';
    protected $guarded = [];

    public function promotion()
    {
        return $this->belongsTo(Promotion::class, 'promotion_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
