<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            if (!Schema::hasColumn('promotions', 'requires_staff_verification')) {
                // Cờ đánh dấu mã cần nhân viên xác nhận trước khi áp dụng, VD: mã sinh viên, sinh nhật.
                // Khi bật, true: mã bị loại khỏi luồng tự động, resolveAutoPromotion
                // chỉ có thể áp khi lễ tân/khách nhập tay sau khi đã xác nhận điều kiện.
                $table->boolean('requires_staff_verification')->default(false)->after('applies_to');
            }
        });
    }

    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn('requires_staff_verification');
        });
    }
};
