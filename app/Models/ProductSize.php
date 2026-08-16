<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSize extends Model
{
    // Tắt tính năng tự động ghi nhận thời gian tạo/sửa
    public $timestamps = false;

    // Tên bảng dữ liệu được quản lý bởi model này
    protected $table = 'product_sizes';

    // Cho phép thêm mới hoặc cập nhật hàng loạt trên tất cả
    protected $guarded = [];

    // Mối quan hệ Nhiều - Một (Belongs To) với model Product, mỗi cấu hình size (ví dụ: Size L của Trà sữa trân châu) chỉ thuộc về một sản phẩm (Product) duy nhất
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
