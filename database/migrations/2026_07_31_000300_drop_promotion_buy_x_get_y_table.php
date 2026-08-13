<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Bước cuối cùng của rework "Mua X tặng Y" -> "Combo": xoá hẳn bảng cũ, tách riêng khỏi migration
// data (2026_07_31_000200) để rollback lần lượt từng bước được — rollback riêng migration này chỉ
// dựng lại schema rỗng, migration data phía trước mới lo dựng lại dữ liệu.
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('promotion_buy_x_get_y');
    }

    public function down(): void
    {
        if (!Schema::hasTable('promotion_buy_x_get_y')) {
            Schema::create('promotion_buy_x_get_y', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->integer('promotion_id')->unique();
                $table->integer('buy_product_id');
                $table->unsignedInteger('buy_quantity');
                $table->integer('gift_product_id');
                $table->unsignedInteger('gift_quantity');
                $table->unsignedInteger('max_applications_per_order')->nullable();
                $table->boolean('auto_add_gift')->default(true);
                $table->timestamps();

                $table->foreign('promotion_id')->references('id')->on('promotions')->cascadeOnDelete();
                $table->foreign('buy_product_id')->references('id')->on('products')->cascadeOnDelete();
                $table->foreign('gift_product_id')->references('id')->on('products')->cascadeOnDelete();
            });
        }
    }
};
