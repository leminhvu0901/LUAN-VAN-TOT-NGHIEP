<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('promotion_buy_x_get_y')) {
            return;
        }

        // Dò kiểu thật của cột đích trước khi tạo, xem ghi chú đầy đủ trong migration
        // promotion_products cùng ngày.
        $promotionIdIsBigInt = $this->idColumnIsBigInt('promotions');
        $productIdIsBigInt = $this->idColumnIsBigInt('products');

        Schema::create('promotion_buy_x_get_y', function (Blueprint $table) use ($promotionIdIsBigInt, $productIdIsBigInt) {
            // Ép InnoDB tường minh, xem ghi chú trong migration promotion_products cùng ngày.
            $table->engine = 'InnoDB';
            $table->id();

            // 1 khuyến mãi scope=buy_x_get_y chỉ có đúng 1 cấu hình mua-tặng -> unique để đảm bảo quan hệ 1-1.
            if ($promotionIdIsBigInt) {
                $table->foreignId('promotion_id')->unique()->constrained()->cascadeOnDelete();
            } else {
                $table->integer('promotion_id')->unique();
                $table->foreign('promotion_id')->references('id')->on('promotions')->cascadeOnDelete();
            }

            if ($productIdIsBigInt) {
                $table->foreignId('buy_product_id')->constrained('products')->cascadeOnDelete();
            } else {
                $table->integer('buy_product_id');
                $table->foreign('buy_product_id')->references('id')->on('products')->cascadeOnDelete();
            }
            $table->unsignedInteger('buy_quantity');

            if ($productIdIsBigInt) {
                $table->foreignId('gift_product_id')->constrained('products')->cascadeOnDelete();
            } else {
                $table->integer('gift_product_id');
                $table->foreign('gift_product_id')->references('id')->on('products')->cascadeOnDelete();
            }
            $table->unsignedInteger('gift_quantity');

            // Giới hạn số LẦN áp dụng công thức trong 1 đơn (null = không giới hạn, số lần = floor(mua/X)).
            $table->unsignedInteger('max_applications_per_order')->nullable();
            // Có tự động thêm quà vào đơn khi đủ điều kiện hay không, mặc định có.
            $table->boolean('auto_add_gift')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_buy_x_get_y');
    }

    private function idColumnIsBigInt(string $table): bool
    {
        if (DB::getDriverName() !== 'mysql') {
            return true;
        }

        $type = strtolower((string) (DB::selectOne("SHOW COLUMNS FROM `{$table}` WHERE Field = 'id'")->Type ?? ''));
        return str_contains($type, 'bigint');
    }
};
