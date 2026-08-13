<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model quản lý chi tiết từng món đồ uống trong đơn hàng (ví dụ: số lượng, đơn giá, kích cỡ...).
 * Liên kết trực tiếp với bảng 'order_items' trong cơ sở dữ liệu.
 */
class OrderItem extends Model
{
    // Tắt tính năng tự động ghi nhận thời gian tạo/sửa
    public $timestamps = false;
    
    // Tên bảng dữ liệu được quản lý bởi model này
    protected $table = 'order_items';
    
    // Cho phép thêm mới hoặc cập nhật hàng loạt trên tất cả
    protected $guarded = [];

    // Cấu hình ép kiểu dữ liệu khi lấy dữ liệu ra khỏi cơ sở
    protected $casts = [
        'options' => 'array', // Ép kiểu cột 'options' lưu dưới dạng JSON ở DB thành mảng array trong PHP (để lưu topping, size...)
    ];

    /**
     * Mối quan hệ Nhiều - Một (Belongs To): Một dòng chi tiết đơn hàng sẽ thuộc về một đơn hàng (Order) duy nhất.
     * Liên kết cột 'order_id' trong bảng order_items với cột 'id' trong bảng orders.
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Mối quan hệ Nhiều - Một (Belongs To): Một dòng chi tiết đơn hàng sẽ mô tả cho một sản phẩm (Product) cụ thể.
     * Liên kết cột 'product_id' trong bảng order_items với cột 'id' trong bảng products.
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Accessor ảo (Attribute) để lấy URL ảnh sản phẩm đã chụp lại (snapshot) lúc đặt hàng.
     * Dùng upload_url() để tạo URL cho đường dẫn ảnh tương đối trong public/images/.
     * Trả về ảnh mặc định (placeholder.jpg) khi chưa có ảnh.
     */
    public function getProductImageUrlAttribute()
    {
        return upload_url($this->product_image) ?: asset('images/products/placeholder.jpg');
    }
}
