<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Bảng cart_item_toppings/order_item_toppings được dùng thật trong code (CartController,
// OrderService, MomoController, model CartItemTopping) nhưng cart_item_toppings đã bị drift khỏi
// migration gốc (2026_06_17_000000_create_missing_commerce_tables.php) từ rất lâu trên môi trường
// local (bảng thật hiện tại dùng PK int, khác hẳn foreignId() bigint unsigned trong file migration
// hiện tại) nên hasTable() luôn true và không ai phát hiện ra; còn order_item_toppings thì CHƯA TỪNG
// có migration nào tạo cả. Hậu quả: máy chủ mới (migrate lần đầu, vd. Railway) không có 2 bảng này dù
// migrate:status báo mọi migration đã "Ran". Migration này tạo bù 2 bảng nếu còn thiếu, khớp đúng
// kiểu bigint unsigned mà cart_items/order_items/toppings hiện dùng ($table->id() trong migration
// hiện tại) — không đụng gì tới bảng đã tồn tại (hasTable() guard), an toàn chạy lại nhiều lần.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cart_item_toppings')) {
            Schema::create('cart_item_toppings', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->foreignId('cart_item_id')->constrained()->cascadeOnDelete();
                $table->foreignId('topping_id')->constrained()->cascadeOnDelete();
                $table->decimal('price', 10, 2)->nullable();
                $table->unique(['cart_item_id', 'topping_id']);
            });
        }

        if (!Schema::hasTable('order_item_toppings')) {
            Schema::create('order_item_toppings', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
                $table->foreignId('topping_id')->nullable()->constrained()->nullOnDelete();
                $table->string('topping_name')->nullable();
                $table->decimal('topping_price', 10, 2)->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_toppings');
        Schema::dropIfExists('cart_item_toppings');
    }
};
