<?php

use App\Models\User;

return [

    // Cấu hình bảo mật và khôi phục mặc định
    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    // Các chốt chặn bảo mật cho ứng dụng
    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
    ],

    // Nguồn dữ liệu tài khoản người dùng
    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', App\Models\User::class),
        ],
    ],

    // Cấu hình thiết lập lại mật khẩu
    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    // Thời gian hết hạn xác nhận mật khẩu, mặc định 10800 giây = 3 tiếng
    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
