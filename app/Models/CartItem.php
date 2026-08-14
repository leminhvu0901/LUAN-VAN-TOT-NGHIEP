<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $table = 'cart_items'; // Model này đại diện và liên kết với bảng chi tiết giỏ hàng cart_items
    protected $guarded = []; // Cho phép điền hàng loạt cho tất cả các trường, không khóa trường nào

    /**
     * Quan hệ Nhiều - 1 (Many-to-One) với Model Cart.
     * Nhiều chi tiết sản phẩm thuộc về cùng một Giỏ hàng (Cart) chính.
     */
    public function cart()
    {
        return $this->belongsTo(Cart::class, 'cart_id');
    }

    /**
     * Quan hệ Nhiều - 1 (Many-to-One) với Model Product.
     * Mỗi dòng sản phẩm trong giỏ hàng tương ứng với một Sản phẩm cụ thể.
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Quan hệ 1 - Nhiều (One-to-Many) với Model CartItemTopping.
     * Một dòng sản phẩm trong giỏ hàng có thể chọn đi kèm nhiều Topping khác nhau.
     */
    public function toppings()
    {
        return $this->hasMany(CartItemTopping::class, 'cart_item_id');
    }
}
