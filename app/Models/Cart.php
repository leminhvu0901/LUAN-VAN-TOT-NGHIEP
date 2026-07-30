<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $table = 'carts'; // Model này đại diện và liên kết trực tiếp với bảng carts (giỏ hàng) trong DB
    protected $guarded = []; // Cho phép điền hàng loạt cho tất cả các trường (không khóa trường nào)
}
