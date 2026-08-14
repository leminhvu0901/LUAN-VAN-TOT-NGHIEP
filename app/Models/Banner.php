<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $table = 'banners'; // Model này đại diện và liên kết trực tiếp với bảng banners trong DB
    protected $guarded = []; // Cho phép điền hàng loạt cho tất cả các trường, không khóa trường nào
}
