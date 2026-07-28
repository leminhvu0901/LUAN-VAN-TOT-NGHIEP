<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_categories', function (Blueprint $table) {
            // Ép InnoDB tường minh — xem ghi chú trong migration promotion_products cùng ngày.
            $table->engine = 'InnoDB';
            $table->id();
            // promotions/categories dùng id kiểu int (không phải bigint unsigned) -> khai integer() khớp kiểu.
            $table->integer('promotion_id');
            $table->integer('category_id');
            $table->timestamps();

            $table->foreign('promotion_id')->references('id')->on('promotions')->cascadeOnDelete();
            $table->foreign('category_id')->references('id')->on('categories')->cascadeOnDelete();
            $table->unique(['promotion_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_categories');
    }
};
