<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model quản lý danh sách sản phẩm yêu thích (Wishlist) của người dùng.
 * Liên kết trực tiếp với bảng 'favorites' trong cơ sở dữ liệu.
 */
class Favorite extends Model
{
    // Tên bảng được quản lý bởi model này
    protected $table = 'favorites';

    // Cho phép thêm mới hoặc cập nhật hàng loạt trên tất cả các cột của bảng favorites
    protected $guarded = [];

    /**
     * Mối quan hệ Many-to-One (Nhiều - Một) với model User.
     * Mỗi bản ghi yêu thích cụ thể chỉ thuộc về một người dùng (User).
     * Liên kết cột 'user_id' trong bảng favorites với cột 'id' trong bảng users.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Mối quan hệ Many-to-One (Nhiều - Một) với model Product.
     * Mỗi bản ghi yêu thích cụ thể liên kết đến một sản phẩm (Product).
     * Liên kết cột 'product_id' trong bảng favorites với cột 'id' trong bảng products.
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
