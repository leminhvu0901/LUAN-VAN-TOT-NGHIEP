<?php

return [

    // Mailer mặc định được sử dụng để gửi email (smtp, log, mailgun, v.v.)
    'default' => env('MAIL_MAILER', 'log'),

    // Các cấu hình máy chủ gửi email khác nhau
    'mailers' => [

        // Cấu hình gửi qua giao thức SMTP
        'smtp' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL_SCHEME'),
            'url' => env('MAIL_URL'),
            'host' => env('MAIL_HOST', '127.0.0.1'),
            'port' => env('MAIL_PORT', 2525),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            // Thời gian chờ tối đa kết nối SMTP (giới hạn 25 giây để tránh treo UI)
            'timeout' => (int) env('MAIL_TIMEOUT', 25),
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

        // Gửi qua Amazon SES
        'ses' => [
            'transport' => 'ses',
        ],

        // Gửi qua Postmark
        'postmark' => [
            'transport' => 'postmark',
        ],

        // Gửi qua dịch vụ Resend
        'resend' => [
            'transport' => 'resend',
        ],

        // Gửi qua chương trình Sendmail cục bộ của server Linux
        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        // Chỉ ghi thông tin email vào file log (dùng khi phát triển local)
        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        // Lưu email vào mảng bộ nhớ (dùng để viết các bản kiểm thử test)
        'array' => [
            'transport' => 'array',
        ],

        // Cơ chế tự chuyển đổi dự phòng nếu mailer chính gặp lỗi kết nối
        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'smtp',
                'log',
            ],
            'retry_after' => 60,
        ],

        // Cơ chế luân phiên gửi email qua nhiều dịch vụ để phân chia tải
        'roundrobin' => [
            'transport' => 'roundrobin',
            'mailers' => [
                'ses',
                'postmark',
            ],
            'retry_after' => 60,
        ],

    ],

    // Thông tin địa chỉ email gửi đi mặc định của hệ thống
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', env('APP_NAME', 'Laravel')),
    ],

];
