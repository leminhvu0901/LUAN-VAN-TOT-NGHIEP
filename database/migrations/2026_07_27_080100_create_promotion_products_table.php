<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('promotion_products')) {
            Schema::create('promotion_products', function (Blueprint $table) {
                // Ép InnoDB tường minh — server MySQL của môi trường này có default_storage_engine=MyISAM,
                // nếu không ép thì bảng bị tạo MyISAM và khóa ngoại bị lờ đi âm thầm (không báo lỗi).
                $table->engine = 'InnoDB';
                $table->id();
                // Bảng promotions/products trong dự án dùng id kiểu int (không phải bigint unsigned mặc
                // định của Laravel) -> phải khai integer() khớp kiểu, foreignId() sẽ sai kiểu và bị MySQL
                // từ chối ngay khi bảng là InnoDB thật (đã xác nhận qua lỗi thật khi thử ở order_items).
                $table->integer('promotion_id');
                $table->integer('product_id');
                $table->timestamps();

                $table->foreign('promotion_id')->references('id')->on('promotions')->cascadeOnDelete();
                $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
                $table->unique(['promotion_id', 'product_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_products');
    }
};
