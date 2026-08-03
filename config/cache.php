<?php

use Illuminate\Support\Str;

return [

    // Bộ lưu trữ cache mặc định (database, file, redis, v.v.)
    'default' => env('CACHE_STORE', 'database'),

    // Danh sách các bộ lưu trữ cache được định nghĩa
    'stores' => [

        // Bộ lưu trữ dạng mảng (chỉ sống trong vòng đời 1 request, dùng cho testing)
        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],

        // Bộ lưu trữ trong Database (yêu cầu tạo bảng cache)
        'database' => [
            'driver' => 'database',
            'connection' => env('DB_CACHE_CONNECTION'),
            'table' => env('DB_CACHE_TABLE', 'cache'),
            'lock_connection' => env('DB_CACHE_LOCK_CONNECTION'),
            'lock_table' => env('DB_CACHE_LOCK_TABLE'),
        ],

        // Bộ lưu trữ lưu trực tiếp thành tệp tin trên ổ đĩa cứng
        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
            'lock_path' => storage_path('framework/cache/data'),
        ],

        // Bộ lưu trữ dùng dịch vụ Memcached bên ngoài
        'memcached' => [
            'driver' => 'memcached',
            'persistent_id' => env('MEMCACHED_PERSISTENT_ID'),
            'sasl' => [
                env('MEMCACHED_USERNAME'),
                env('MEMCACHED_PASSWORD'),
            ],
            'options' => [
                // Memcached::OPT_CONNECT_TIMEOUT => 2000,
            ],
            'servers' => [
                [
                    'host' => env('MEMCACHED_HOST', '127.0.0.1'),
                    'port' => env('MEMCACHED_PORT', 11211),
                    'weight' => 100,
                ],
            ],
        ],

        // Bộ lưu trữ dùng dịch vụ cơ sở dữ liệu RAM ảo Redis
        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_CACHE_CONNECTION', 'cache'),
            'lock_connection' => env('REDIS_CACHE_LOCK_CONNECTION', 'default'),
        ],

        // Bộ lưu trữ dùng cơ sở dữ liệu AWS DynamoDB
        'dynamodb' => [
            'driver' => 'dynamodb',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'table' => env('DYNAMODB_CACHE_TABLE', 'cache'),
            'endpoint' => env('DYNAMODB_ENDPOINT'),
        ],

        // Bộ lưu trữ dành cho môi trường Laravel Octane
        'octane' => [
            'driver' => 'octane',
        ],

        // Bộ lưu trữ dự phòng nếu các bộ lưu trữ chính gặp lỗi kết nối
        'failover' => [
            'driver' => 'failover',
            'stores' => [
                'database',
                'array',
            ],
        ],

    ],

    // Tiền tố khóa cache để tránh xung đột dữ liệu giữa các ứng dụng
    'prefix' => env('CACHE_PREFIX', Str::slug((string) env('APP_NAME', 'laravel')).'-cache-'),

    // Cấu hình ngăn chặn gadget chain attacks bằng cách tắt giải tuần tự các PHP class từ cache
    'serializable_classes' => false,

];
