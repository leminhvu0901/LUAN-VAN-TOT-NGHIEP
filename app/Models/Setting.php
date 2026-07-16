<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['group', 'key', 'value', 'type'];

    /**
     * Get a setting value with caching.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function getValue(string $key, $default = null)
    {
        return Cache::rememberForever("setting.{$key}", function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            if (!$setting) {
                return $default;
            }

            $value = $setting->value;
            if ($value === null) {
                return null;
            }

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
     * Set a setting value and invalidate cache.
     *
     * @param string $key
     * @param mixed $value
     * @param string $group
     * @param string $type
     * @return void
     */
    public static function setValue(string $key, $value, string $group = 'general', string $type = 'string'): void
    {
        self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value !== null ? (string)$value : null,
                'group' => $group,
                'type' => $type
            ]
        );

        Cache::forget("setting.{$key}");
    }

    /**
     * Set multiple setting values in a group.
     *
     * @param array $settings
     * @param string $group
     * @return void
     */
    public static function setMany(array $settings, string $group = 'general'): void
    {
        foreach ($settings as $key => $value) {
            self::setValue($key, $value, $group);
        }
    }
}
