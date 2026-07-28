<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_buy_x_get_y', function (Blueprint $table) {
            // Ép InnoDB tường minh — xem ghi chú trong migration promotion_products cùng ngày.
            $table->engine = 'InnoDB';
            $table->id();
            // promotions/products dùng id kiểu int (không phải bigint unsigned) -> khai integer() khớp kiểu.
            // 1 khuyến mãi scope=buy_x_get_y chỉ có đúng 1 cấu hình mua-tặng -> unique để đảm bảo quan hệ 1-1.
            $table->integer('promotion_id')->unique();
            $table->integer('buy_product_id');
            $table->unsignedInteger('buy_quantity');
            $table->integer('gift_product_id');
            $table->unsignedInteger('gift_quantity');
            // Giới hạn số LẦN áp dụng công thức trong 1 đơn (null = không giới hạn, số lần = floor(mua/X)).
            $table->unsignedInteger('max_applications_per_order')->nullable();
            // Có tự động thêm quà vào đơn khi đủ điều kiện hay không (mặc định có).
            $table->boolean('auto_add_gift')->default(true);
            $table->timestamps();

            $table->foreign('promotion_id')->references('id')->on('promotions')->cascadeOnDelete();
            $table->foreign('buy_product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('gift_product_id')->references('id')->on('products')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_buy_x_get_y');
    }
};
