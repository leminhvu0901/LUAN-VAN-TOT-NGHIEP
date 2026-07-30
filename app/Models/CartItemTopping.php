<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItemTopping extends Model
{
    protected $table = 'cart_item_toppings'; // Model này đại diện và liên kết với bảng trung gian cart_item_toppings
    protected $guarded = []; // Cho phép điền hàng loạt cho tất cả các trường (không khóa trường nào)

    /**
     * Quan hệ Nhiều - 1 (Many-to-One) với Model CartItem.
     * Nhiều bản ghi topping thuộc về cùng một dòng sản phẩm (CartItem) trong giỏ hàng.
     */
    public function cartItem()
    {
        return $this->belongsTo(CartItem::class, 'cart_item_id');
    }

    /**
     * Quan hệ Nhiều - 1 (Many-to-One) với Model Topping.
     * Mỗi dòng topping liên kết tới định nghĩa Topping cụ thể trong bảng toppings (ví dụ: Trân châu đường đen).
     */
    public function topping()
    {
        return $this->belongsTo(Topping::class, 'topping_id');
    }
}
