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
    
    // Cho phép thêm mới hoặc sửa đổi hàng loạt dữ liệu trên
    protected $guarded = [];

    // Tự động ép kiểu các cột dữ liệu khi lấy từ DB ra (ví
    protected $casts = [
        'is_active' => 'boolean',      // Trạng thái hoạt động (true/false)
        'display_order' => 'integer',  // Thứ tự hiển thị danh mục (số nguyên)
    ];

    /**
     * Quan hệ 1 - Nhiều, 1 danh mục chứa nhiều sản phẩm
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    /**
     * Quan hệ Nhiều - Nhiều, danh mục áp dụng nhiều chương trình khuyến mãi khác nhau
     */
    public function promotions()
    {
        return $this->belongsToMany(Promotion::class, 'promotion_categories');
    }
}
