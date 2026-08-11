<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('promotion_products')) {
            return;
        }

        // Trên CSDL production/local đã tồn tại từ trước migration này, promotions.id và products.id
        // là INT thường (bảng được tạo ngoài migration, không phải bởi $table->id()). Nhưng trên một
        // CSDL HOÀN TOÀN MỚI (CI, hoặc lần deploy đầu tiên), 2 bảng đó do chính migration
        // create_missing_commerce_tables tạo bằng $table->id() -> BIGINT UNSIGNED. Phải dò đúng kiểu
        // thật của cột đích rồi khai cột tham chiếu khớp kiểu, nếu không MySQL từ chối tạo khóa ngoại
        // (lỗi 3780: Referencing column and referenced column are incompatible) — cùng kỹ thuật với
        // migration ensure_inventory_movements_table.
        $promotionIdIsBigInt = $this->idColumnIsBigInt('promotions');
        $productIdIsBigInt = $this->idColumnIsBigInt('products');

        Schema::create('promotion_products', function (Blueprint $table) use ($promotionIdIsBigInt, $productIdIsBigInt) {
            // Ép InnoDB tường minh — server MySQL của môi trường này có default_storage_engine=MyISAM,
            // nếu không ép thì bảng bị tạo MyISAM và khóa ngoại bị lờ đi âm thầm (không báo lỗi).
            $table->engine = 'InnoDB';
            $table->id();

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

            $table->timestamps();
            $table->unique(['promotion_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_products');
    }

    /**
     * Dò kiểu thật của cột id trên bảng $table: true nếu là bigint unsigned (CSDL mới, do
     * $table->id() tạo), false nếu là int thường (CSDL production/local đã tồn tại từ trước).
     */
    private function idColumnIsBigInt(string $table): bool
    {
        if (DB::getDriverName() !== 'mysql') {
            return true; // sqlite/pgsql khi chạy test cục bộ: coi như bigint, khớp $table->id() mặc định
        }

        $type = strtolower((string) (DB::selectOne("SHOW COLUMNS FROM `{$table}` WHERE Field = 'id'")->Type ?? ''));
        return str_contains($type, 'bigint');
    }
};
