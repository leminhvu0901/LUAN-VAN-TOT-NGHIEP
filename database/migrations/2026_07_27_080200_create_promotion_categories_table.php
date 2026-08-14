<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('promotion_categories')) {
            return;
        }

        // Dò kiểu thật của cột đích trước khi tạo, xem ghi chú đầy đủ trong migration
        // promotion_products cùng ngày.
        $promotionIdIsBigInt = $this->idColumnIsBigInt('promotions');
        $categoryIdIsBigInt = $this->idColumnIsBigInt('categories');

        Schema::create('promotion_categories', function (Blueprint $table) use ($promotionIdIsBigInt, $categoryIdIsBigInt) {
            // Ép InnoDB tường minh, xem ghi chú trong migration promotion_products cùng ngày.
            $table->engine = 'InnoDB';
            $table->id();

            if ($promotionIdIsBigInt) {
                $table->foreignId('promotion_id')->constrained()->cascadeOnDelete();
            } else {
                $table->integer('promotion_id');
                $table->foreign('promotion_id')->references('id')->on('promotions')->cascadeOnDelete();
            }

            if ($categoryIdIsBigInt) {
                $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            } else {
                $table->integer('category_id');
                $table->foreign('category_id')->references('id')->on('categories')->cascadeOnDelete();
            }

            $table->timestamps();
            $table->unique(['promotion_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_categories');
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
