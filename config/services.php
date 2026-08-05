<?php

return [

    // Cấu hình dịch vụ gửi email Postmark
    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    // Cấu hình dịch vụ gửi email Resend
    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    // Cấu hình dịch vụ Amazon SES (Simple Email Service)
    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // Cấu hình kênh thông báo Slack
    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Cấu hình đăng nhập bằng tài khoản Google (OAuth)
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URL', 'http://127.0.0.1:8000/auth/google/callback'),
    ],


    // Cấu hình kết nối cổng thanh toán VNPay (Môi trường thử nghiệm Sandbox)
    'vnpay' => [
        'sandbox' => [
            'tmn_code' => env('VNPAY_TMN_CODE_SANDBOX'),
            'hash_secret' => env('VNPAY_HASH_SECRET_SANDBOX'),
            'url' => env('VNPAY_URL_SANDBOX', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),
            'refund_endpoint' => env('VNPAY_REFUND_ENDPOINT_SANDBOX', 'https://sandbox.vnpayment.vn/merchant_webapi/api/transaction'),
        ],
    ],

    // Cấu hình API Key dự phòng của OpenRouteService
    'openroute' => [
        'key' => env('OPENROUTE_SERVICE_API_KEY'),
    ],

    // Cấu hình API Key của Geoapify (dùng định vị, hiển thị bản đồ, tính phí ship)
    'geoapify' => [
        'key' => env('GEOAPIFY_API_KEY'),
    ],

];
