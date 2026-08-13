<?php

return [

    // Ổ đĩa lưu trữ mặc định được sử dụng
    'default' => env('FILESYSTEM_DISK', 'local'),

    // Các ổ đĩa lưu trữ được định nghĩa
    'disks' => [

        // Ổ đĩa lưu trữ nội bộ riêng tư
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        // Ổ đĩa lưu trữ công khai
        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/') . '/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        // Ổ đĩa đám mây Amazon S3
        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    // Cấu hình liên kết thư mục công khai
    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
