<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// Model quản lý Nguyên liệu (Material) trong kho, liên kết trực tiếp với bảng 'materials' trong cơ sở dữ liệu
class Material extends Model
{
    use HasFactory;

    // Các cột cho phép thêm/sửa hàng loạt, Mass Assignment
    protected $fillable = [
        'name',          // Tên nguyên liệu, VD: Trà đen, Sữa tươi, Đường...
        'unit',          // Đơn vị tính, VD: g, ml, túi...
        'unit_price',    // Giá vốn trên mỗi đơn vị nguyên liệu
        'current_stock', // Số lượng tồn kho hiện tại, tổng các lô nhập
        'is_active',     // Trạng thái hoạt động, true: đang dùng, false: ngừng sử dụng
    ];

    // Mối quan hệ One-to-Many (Một - Nhiều) với model MaterialImport, một nguyên liệu có thể được nhập về theo nhiều lô khác nhau
    public function imports()
    {
        return $this->hasMany(MaterialImport::class);
    }

    // Mối quan hệ Many-to-Many (Nhiều - Nhiều) với model Product thông qua bảng trung gian 'product_materials', một nguyên liệu có thể nằm trong công thức của nhiều sản phẩm khác nhau (chứa thêm lượng tiêu thụ 'quantity_used')
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_materials')
            ->withPivot('quantity_used')
            ->withTimestamps();
    }
}
