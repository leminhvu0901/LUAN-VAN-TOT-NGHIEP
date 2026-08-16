<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAddress extends Model
{
    // Tên bảng quản lý các địa chỉ nhận hàng của khách trong DB
    protected $table = 'user_addresses';

    // Cho phép điền hàng loạt cho tất cả các trường không
    protected $guarded = [];

    // Mối quan hệ Nhiều - Một (Belongs To) với model User, mỗi địa chỉ nhận hàng (UserAddress) sẽ thuộc sở hữu của một người dùng (User) duy nhất
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
