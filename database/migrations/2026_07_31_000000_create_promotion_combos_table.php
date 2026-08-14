<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('promotion_combos')) {
            return;
        }

        // Dò kiểu thật của cột đích trước khi tạo, xem ghi chú đầy đủ trong migration
        // promotion_products cùng đợt.
        $promotionIdIsBigInt = $this->idColumnIsBigInt('promotions');
        $productIdIsBigInt = $this->idColumnIsBigInt('products');

        Schema::create('promotion_combos', function (Blueprint $table) use ($promotionIdIsBigInt, $productIdIsBigInt) {
            // Ép InnoDB tường minh, xem ghi chú trong migration promotion_products cùng đợt.
            $table->engine = 'InnoDB';
            $table->id();

            // 1 khuyến mãi scope=combo chỉ có đúng 1 cấu hình thưởng -> unique để đảm bảo quan hệ 1-1.
            if ($promotionIdIsBigInt) {
                $table->foreignId('promotion_id')->unique()->constrained()->cascadeOnDelete();
            } else {
                $table->integer('promotion_id')->unique();
                $table->foreign('promotion_id')->references('id')->on('promotions')->cascadeOnDelete();
            }

            // Thành phần "Giảm giá" - TUỲ CHỌN, độc lập với thành phần "Tặng quà" bên dưới.
            // discount_type=null nghĩa là combo này không bật giảm giá, chỉ tặng quà.
            $table->string('discount_type', 10)->nullable(); // percent | fixed
            $table->decimal('discount_value', 12, 2)->nullable();
            // Chỉ có ý nghĩa khi discount_type=percent, trần số tiền giảm tối đa.
            $table->decimal('max_discount_amount', 12, 2)->nullable();

            // Thành phần "Tặng quà" - TUỲ CHỌN, độc lập với thành phần "Giảm giá" ở trên.
            // gift_product_id=null nghĩa là combo này không bật tặng quà, chỉ giảm giá.
            if ($productIdIsBigInt) {
                $table->foreignId('gift_product_id')->nullable()->constrained('products')->cascadeOnDelete();
            } else {
                $table->integer('gift_product_id')->nullable();
                $table->foreign('gift_product_id')->references('id')->on('products')->cascadeOnDelete();
            }
            $table->unsignedInteger('gift_quantity')->nullable();
            $table->boolean('auto_add_gift')->default(true);

            // Giới hạn số LẦN áp dụng combo trong 1 đơn, null = không giới hạn, dùng chung cho cả
            // 2 thành phần thưởng, số lần đủ combo là 1 con số duy nhất, không tách riêng theo thưởng.
            $table->unsignedInteger('max_applications_per_order')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_combos');
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
