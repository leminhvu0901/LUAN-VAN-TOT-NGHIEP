<?php

use Illuminate\Support\Str;

return [

    // Driver lưu trữ session mặc định
    'driver' => env('SESSION_DRIVER', 'database'),

    // Thời gian sống của session
    'lifetime' => (int) env('SESSION_LIFETIME', 120),

    // Hết hạn session khi đóng trình duyệt
    'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', false),

    // Mã hóa dữ liệu session
    'encrypt' => env('SESSION_ENCRYPT', false),

    // Đường dẫn lưu file session
    'files' => storage_path('framework/sessions'),

    // Kết nối CSDL cho session
    'connection' => env('SESSION_CONNECTION'),

    // Tên bảng lưu session
    'table' => env('SESSION_TABLE', 'sessions'),

    // Bộ lưu trữ cache cho session
    'store' => env('SESSION_STORE'),

    // Tỉ lệ dọn dẹp session cũ
    'lottery' => [2, 100],

    // Tên Cookie của session
    'cookie' => env(
        'SESSION_COOKIE',
        Str::slug((string) env('APP_NAME', 'laravel')).'-session'
    ),

    // Đường dẫn khả dụng của Cookie
    'path' => env('SESSION_PATH', '/'),

    // Tên miền của Cookie
    'domain' => env('SESSION_DOMAIN'),

    // Chỉ gửi Cookie qua HTTPS
    'secure' => env('SESSION_SECURE_COOKIE'),

    // Ngăn chặn truy cập Cookie qua JavaScript
    'http_only' => env('SESSION_HTTP_ONLY', true),

    // Cấu hình Same-Site cho Cookie
    'same_site' => env('SESSION_SAME_SITE', 'lax'),

    // Cookie phân vùng
    'partitioned' => env('SESSION_PARTITIONED_COOKIE', false),

    // Định dạng mã hóa dữ liệu session
    'serialization' => 'json',

];

