<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            // Phạm vi áp dụng giảm giá. 'order' = toàn đơn (hành vi cũ, mặc định cho dữ liệu hiện có
            // để percent/fixed đang chạy không đổi hành vi). 'product'/'category' = chỉ tính giảm trên
            // các dòng sản phẩm/danh mục được chọn (xem bảng promotion_products/promotion_categories).
            // 'buy_x_get_y' = mua X tặng Y (xem bảng promotion_buy_x_get_y) — không cạnh tranh với 3
            // loại giảm giá tiền, được cộng thêm độc lập.
            $table->string('scope', 20)->default('order')->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn('scope');
        });
    }
};
