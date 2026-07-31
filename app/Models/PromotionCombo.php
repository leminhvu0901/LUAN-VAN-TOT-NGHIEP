<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Cấu hình thưởng của 1 combo (scope=combo) — quan hệ 1-1 với Promotion. 2 thành phần thưởng ĐỘC
// LẬP nhau: giảm giá (discount_type/discount_value/max_discount_amount, null nếu không bật) và
// tặng quà (gift_product_id/gift_quantity/auto_add_gift, null nếu không bật) — 1 combo có thể bật
// cả 2, chỉ 1, hoặc không được lưu nếu không bật cái nào (validate ở PromotionController).
class PromotionCombo extends Model
{
    protected $table = 'promotion_combos';
    protected $guarded = [];

    protected $casts = [
        'discount_value' => 'float',
        'max_discount_amount' => 'float',
        'auto_add_gift' => 'boolean',
    ];

    public function promotion()
    {
        return $this->belongsTo(Promotion::class, 'promotion_id');
    }

    public function giftProduct()
    {
        return $this->belongsTo(Product::class, 'gift_product_id');
    }

    public function hasDiscount(): bool
    {
        return !is_null($this->discount_type);
    }

    public function hasGift(): bool
    {
        return !is_null($this->gift_product_id);
    }
}
