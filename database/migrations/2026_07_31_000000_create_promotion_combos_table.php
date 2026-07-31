<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('promotion_combos')) {
            Schema::create('promotion_combos', function (Blueprint $table) {
                // Ép InnoDB tường minh — xem ghi chú trong migration promotion_products cùng đợt.
                $table->engine = 'InnoDB';
                $table->id();
                // 1 khuyến mãi scope=combo chỉ có đúng 1 cấu hình thưởng -> unique để đảm bảo quan hệ 1-1.
                $table->integer('promotion_id')->unique();

                // Thành phần "Giảm giá" - TUỲ CHỌN, độc lập với thành phần "Tặng quà" bên dưới.
                // discount_type=null nghĩa là combo này không bật giảm giá (chỉ tặng quà).
                $table->string('discount_type', 10)->nullable(); // percent | fixed
                $table->decimal('discount_value', 12, 2)->nullable();
                // Chỉ có ý nghĩa khi discount_type=percent (trần số tiền giảm tối đa).
                $table->decimal('max_discount_amount', 12, 2)->nullable();

                // Thành phần "Tặng quà" - TUỲ CHỌN, độc lập với thành phần "Giảm giá" ở trên.
                // gift_product_id=null nghĩa là combo này không bật tặng quà (chỉ giảm giá).
                $table->integer('gift_product_id')->nullable();
                $table->unsignedInteger('gift_quantity')->nullable();
                $table->boolean('auto_add_gift')->default(true);

                // Giới hạn số LẦN áp dụng combo trong 1 đơn (null = không giới hạn), dùng chung cho cả
                // 2 thành phần thưởng (số lần đủ combo là 1 con số duy nhất, không tách riêng theo thưởng).
                $table->unsignedInteger('max_applications_per_order')->nullable();
                $table->timestamps();

                $table->foreign('promotion_id')->references('id')->on('promotions')->cascadeOnDelete();
                $table->foreign('gift_product_id')->references('id')->on('products')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_combos');
    }
};
