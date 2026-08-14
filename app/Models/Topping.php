<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Topping extends Model
{
    // Tên bảng quản lý các topping trong DB
    protected $table = 'toppings';

    // Cho phép điền hàng loạt cho tất cả các trường không
    protected $guarded = [];
}
