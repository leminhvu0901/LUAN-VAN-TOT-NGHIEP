<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('promotion_combo_items')) {
            return;
        }

        // Dò kiểu thật của cột đích trước khi tạo — xem ghi chú đầy đủ trong migration
        // promotion_products cùng đợt.
        $promotionIdIsBigInt = $this->idColumnIsBigInt('promotions');
        $productIdIsBigInt = $this->idColumnIsBigInt('products');

        Schema::create('promotion_combo_items', function (Blueprint $table) use ($promotionIdIsBigInt, $productIdIsBigInt) {
            $table->engine = 'InnoDB';
            $table->id();

            // Nhiều dòng / promotion (khác promotion_combos là quan hệ 1-1) - mỗi dòng là 1 sản
            // phẩm bắt buộc phải có trong giỏ (kèm số lượng) mới tính là đã mua đủ combo.
            if ($promotionIdIsBigInt) {
                $table->foreignId('promotion_id')->constrained()->cascadeOnDelete();
            } else {
                $table->integer('promotion_id');
                $table->foreign('promotion_id')->references('id')->on('promotions')->cascadeOnDelete();
            }

            if ($productIdIsBigInt) {
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            } else {
                $table->integer('product_id');
                $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            }

            $table->unsignedInteger('quantity');
            $table->timestamps();

            $table->unique(['promotion_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_combo_items');
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
