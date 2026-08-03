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

    // Cấu hình kết nối cổng thanh toán ví điện tử MoMo
    'momo' => [
        // Thông tin tài khoản thử nghiệm (sandbox) mặc định
        'sandbox' => [
            'partner_code' => env('MOMO_PARTNER_CODE_SANDBOX', 'MOMOBKUN20180529'),
            'access_key' => env('MOMO_ACCESS_KEY_SANDBOX', 'klm05TvNBzhg7h7j'),
            'secret_key' => env('MOMO_SECRET_KEY_SANDBOX', 'at67qH6mk8w5Y1nAyMoYKMWACiEi2bsa'),
            'endpoint' => env('MOMO_ENDPOINT_SANDBOX', 'https://test-payment.momo.vn/v2/gateway/api/create'),
            'refund_endpoint' => env('MOMO_REFUND_ENDPOINT_SANDBOX', 'https://test-payment.momo.vn/v2/gateway/api/refund'),
        ],
        // Thông tin tài khoản chính thức (production)
        'production' => [
            'partner_code' => env('MOMO_PARTNER_CODE'),
            'access_key' => env('MOMO_ACCESS_KEY'),
            'secret_key' => env('MOMO_SECRET_KEY'),
            'endpoint' => env('MOMO_ENDPOINT', 'https://payment.momo.vn/v2/gateway/api/create'),
            'refund_endpoint' => env('MOMO_REFUND_ENDPOINT', 'https://payment.momo.vn/v2/gateway/api/refund'),
        ],
    ],

    // Cấu hình kết nối cổng thanh toán VNPay
    'vnpay' => [
        // Thông tin kết nối thử nghiệm VNPay (sandbox)
        'sandbox' => [
            'tmn_code' => env('VNPAY_TMN_CODE_SANDBOX'),
            'hash_secret' => env('VNPAY_HASH_SECRET_SANDBOX'),
            'url' => env('VNPAY_URL_SANDBOX', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),
            'refund_endpoint' => env('VNPAY_REFUND_ENDPOINT_SANDBOX', 'https://sandbox.vnpayment.vn/merchant_webapi/api/transaction'),
        ],
        // Thông tin kết nối chính thức VNPay (production)
        'production' => [
            'tmn_code' => env('VNPAY_TMN_CODE'),
            'hash_secret' => env('VNPAY_HASH_SECRET'),
            'url' => env('VNPAY_URL', 'https://vnpayment.vn/paymentv2/vpcpay.html'),
            'refund_endpoint' => env('VNPAY_REFUND_ENDPOINT', 'https://vnpayment.vn/merchant_webapi/api/transaction'),
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
