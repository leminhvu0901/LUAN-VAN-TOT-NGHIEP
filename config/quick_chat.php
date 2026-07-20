<?php

// Nội dung tĩnh cho chatbox trả lời nhanh (quick-reply, không AI) hiển thị ở frontend.
// JS/Blade chỉ hiển thị, không chứa nội dung nghiệp vụ — mọi text sửa ở đây.

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
    // Ô NHẬP CÂU HỎI TỰ DO (rule-based intent, không AI) — dùng bởi
    // App\Services\QuickChatService + App\Http\Controllers\Frontend\QuickChatController.
    // ============================================================
    'freeform_placeholder' => 'Nhập câu hỏi về Happy Tea...',

    // Câu trả lời khi câu hỏi không khớp intent nào đã định nghĩa (hệ thống không nhận diện được).
    'fallback_freeform' => 'Xin lỗi, mình chưa hiểu rõ câu hỏi của bạn. Bạn có thể hỏi về sản phẩm, giá bán, khuyến mãi, thanh toán, giao hàng hoặc đơn hàng nhé!',

    // Nút gợi ý hiển thị SAU câu fallback ở trên — mỗi nút gửi lại đúng intent đã biết (bỏ qua chấm
    // điểm) qua QuickChatService::askByIntent(). Không kèm sản phẩm/bán chạy gì để tránh khách hiểu
    // nhầm là chatbot đã hiểu câu hỏi.
    'fallback_suggestions' => [
        ['intent_id' => 'product', 'label' => 'Sản phẩm'],
        ['intent_id' => 'product_price', 'label' => 'Giá bán'],
        ['intent_id' => 'promotion', 'label' => 'Khuyến mãi'],
        ['intent_id' => 'payment', 'label' => 'Thanh toán'],
        ['intent_id' => 'shipping', 'label' => 'Giao hàng'],
        ['intent_id' => 'order_tracking', 'label' => 'Theo dõi đơn hàng'],
    ],

    // Câu dẫn khi hiển thị gợi ý làm rõ (câu hỏi 1 từ hoặc nhiều intent điểm gần nhau).
    'clarify_prompt' => 'Bạn muốn hỏi về:',

    // ---- Câu hỏi sản phẩm mơ hồ / không khớp dữ liệu (dùng trong QuickChatService::handleProduct) ----
    // Câu hỏi kiểu "uống gì ngon" — không nêu rõ loại: CHỈ trả lời trung thực, không kèm gì thêm.
    'product_ambiguous_answer' => 'Câu hỏi của bạn chưa nêu rõ loại đồ uống mong muốn.',
    // Yêu cầu mâu thuẫn (vd "tỉnh táo nhưng không cà phê") — nhu cầu chính bị chính điều kiện loại
    // trừ của cùng câu hỏi chặn hết, không còn gì để gợi ý trung thực.
    'product_no_match_answer' => 'Hiện tại mình chưa tìm được gợi ý phù hợp với cả hai yêu cầu. Bạn có thể chọn nhóm trà hoặc đồ uống khác.',

    // ============================================================
    // NHU CẦU ĐỒ UỐNG — cấu hình tập trung, dùng bởi QuickChatService::handleProduct(). Mỗi need có
    // 'keywords' (cụm từ nhận diện ý định khách, có dấu — service tự suy ra bản không dấu) và ĐÚNG 1
    // trong các hiệu ứng sau (Service phân loại need dựa trên field nào có mặt, không hard-code từng
    // tên need trong code):
    //   - preferred_categories: ưu tiên các danh mục này (so theo TÊN DANH MỤC thật, không hard-code
    //     ID). description_keywords (tùy chọn, thêm ngoài yêu cầu gốc): mở rộng khớp thêm theo MÔ TẢ
    //     sản phẩm thật — vd "béo ngậy" xuất hiện ở cả Sữa chua/Đồ uống khác, không chỉ Trà sữa.
    //   - excluded_categories: loại các danh mục này khỏi mọi kết quả (kể cả khi khớp qua need khác).
    //   - sort: 'price_asc' | 'price_desc' | 'best_seller' — sắp xếp lại danh sách, không lọc danh mục.
    //   - answer (không kèm preferred/excluded/sort): chỉ là hướng dẫn thêm, KHÔNG tự lọc sản phẩm
    //     (vd "ít ngọt" — nếu câu hỏi có kèm danh mục cụ thể thì danh mục đó vẫn được lọc riêng, đây
    //     chỉ bổ sung thêm dòng hướng dẫn).
    // 'intro': câu dẫn hiển thị trước danh sách sản phẩm (KHÔNG khẳng định tác dụng y tế/sức khỏe).
    'product_needs' => [
        'alertness' => [
            'keywords' => [
                'tỉnh táo', 'tỉnh ngủ', 'buồn ngủ', 'chống buồn ngủ', 'cần tỉnh', 'cần tập trung',
                'tập trung', 'thức khuya', 'thức đêm', 'nhiều caffeine', 'có caffeine',
            ],
            // CHỈ lọc theo danh mục Cà phê (không kèm description_keywords) — "đậm đà" cũng xuất
            // hiện trong mô tả một số món trà (vị trà đậm), nếu khớp thêm theo mô tả sẽ vô tình kéo
            // cả trà sữa vào kết quả "tỉnh táo", trái với yêu cầu "chỉ gợi ý sản phẩm thuộc cà phê".
            'preferred_categories' => ['cà phê', 'coffee'],
            'intro' => 'Bạn có thể tham khảo các món cà phê thường được lựa chọn khi cần sự tỉnh táo:',
        ],
        'refreshing' => [
            'keywords' => [
                'giải khát', 'thanh mát', 'mát', 'mát lạnh', 'sảng khoái', 'giải nhiệt',
                'nóng quá', 'khát nước', 'trái cây', 'chua ngọt',
            ],
            'preferred_categories' => ['trà trái cây', 'trà', 'nước trái cây', 'đồ uống khác'],
            'description_keywords' => ['giải nhiệt', 'tươi mát', 'thanh mát', 'mát lạnh', 'chua ngọt', 'giải khát'],
            'intro' => 'Bạn có thể tham khảo các món thanh mát và giải khát sau:',
        ],
        'milky' => [
            'keywords' => ['béo', 'béo béo', 'nhiều sữa', 'vị sữa', 'ngọt béo', 'thơm sữa'],
            'preferred_categories' => ['trà sữa', 'sữa chua', 'matcha'],
            'description_keywords' => ['béo ngậy', 'béo thơm', 'kem béo'],
            'intro' => 'Bạn có thể tham khảo các món có vị sữa và béo nhẹ sau:',
        ],
        'less_sweet' => [
            // Bỏ "nhạt" đơn lẻ: khi bỏ dấu trùng với "nhất" (đắt/rẻ NHẤT, bán chạy NHẤT) — gây dính
            // nhầm hướng dẫn giảm đường vào câu hỏi sắp xếp giá/bán chạy. Dùng "nhạt quá"/"khá nhạt"
            // cụ thể hơn nếu cần bắt thêm biến thể này sau.
            'keywords' => ['ít ngọt', 'không ngọt', 'bớt ngọt', 'sợ ngọt', 'giảm đường', 'không đường'],
            'answer' => 'Bạn có thể chọn mức đường thấp hơn khi thêm sản phẩm vào giỏ hàng.',
        ],
        'no_coffee' => [
            'keywords' => ['không cà phê', 'không uống cà phê', 'không coffee', 'không caffeine', 'không có caffeine'],
            'excluded_categories' => ['cà phê', 'coffee'],
            'intro' => 'Bạn có thể tham khảo các món không thuộc nhóm cà phê sau:',
        ],
        'cheap' => [
            'keywords' => ['rẻ', 'rẻ nhất', 'giá thấp', 'giá thấp nhất', 'giá mềm', 'ít tiền', 'tiết kiệm', 'món nào rẻ'],
            'sort' => 'price_asc',
            'intro' => 'Các món có giá khởi điểm thấp nhất tại Happy Tea gồm:',
        ],
        'expensive' => [
            'keywords' => ['đắt nhất', 'mắc nhất', 'giá cao nhất', 'giá đắt nhất', 'đắt tiền nhất', 'giá mắc nhất'],
            'sort' => 'price_desc',
            'intro' => 'Các món có giá khởi điểm cao nhất tại Happy Tea gồm:',
        ],
        'popular' => [
            'keywords' => ['ngon', 'bán chạy', 'nổi bật', 'nhiều người uống', 'được yêu thích', 'món hot', 'best seller'],
            'sort' => 'best_seller',
            'intro' => 'Bạn có thể tham khảo các món bán chạy của Happy Tea:',
        ],
    ],

    // Mỗi intent: id (duy nhất), label (dùng làm nhãn nút gợi ý), keywords (khớp +2 điểm/từ,
    // có dấu — service tự suy ra bản không dấu qua Str::ascii()), phrases (cụm đặc trưng hơn,
    // khớp +3 điểm/cụm), priority (chỉ dùng để sắp thứ tự khi hòa điểm, KHÔNG cộng vào điểm),
    // handler (static|product|promotion|order_tracking|opening_hours|contact), answer (dùng cho
    // handler static hoặc làm câu dẫn trước danh sách card với handler product/promotion),
    // action_route (tên route Laravel nếu có nút hành động), suggest_intents (chỉ có ở intent
    // "payment": kèm thêm gợi ý bấm sâu hơn bên cạnh câu trả lời tổng quan).
    'intents' => [
        [
            'id' => 'product',
            'label' => 'Sản phẩm',
            'keywords' => ['sản phẩm', 'trà', 'cà phê', 'đồ uống', 'thức uống', 'menu', 'món', 'uống', 'giải nhiệt', 'giải khát'],
            'phrases' => ['loại trà sữa nào', 'sản phẩm nào đang bán', 'đang bán những gì', 'uống gì giải nhiệt', 'có gì ngon', 'muốn uống gì', 'gợi ý món uống', 'gợi ý đồ uống'],
            'priority' => 4,
            'handler' => 'product',
            'answer' => 'Đây là một số sản phẩm bạn có thể quan tâm:',
            'action_route' => 'products',
        ],
        [
            'id' => 'product_price',
            'label' => 'Giá sản phẩm',
            'keywords' => ['giá'],
            'phrases' => ['giá bao nhiêu', 'giá sản phẩm', 'giá tiền', 'giá cả'],
            'priority' => 4,
            'handler' => 'product',
            'answer' => 'Giá các sản phẩm hiện tại:',
            'action_route' => 'products',
        ],
        [
            'id' => 'product_option',
            'label' => 'Size, topping, đường, đá',
            'keywords' => ['topping', 'size', 'đường', 'tùy chọn'],
            'phrases' => ['có topping gì', 'chọn size', 'mức đường', 'mức đá', 'ít đá', 'không đá', 'đá riêng', 'đá chung'],
            'priority' => 3,
            'handler' => 'static',
            'answer' => 'Mỗi sản phẩm đều có thể tùy chỉnh size, mức đường, mức đá và thêm topping ngay khi bạn thêm vào giỏ hàng.',
            'action_route' => null,
        ],
        [
            'id' => 'promotion',
            'label' => 'Khuyến mãi hiện có',
            'keywords' => ['khuyến mãi', 'ưu đãi', 'giảm giá', 'mã giảm giá'],
            'phrases' => ['có khuyến mãi gì', 'khuyến mãi hôm nay', 'có mã giảm giá không'],
            'priority' => 4,
            'handler' => 'promotion',
            'answer' => 'Các khuyến mãi đang áp dụng:',
            'action_route' => null,
        ],
        [
            'id' => 'promotion_condition',
            'label' => 'Điều kiện dùng mã giảm giá',
            'keywords' => ['điều kiện'],
            'phrases' => ['điều kiện sử dụng mã', 'điều kiện áp dụng', 'cần mua bao nhiêu để được giảm'],
            'priority' => 4,
            'handler' => 'promotion',
            'answer' => 'Điều kiện áp dụng của từng chương trình (đơn tối thiểu, số lượng tối thiểu, mức giảm tối đa) được ghi rõ trong từng khuyến mãi bên dưới:',
            'action_route' => null,
        ],
        [
            'id' => 'payment',
            'label' => 'Phương thức thanh toán',
            'keywords' => ['thanh toán'],
            'phrases' => ['thanh toán thế nào', 'hình thức thanh toán', 'phương thức thanh toán'],
            'priority' => 3,
            'handler' => 'static',
            'answer' => 'Đơn đặt online có thể thanh toán bằng MoMo hoặc tiền mặt khi nhận hàng (COD), tùy theo cấu hình cửa hàng đang bật. Đơn tạo tại quầy do lễ tân xử lý có thể thanh toán bằng tiền mặt hoặc MoMo.',
            'action_route' => null,
            'suggest_intents' => ['momo', 'cod'],
        ],
        [
            'id' => 'momo',
            'label' => 'Thanh toán MoMo',
            'keywords' => ['momo', 'ví điện tử'],
            'phrases' => ['thanh toán momo', 'trả bằng momo', 'quét mã momo', 'thanh toán qua momo'],
            'priority' => 5,
            'handler' => 'static',
            'answer' => 'Có, bạn có thể thanh toán online bằng ví MoMo ngay tại bước thanh toán (khi cửa hàng đang bật cổng MoMo).',
            'action_route' => null,
        ],
        [
            'id' => 'cod',
            'label' => 'Thanh toán COD',
            'keywords' => ['cod'],
            'phrases' => ['trả tiền khi nhận hàng', 'thanh toán khi nhận hàng', 'cod là gì', 'khi nhận hàng'],
            'priority' => 5,
            'handler' => 'static',
            'answer' => 'COD (thanh toán khi nhận hàng) là hình thức bạn trả tiền mặt trực tiếp cho người giao hàng khi nhận đơn — áp dụng khi cửa hàng đang bật tùy chọn này cho đơn giao hàng.',
            'action_route' => null,
        ],
        [
            'id' => 'shipping',
            'label' => 'Phí giao hàng',
            'keywords' => ['ship', 'giao hàng', 'vận chuyển'],
            'phrases' => ['phí ship bao nhiêu', 'giao hàng tính tiền thế nào', 'phí vận chuyển bao nhiêu', 'phí giao hàng'],
            'priority' => 5,
            'handler' => 'static',
            'answer' => 'Phí giao hàng được tính dựa trên khoảng cách từ cửa hàng đến địa chỉ nhận hàng, có thể phát sinh phụ thu khi thời tiết xấu (mưa to, bão). Phí chính xác sẽ hiển thị ở bước thanh toán. Đơn nhận tại cửa hàng không tính phí giao hàng.',
            'action_route' => null,
        ],
        [
            'id' => 'order_tracking',
            'label' => 'Theo dõi đơn hàng',
            'keywords' => ['đơn hàng', 'theo dõi', 'tra cứu'],
            'phrases' => ['xem đơn hàng ở đâu', 'theo dõi đơn hàng', 'đơn hàng của tôi'],
            'priority' => 5,
            'handler' => 'order_tracking',
            'answer' => null,
            'action_route' => 'orders',
        ],
        [
            'id' => 'ordering_guide',
            'label' => 'Cách đặt hàng',
            'keywords' => ['đặt hàng', 'mua hàng'],
            'phrases' => ['làm sao để đặt hàng', 'cách đặt hàng như thế nào', 'đặt hàng như thế nào'],
            'priority' => 4,
            'handler' => 'static',
            'answer' => 'Bạn chọn sản phẩm, tùy chỉnh size/đường/đá/topping, thêm vào giỏ hàng rồi vào giỏ hàng để thanh toán. Có thể đặt online (giao hàng) hoặc đến trực tiếp cửa hàng.',
            'action_route' => 'products',
        ],
        [
            'id' => 'opening_hours',
            'label' => 'Giờ hoạt động',
            'keywords' => ['giờ mở cửa', 'giờ hoạt động', 'mở cửa'],
            'phrases' => ['mở cửa lúc mấy giờ', 'giờ hoạt động thế nào', 'mấy giờ mở cửa'],
            'priority' => 4,
            'handler' => 'opening_hours',
            'answer' => null,
            'action_route' => null,
        ],
        [
            'id' => 'contact',
            'label' => 'Thông tin liên hệ',
            'keywords' => ['địa chỉ', 'liên hệ', 'số điện thoại', 'email'],
            'phrases' => ['thông tin liên hệ', 'địa chỉ cửa hàng', 'số điện thoại cửa hàng'],
            'priority' => 3,
            'handler' => 'contact',
            'answer' => null,
            'action_route' => null,
        ],
    ],
];
