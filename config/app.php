<?php

return [

    // Tên của ứng dụng
    'name' => env('APP_NAME', 'Laravel'),

    // Môi trường hoạt động của ứng dụng (local, production, v.v.)
    'env' => env('APP_ENV', 'production'),

    // Chế độ sửa lỗi (bật/tắt hiển thị chi tiết thông báo lỗi)
    'debug' => (bool) env('APP_DEBUG', false),

    // Địa chỉ URL chính của trang web
    'url' => env('APP_URL', 'http://localhost'),

    // Múi giờ mặc định của ứng dụng
    'timezone' => 'Asia/Ho_Chi_Minh',

    // Ngôn ngữ mặc định được sử dụng
    'locale' => env('APP_LOCALE', 'en'),

    // Ngôn ngữ dự phòng nếu ngôn ngữ mặc định bị thiếu
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    // Ngôn ngữ mặc định dùng để sinh dữ liệu mẫu (Faker)
    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    // Thuật toán mã hóa dữ liệu
    'cipher' => 'AES-256-CBC',

    // Khóa mã hóa ứng dụng
    'key' => env('APP_KEY'),

    // Danh sách các khóa mã hóa cũ dùng để giải mã dữ liệu cũ
    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    // Cấu hình chế độ bảo trì hệ thống
    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

];
