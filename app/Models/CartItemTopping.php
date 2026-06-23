<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItemTopping extends Model
{
    protected $table = 'cart_item_toppings';
    protected $guarded = [];

    public function cartItem()
    {
        return $this->belongsTo(CartItem::class, 'cart_item_id');
    }

    public function topping()
    {
        return $this->belongsTo(Topping::class, 'topping_id');
    }
}
