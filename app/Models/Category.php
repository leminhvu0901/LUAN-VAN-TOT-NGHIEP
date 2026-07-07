<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model quản lý danh mục sản phẩm (ví dụ: Trà sữa, Cà phê, Trà hoa quả...).
 * Liên kết trực tiếp với bảng 'categories' trong cơ sở dữ liệu.
 */
class Category extends Model
{
    // Tên bảng dữ liệu được quản lý bởi model này
    protected $table = 'categories';
    
    // Cho phép thêm mới hoặc sửa đổi hàng loạt dữ liệu trên tất cả các cột của bảng categories
    protected $guarded = [];

    /**
     * Mối quan hệ Một - Nhiều (Has Many): Một danh mục sản phẩm sẽ chứa nhiều sản phẩm (Products) khác nhau.
     * Liên kết từ khóa chính 'id' của bảng categories sang khóa ngoại 'category_id' trong bảng products.
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }
}
