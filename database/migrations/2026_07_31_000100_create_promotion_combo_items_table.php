<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('promotion_combo_items')) {
            Schema::create('promotion_combo_items', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                // Nhiều dòng / promotion (khác promotion_combos là quan hệ 1-1) - mỗi dòng là 1 sản
                // phẩm bắt buộc phải có trong giỏ (kèm số lượng) mới tính là đã mua đủ combo.
                $table->integer('promotion_id');
                $table->integer('product_id');
                $table->unsignedInteger('quantity');
                $table->timestamps();

                $table->unique(['promotion_id', 'product_id']);
                $table->foreign('promotion_id')->references('id')->on('promotions')->cascadeOnDelete();
                $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_combo_items');
    }
};
