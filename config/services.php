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
        ],
        // Môi trường "chính thức" (production) - bắt buộc phải cấu hình credentials thật trong .env,
        // không có giá trị mặc định để tránh vô tình chạy production bằng credentials test.
        'production' => [
            'partner_code' => env('MOMO_PARTNER_CODE'),
            'access_key' => env('MOMO_ACCESS_KEY'),
            'secret_key' => env('MOMO_SECRET_KEY'),
            'endpoint' => env('MOMO_ENDPOINT', 'https://payment.momo.vn/v2/gateway/api/create'),
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

    // Gemini API - dùng làm lớp phân loại dự phòng (fallback) cho chatbox Quick Chat khi rule-based
    // không hiểu hoặc mập mờ. Tắt mặc định (enabled=false) để không gọi ra ngoài nếu chưa cấu hình key.
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        // 'gemini-flash-latest' là alias Google tự trỏ đến model flash mới nhất còn khả dụng cho API
        // key — các tên model cố định thế hệ cũ (vd gemini-2.5-flash-lite) có thể bị Google ngừng cấp
        // cho key mới ("no longer available to new users") dù vẫn còn trong danh sách models.list().
        'model' => env('GEMINI_MODEL', 'gemini-flash-latest'),
        'timeout' => env('GEMINI_TIMEOUT', 8),
        'enabled' => env('GEMINI_ENABLED', false),
        // Chỉ đổi giá trị này trong test (trỏ về 1 cổng local không lắng nghe để mô phỏng lỗi kết
        // nối thật mà không gọi ra ngoài) — không cần khai báo trong .env cho môi trường thật.
        'endpoint' => env('GEMINI_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta/models'),
    ],

];
