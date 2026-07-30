<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    // Các trường có thể điền hàng loạt (Mass Assignment)
    protected $fillable = ['group', 'key', 'value', 'type'];

    /**
     * Lấy giá trị cấu hình theo Key (có sử dụng Cache vĩnh viễn để tối ưu hiệu năng)
     */
    public static function getValue(string $key, $default = null)
    {
        // Cache vĩnh viễn giá trị cấu hình, tránh truy vấn database liên tục
        return Cache::rememberForever("setting.{$key}", function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            if (!$setting) {
                return $default; // Trả về giá trị mặc định nếu cấu hình chưa tồn tại
            }

            $value = $setting->value;
            if ($value === null) {
                return null;
            }

            // Chuyển kiểu dữ liệu từ chuỗi (string) trong DB về kiểu thực tế (int, float, bool, json...)
            $type = strtolower($setting->type ?? 'string');

            switch ($type) {
                case 'integer':
                case 'int':
                    return (int)$value;
                case 'decimal':
                case 'float':
                case 'double':
                case 'numeric':
                    return (float)$value;
                case 'boolean':
                case 'bool':
                    return filter_var($value, FILTER_VALIDATE_BOOLEAN);
                case 'json':
                case 'array':
                    return json_decode($value, true);
                default:
                    return (string)$value;
            }
        });
    }

    /**
     * Lưu/Cập nhật giá trị cấu hình và xóa bộ nhớ đệm (Cache) tương ứng
     */
    public static function setValue(string $key, $value, string $group = 'general', string $type = 'string'): void
    {
        // Cập nhật hoặc Tạo mới nếu chưa có
        self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value !== null ? (string)$value : null,
                'group' => $group,
                'type' => $type
            ]
        );

        // Xóa cache cũ để hệ thống nạp lại giá trị mới ở lần truy cập tiếp theo
        Cache::forget("setting.{$key}");
    }

    /**
     * Lưu hàng loạt cấu hình trong cùng một nhóm
     */
    public static function setMany(array $settings, string $group = 'general'): void
    {
        foreach ($settings as $key => $value) {
            self::setValue($key, $value, $group);
        }
    }
}
