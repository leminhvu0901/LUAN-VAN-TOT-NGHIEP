<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    // Tên bảng quản lý các đánh giá sản phẩm trong DB
    protected $table = 'reviews';

    // Cho phép điền hàng loạt cho tất cả các trường (không
    protected $guarded = [];

    // Tự động ép kiểu dữ liệu khi lấy từ DB ra
    protected $casts = [
        'edited_at' => 'datetime', // Ép kiểu cột thời điểm chỉnh sửa đánh giá sang Carbon DateTime
    ];

    /**
     * Mối quan hệ Nhiều - Một (Belongs To) với model Product.
     * Mỗi đánh giá (Review) sẽ thuộc về một sản phẩm (Product) cụ thể.
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Mối quan hệ Nhiều - Một (Belongs To) với model User.
     * Mỗi đánh giá (Review) được viết bởi một tài khoản khách hàng (User) duy nhất.
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
