<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URL', 'http://127.0.0.1:8000/auth/google/callback'),
    ],

    'momo' => [
        // Môi trường "thử nghiệm" (sandbox) - dùng credentials test công khai của MoMo làm mặc định
        // để demo hoạt động ngay cả khi .env chưa cấu hình gì.
        'sandbox' => [
            'partner_code' => env('MOMO_PARTNER_CODE_SANDBOX', 'MOMOBKUN20180529'),
            'access_key' => env('MOMO_ACCESS_KEY_SANDBOX', 'klm05TvNBzhg7h7j'),
            'secret_key' => env('MOMO_SECRET_KEY_SANDBOX', 'at67qH6mk8w5Y1nAyMoYKMWACiEi2bsa'),
            'endpoint' => env('MOMO_ENDPOINT_SANDBOX', 'https://test-payment.momo.vn/v2/gateway/api/create'),
            'refund_endpoint' => env('MOMO_REFUND_ENDPOINT_SANDBOX', 'https://test-payment.momo.vn/v2/gateway/api/refund'),
        ],
        // Môi trường "chính thức" (production) - bắt buộc phải cấu hình credentials thật trong .env,
        // không có giá trị mặc định để tránh vô tình chạy production bằng credentials test.
        'production' => [
            'partner_code' => env('MOMO_PARTNER_CODE'),
            'access_key' => env('MOMO_ACCESS_KEY'),
            'secret_key' => env('MOMO_SECRET_KEY'),
            'endpoint' => env('MOMO_ENDPOINT', 'https://payment.momo.vn/v2/gateway/api/create'),
            'refund_endpoint' => env('MOMO_REFUND_ENDPOINT', 'https://payment.momo.vn/v2/gateway/api/refund'),
        ],
    ],

    'vnpay' => [
        // Khác MoMo: KHÔNG bake sẵn credentials demo công khai — VNPay yêu cầu đăng ký merchant sandbox
        // riêng (TMN Code + Hash Secret), không có bộ credentials test dùng chung công khai như MoMo.
        'sandbox' => [
            'tmn_code' => env('VNPAY_TMN_CODE_SANDBOX'),
            'hash_secret' => env('VNPAY_HASH_SECRET_SANDBOX'),
            'url' => env('VNPAY_URL_SANDBOX', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),
            'refund_endpoint' => env('VNPAY_REFUND_ENDPOINT_SANDBOX', 'https://sandbox.vnpayment.vn/merchant_webapi/api/transaction'),
        ],
        'production' => [
            'tmn_code' => env('VNPAY_TMN_CODE'),
            'hash_secret' => env('VNPAY_HASH_SECRET'),
            'url' => env('VNPAY_URL', 'https://vnpayment.vn/paymentv2/vpcpay.html'),
            'refund_endpoint' => env('VNPAY_REFUND_ENDPOINT', 'https://vnpayment.vn/merchant_webapi/api/transaction'),
        ],
    ],

    // Chỉ dùng làm DỰ PHÒNG cho tính khoảng cách giao hàng — Geoapify Routing API là nguồn chính (xem
    // ShippingQuoteService). Giữ lại đến khi Geoapify được xác nhận chạy ổn định.
    'openroute' => [
        'key' => env('OPENROUTE_SERVICE_API_KEY'),
    ],

    // Geoapify — thay thế OpenStreetMap+Leaflet+Nominatim+OSRM cũ. 1 KEY DUY NHẤT dùng chung cho cả
    // 3 việc: (1) tile bản đồ hiển thị trong Leaflet ở checkout (khác OSM tile công khai — có key,
    // ổn định hơn), (2) Geocoding API (địa chỉ chữ <-> tọa độ, cả forward lẫn reverse), (3) Routing
    // API tính khoảng cách giao hàng thật (ShippingQuoteService, thay OpenRouteService/Google Routes).
    // Không cần bật billing — free tier theo request/ngày.
    'geoapify' => [
        'key' => env('GEOAPIFY_API_KEY'),
    ],

];
