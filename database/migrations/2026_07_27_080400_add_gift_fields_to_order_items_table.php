<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Đánh dấu dòng quà tặng (Mua X tặng Y) — unit_price luôn = 0, không tính vào doanh thu.
            if (!Schema::hasColumn('order_items', 'is_gift')) {
                $table->boolean('is_gift')->default(false)->after('note');
            }
        });

        if (!Schema::hasColumn('order_items', 'source_promotion_id')) {
            // Dò kiểu thật của promotions.id trước khi thêm cột — xem ghi chú đầy đủ trong migration
            // promotion_products cùng đợt. nullOnDelete để KHÔNG mất lịch sử đơn hàng cũ nếu sau này
            // khuyến mãi bị xóa khỏi hệ thống.
            $promotionIdIsBigInt = $this->idColumnIsBigInt('promotions');

            Schema::table('order_items', function (Blueprint $table) use ($promotionIdIsBigInt) {
                if ($promotionIdIsBigInt) {
                    $table->foreignId('source_promotion_id')->nullable()->after('is_gift')
                        ->constrained('promotions')->nullOnDelete();
                } else {
                    $table->integer('source_promotion_id')->nullable()->after('is_gift');
                    $table->foreign('source_promotion_id')->references('id')->on('promotions')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['source_promotion_id']);
            $table->dropColumn(['is_gift', 'source_promotion_id']);
        });
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
