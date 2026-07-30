<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $table = 'cart_items'; // Model này đại diện và liên kết với bảng chi tiết giỏ hàng cart_items
    protected $guarded = []; // Cho phép điền hàng loạt cho tất cả các trường (không khóa trường nào)

    /**
     * Mối quan hệ: Chi tiết dòng sản phẩm thuộc về một Giỏ hàng chính (Cart)
     */
    public function cart()
    {
        return $this->belongsTo(Cart::class, 'cart_id');
    }

    /**
     * Mối quan hệ: Dòng sản phẩm này tương ứng với Sản phẩm nào (Product - ví dụ: Trà sữa Trân châu)
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Mối quan hệ: Một dòng sản phẩm trong giỏ hàng có thể chọn kèm nhiều Topping khác nhau (CartItemTopping)
     */
    public function toppings()
    {
        return $this->hasMany(CartItemTopping::class, 'cart_item_id');
    }
}
