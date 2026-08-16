<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    // Các cột dữ liệu được phép điền nhanh, mass-assign
    protected $fillable = ['group', 'key', 'value', 'type'];

    // Lấy giá trị cấu hình theo Key (tên cấu hình), Hàm này tự động cache kết quả vĩnh viễn (rememberForever) để tránh việc phải liên tục truy vấn SQL,, giúp tăng tốc độ phản hồi của hệ thống
    public static function getValue(string $key, $default = null)
    {
        // Cache vĩnh viễn giá trị cấu hình theo key, nếu có
        return Cache::rememberForever("setting.{$key}", function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            if (!$setting) {
                return $default; // Trả về giá trị mặc định nếu cấu hình chưa tồn tại trong DB
            }

            $value = $setting->value;
            if ($value === null) {
                return null;
            }

            // Chuyển kiểu dữ liệu từ chuỗi, string trong DB về kiểu
            $type = strtolower($setting->type ?? 'string');

            switch ($type) {
                case 'integer':
                case 'int':
                    return (int) $value;
                case 'decimal':
                case 'float':
                case 'double':
                case 'numeric':
                    return (float) $value;
                case 'boolean':
                case 'bool':
                    // Chuyển chuỗi "true"/"false" sang kiểu boolean thực sự
                    return filter_var($value, FILTER_VALIDATE_BOOLEAN);
                case 'json':
                case 'array':
                    // Giải mã chuỗi JSON trong DB thành mảng array trong PHP
                    return json_decode($value, true);
                default:
                    return (string) $value;
            }
        });
    }

    // Lưu hoặc cập nhật một cấu hình cụ thể, đồng thời xóa bộ nhớ đệm (Cache) tương ứng, khi xóa Cache, ở lần gọi getValue() tiếp theo, hệ thống sẽ tự động lấy giá trị mới từ DB và lưu vào Cache lại
    public static function setValue(string $key, $value, string $group = 'general', string $type = 'string'): void
    {
        // Cập nhật giá trị nếu đã tồn tại, hoặc tạo mới nếu chưa có
        self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value !== null ? (string) $value : null,
                'group' => $group,
                'type' => $type
            ]
        );

        // Xóa cache cũ để hệ thống nạp lại giá trị mới ở lần
        Cache::forget("setting.{$key}");
    }

    // Lưu/Cập nhật hàng loạt cấu hình trong cùng một nhóm (group), Nhận đầu vào là một mảng có khóa là tên cấu hình và giá trị cấu hình (ví dụ: ['shipping_fee' => 15000])
    public static function setMany(array $settings, string $group = 'general'): void
    {
        foreach ($settings as $key => $value) {
            self::setValue($key, $value, $group);
        }
    }
}
