<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$products = DB::table('products')->get();

$comments = [
    'Sản phẩm rất ngon, sẽ ủng hộ dài dài!',
    'Vị đậm đà, cực kỳ ưng ý.',
    'Nước uống ngon, đóng gói cẩn thận.',
    'Mình rất thích món này, điểm 10 chất lượng.',
    'Hương vị tuyệt vời, không chê vào đâu được!',
    'Giao hàng nhanh, đồ uống siêu đỉnh.',
    'Uống một lần là ghiền luôn.',
    'Cực kỳ đáng tiền, rất recommend.',
    'Đã mua nhiều lần và chưa bao giờ thất vọng.',
    'Tuyệt vời ông mặt trời ☀️',
    'Chất lượng tốt, giá hợp lý.',
    'Mùi vị rất thanh, không bị ngọt gắt.'
];

$totalReviews = 0;

foreach ($products as $p) {
    // Generate 15 to 40 reviews per product
    $numReviews = rand(15, 40);
    $totalReviews += $numReviews;
    
    $inserts = [];
    for ($i = 0; $i < $numReviews; $i++) {
        // Mostly 4 and 5 stars, rarely 3
        $rating = rand(1, 10) > 2 ? rand(4, 5) : 3; 
        $comment = $comments[array_rand($comments)];
        $inserts[] = [
            'user_id' => 1,
            'product_id' => $p->id,
            'order_id' => 1,
            'rating' => $rating,
            'comment' => $comment,
            'is_visible' => 1,
            'created_at' => now()->subDays(rand(1, 30)),
            'updated_at' => now()->subDays(rand(1, 30))
        ];
    }
    
    DB::table('reviews')->insert($inserts);
    echo "Inserted {$numReviews} reviews for Product ID: {$p->id}\n";
}

echo "Successfully seeded {$totalReviews} random reviews for all products!\n";
