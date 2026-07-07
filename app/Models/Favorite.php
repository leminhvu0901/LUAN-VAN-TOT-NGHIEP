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
     * Mối quan hệ: Một người dùng (User) sở hữu một danh sách yêu thích.
     * Liên kết cột 'user_id' trong bảng favorites với cột 'id' trong bảng users.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Mối quan hệ: Một danh sách yêu thích (Favorite) chứa sản phẩm (Product).
     * Liên kết cột 'product_id' trong bảng favorites với cột 'id' trong bảng products.
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
