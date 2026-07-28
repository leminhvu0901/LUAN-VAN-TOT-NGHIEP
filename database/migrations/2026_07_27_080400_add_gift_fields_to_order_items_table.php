<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Đánh dấu dòng quà tặng (Mua X tặng Y) — unit_price luôn = 0, không tính vào doanh thu.
            $table->boolean('is_gift')->default(false)->after('note');
            // Khuyến mãi đã sinh ra dòng quà này. promotions.id là kiểu int (không phải bigint unsigned
            // mặc định của Laravel) -> phải khai integer() khớp kiểu, nếu không MySQL từ chối ALTER vì
            // 2 cột không tương thích (đã xác nhận qua lỗi thật). nullOnDelete để KHÔNG mất lịch sử đơn
            // hàng cũ nếu sau này khuyến mãi bị xóa khỏi hệ thống.
            $table->integer('source_promotion_id')->nullable()->after('is_gift');
            $table->foreign('source_promotion_id')->references('id')->on('promotions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['source_promotion_id']);
            $table->dropColumn(['is_gift', 'source_promotion_id']);
        });
    }
};
