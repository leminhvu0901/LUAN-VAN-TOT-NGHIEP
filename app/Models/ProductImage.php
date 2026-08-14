<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    // Các cột dữ liệu được phép điền nhanh, mass-assign khi
    protected $fillable = [
        'product_id',    // ID của sản phẩm sở hữu ảnh này
        'image_path',    // Đường dẫn tương đối lưu file ảnh trong ổ đĩa
        'display_order'  // Thứ tự hiển thị của ảnh, ví dụ: ảnh nào hiện trước, ảnh nào hiện sau
    ];

    /**
     * Mối quan hệ Nhiều - Một (Belongs To) với model Product.
     * Mỗi bức ảnh (ProductImage) chỉ thuộc về một sản phẩm (Product) duy nhất.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Accessor ảo (Attribute) để lấy URL ảnh đầy đủ.
     * Khi gọi $productImage->image_url, Laravel sẽ tự động gọi hàm này
     * và dùng helper upload_url() để sinh ra đường dẫn đầy đủ hiển thị lên giao diện.
     */
    public function getImageUrlAttribute()
    {
        return upload_url($this->image_path);
    }
}
