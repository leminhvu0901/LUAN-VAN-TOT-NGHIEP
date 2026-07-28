<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Cấu hình "Mua X tặng Y" của 1 khuyến mãi (scope=buy_x_get_y) — quan hệ 1-1 với Promotion.
class PromotionBuyXGetY extends Model
{
    protected $table = 'promotion_buy_x_get_y';
    protected $guarded = [];

    protected $casts = [
        'auto_add_gift' => 'boolean',
    ];

    public function promotion()
    {
        return $this->belongsTo(Promotion::class, 'promotion_id');
    }

    public function buyProduct()
    {
        return $this->belongsTo(Product::class, 'buy_product_id');
    }

    public function giftProduct()
    {
        return $this->belongsTo(Product::class, 'gift_product_id');
    }
}
