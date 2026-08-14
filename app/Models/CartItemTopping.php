<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItemTopping extends Model
{
    protected $table = 'cart_item_toppings'; // Model này đại diện và liên kết với bảng trung gian cart_item_toppings
    protected $guarded = []; // Cho phép điền hàng loạt cho tất cả các trường, không khóa trường nào
    public $timestamps = false; // Bảng cart_item_toppings không có cột created_at/updated_at

    /**
     * Quan hệ Nhiều - 1, nhiều topping thuộc vè 1 sản phẩm
     */
    public function cartItem()
    {
        return $this->belongsTo(CartItem::class, 'cart_item_id');
    }

    /**
     * Quan hệ Nhiều - 1, nhiều topping được chọn thuộc về 1 loại topping gốc
     */
    public function topping()
    {
        return $this->belongsTo(Topping::class, 'topping_id');
    }
}
