<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialImport extends Model
{
    use HasFactory;

    // Các cột dữ liệu được phép điền nhanh (mass-assign) khi
    protected $fillable = [
        'material_id',       // ID của nguyên liệu được nhập
        'quantity',          // Số lượng nhập kho ban đầu
        'total_price',       // Tổng số tiền nhập lô này
        'note',              // Ghi chú hoặc thông tin lý do nhập/xuất
        'expiration_date',   // Ngày hết hạn của lô nguyên liệu
        'remaining_quantity' // Số lượng còn lại khả dụng trong lô (dùng để trừ kho theo lô)
    ];

    // Tự động chuyển kiểu dữ liệu khi truy vấn từ Database
    protected $casts = [
        'expiration_date' => 'date', // Chuyển cột ngày hết hạn sang đối tượng Carbon Date để dễ xử lý thời gian
    ];

    /**
     * Mối quan hệ Nhiều - Một (BelongsTo) với model Material.
     * Mỗi lô nhập kho (MaterialImport) sẽ tương ứng với duy nhất 1 nguyên liệu (Material).
     */
    public function material()
    {
        return $this->belongsTo(Material::class);
    }
}
