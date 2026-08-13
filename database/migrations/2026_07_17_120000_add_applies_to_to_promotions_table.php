<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Phân loại khuyến mãi theo kênh áp dụng: 'all' (mặc định, không đổi hành vi mã cũ),
    // 'pickup' (chỉ đơn tại quầy), 'delivery' (chỉ đơn giao hàng) — để khuyến mãi tại quầy
    // (lễ tân tự động áp dụng) không bị lẫn với khuyến mãi giao hàng và ngược lại.
    public function up(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            if (!Schema::hasColumn('promotions', 'applies_to')) {
                $table->enum('applies_to', ['all', 'pickup', 'delivery'])->default('all')->after('apply_for');
            }
        });
    }

    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            if (Schema::hasColumn('promotions', 'applies_to')) {
                $table->dropColumn('applies_to');
            }
        });
    }
};
