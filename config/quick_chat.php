<?php

// Nội dung chatbox Happy Tea. Gồm 2 phần dùng chung file này:
//   1) Chatbox nút bấm chủ đề tĩnh (greeting/menu/labels/answers/defaults) — hiển thị qua Blade +
//      App\Http\View\Composers\QuickChatboxComposer, không AI.
//   2) Ô nhập câu hỏi tự do — App\Services\QuickChatService luôn trả về câu fallback + gợi ý nút bấm
//      (không phân loại ý định), khách bấm nút để đi vào đúng 'intents' bên dưới.
// Mọi text sửa ở đây, JS/Blade chỉ hiển thị.

return [
    'greeting' => 'Xin chào! Happy Tea có thể hỗ trợ bạn vấn đề gì?',
    'fallback_notice' => 'Chatbox hiện hỗ trợ các câu hỏi có sẵn. Với vấn đề khác, vui lòng liên hệ cửa hàng.',
    'header_title' => 'Hỗ trợ Happy Tea',

    // Độ trễ (ms) trước khi hiện câu trả lời, mô phỏng "đang trả lời" — trong khoảng 300-500ms theo yêu cầu.
    'typing_delay_ms' => 400,

    // Menu chính — thứ tự hiển thị đúng theo yêu cầu.
    'menu' => [
        ['key' => 'featured_products', 'label' => 'Sản phẩm nổi bật'],
        ['key' => 'promotions', 'label' => 'Khuyến mãi hiện có'],
        ['key' => 'order_tracking', 'label' => 'Theo dõi đơn hàng'],
        ['key' => 'payment_methods', 'label' => 'Phương thức thanh toán'],
        ['key' => 'shipping', 'label' => 'Giao hàng và phí vận chuyển'],
        ['key' => 'business_hours', 'label' => 'Giờ hoạt động'],
        ['key' => 'contact', 'label' => 'Thông tin liên hệ'],
    ],

    'labels' => [
        'back' => 'Quay lại',
        'menu' => 'Về menu chính',
        'close' => 'Đóng',
        'view_product' => 'Xem sản phẩm',
        'view_map' => 'Xem bản đồ',
        'call_store' => 'Gọi cửa hàng',
        'send_email' => 'Gửi email',
        'go_to_orders' => 'Xem đơn hàng của tôi',
        'go_to_login' => 'Đăng nhập ngay',
    ],

    // Nội dung trả lời không phụ thuộc dữ liệu động (sản phẩm/khuyến mãi lấy qua QuickChatboxComposer).
    'answers' => [
        'payment_methods' => 'Đơn đặt online có thể thanh toán bằng MoMo hoặc tiền mặt khi nhận hàng (COD), tùy theo cấu hình cửa hàng đang bật. Đơn tạo tại quầy do lễ tân xử lý có thể thanh toán bằng tiền mặt hoặc MoMo.',
        'shipping' => 'Phí giao hàng được tính dựa trên khoảng cách từ cửa hàng đến địa chỉ nhận hàng, có thể phát sinh phụ thu khi thời tiết xấu (mưa to, bão). Phí chính xác sẽ hiển thị ở bước thanh toán trước khi bạn xác nhận đặt hàng. Đơn nhận tại cửa hàng không tính phí giao hàng.',
        'order_tracking_guest' => 'Bạn cần đăng nhập để xem danh sách và trạng thái đơn hàng của mình.',
        'order_tracking_auth' => 'Xem chi tiết và trạng thái đơn hàng tại trang "Đơn hàng của tôi".',
    ],

    // Giá trị mặc định CHỈ dùng khi Setting trong DB chưa có — QuickChatboxComposer luôn ưu tiên DB trước.
    'defaults' => [
        'open_time' => '08:00',
        'close_time' => '22:00',
        'phone' => null,
        'email' => null,
        'address' => null,
    ],

    // ============================================================
    // Ô NHẬP CÂU HỎI TỰ DO — App\Services\QuickChatService + App\Http\Controllers\Frontend\QuickChatController.
    // Luôn trả về câu fallback bên dưới kèm nút gợi ý, không phân loại ý định câu hỏi.
    // ============================================================
    'freeform_placeholder' => 'Nhập câu hỏi về Happy Tea...',

    // Câu trả lời chuẩn cho mọi câu hỏi tự do.
    'fallback_freeform' => 'Xin lỗi, mình chưa hiểu rõ câu hỏi của bạn. Bạn có thể hỏi về sản phẩm, giá bán, khuyến mãi, thanh toán, giao hàng hoặc đơn hàng nhé!',

    // Nút gợi ý hiển thị SAU câu fallback — mỗi nút gửi lại đúng intent đã biết qua
    // QuickChatService::askByIntent().
    'fallback_suggestions' => [
        ['intent_id' => 'product', 'label' => 'Sản phẩm'],
        ['intent_id' => 'product_price', 'label' => 'Giá bán'],
        ['intent_id' => 'promotion', 'label' => 'Khuyến mãi'],
        ['intent_id' => 'payment', 'label' => 'Thanh toán'],
        ['intent_id' => 'shipping', 'label' => 'Giao hàng'],
        ['intent_id' => 'order_tracking', 'label' => 'Theo dõi đơn hàng'],
    ],

    // Câu trả lời khi truy vấn sản phẩm theo yêu cầu của khách không còn kết quả phù hợp (vd loại trừ
    // hết danh mục) — dùng trong QuickChatService::honestNoMatch().
    'product_no_match_answer' => 'Hiện tại mình chưa tìm được gợi ý phù hợp với cả hai yêu cầu. Bạn có thể chọn nhóm trà hoặc đồ uống khác.',

    // Mỗi intent: id (duy nhất), label (nhãn nút gợi ý), handler (static|product|promotion|
    // order_tracking|opening_hours|contact), answer (câu trả lời tĩnh hoặc câu dẫn trước danh sách),
    // action_route (tên route Laravel nếu có nút hành động), suggest_intents (gợi ý bấm sâu hơn).
    // Dùng bởi: askByIntent() (nút gợi ý).
    'intents' => [
        [
            'id' => 'product',
            'label' => 'Sản phẩm',
            'handler' => 'product',
            'answer' => 'Đây là một số sản phẩm bạn có thể quan tâm:',
            'action_route' => 'products',
        ],
        [
            'id' => 'product_price',
            'label' => 'Giá sản phẩm',
            'handler' => 'product',
            'answer' => 'Giá các sản phẩm hiện tại:',
            'action_route' => 'products',
        ],
        [
            'id' => 'product_option',
            'label' => 'Size, topping, đường, đá',
            'handler' => 'static',
            'answer' => 'Mỗi sản phẩm đều có thể tùy chỉnh size, mức đường, mức đá và thêm topping ngay khi bạn thêm vào giỏ hàng.',
            'action_route' => null,
        ],
        [
            'id' => 'promotion',
            'label' => 'Khuyến mãi hiện có',
            'handler' => 'promotion',
            'answer' => 'Các khuyến mãi đang áp dụng:',
            'action_route' => null,
        ],
        [
            'id' => 'promotion_condition',
            'label' => 'Điều kiện dùng mã giảm giá',
            'handler' => 'promotion',
            'answer' => 'Điều kiện áp dụng của từng chương trình (đơn tối thiểu, số lượng tối thiểu, mức giảm tối đa) được ghi rõ trong từng khuyến mãi bên dưới:',
            'action_route' => null,
        ],
        [
            'id' => 'payment',
            'label' => 'Phương thức thanh toán',
            'handler' => 'static',
            'answer' => 'Đơn đặt online có thể thanh toán bằng MoMo hoặc tiền mặt khi nhận hàng (COD), tùy theo cấu hình cửa hàng đang bật. Đơn tạo tại quầy do lễ tân xử lý có thể thanh toán bằng tiền mặt hoặc MoMo.',
            'action_route' => null,
            'suggest_intents' => ['momo', 'cod'],
        ],
        [
            'id' => 'momo',
            'label' => 'Thanh toán MoMo',
            'handler' => 'static',
            'answer' => 'Có, bạn có thể thanh toán online bằng ví MoMo ngay tại bước thanh toán (khi cửa hàng đang bật cổng MoMo).',
            'action_route' => null,
        ],
        [
            'id' => 'cod',
            'label' => 'Thanh toán COD',
            'handler' => 'static',
            'answer' => 'COD (thanh toán khi nhận hàng) là hình thức bạn trả tiền mặt trực tiếp cho người giao hàng khi nhận đơn — áp dụng khi cửa hàng đang bật tùy chọn này cho đơn giao hàng.',
            'action_route' => null,
        ],
        [
            'id' => 'shipping',
            'label' => 'Phí giao hàng',
            'handler' => 'static',
            'answer' => 'Phí giao hàng được tính dựa trên khoảng cách từ cửa hàng đến địa chỉ nhận hàng, có thể phát sinh phụ thu khi thời tiết xấu (mưa to, bão). Phí chính xác sẽ hiển thị ở bước thanh toán. Đơn nhận tại cửa hàng không tính phí giao hàng.',
            'action_route' => null,
        ],
        [
            'id' => 'order_tracking',
            'label' => 'Theo dõi đơn hàng',
            'handler' => 'order_tracking',
            'answer' => null,
            'action_route' => 'orders',
        ],
        [
            'id' => 'ordering_guide',
            'label' => 'Cách đặt hàng',
            'handler' => 'static',
            'answer' => 'Bạn chọn sản phẩm, tùy chỉnh size/đường/đá/topping, thêm vào giỏ hàng rồi vào giỏ hàng để thanh toán. Có thể đặt online (giao hàng) hoặc đến trực tiếp cửa hàng.',
            'action_route' => 'products',
        ],
        [
            'id' => 'opening_hours',
            'label' => 'Giờ hoạt động',
            'handler' => 'opening_hours',
            'answer' => null,
            'action_route' => null,
        ],
        [
            'id' => 'contact',
            'label' => 'Thông tin liên hệ',
            'handler' => 'contact',
            'answer' => null,
            'action_route' => null,
        ],
    ],
];
