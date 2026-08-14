<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Run the migrations.
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'shift_id')) {
                // staff_shifts.id do chính migration này tạo, bigIncrements chuẩn nên không gặp vấn
                // đề "incompatible" như users.id, không cần pattern fallback int/bigint ở đây.
                $table->foreignId('shift_id')->nullable()->after('created_by')->constrained('staff_shifts')->nullOnDelete();
            }
        });
    }

    // Reverse the migrations.
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'shift_id')) {
                $table->dropForeign(['shift_id']);
                $table->dropColumn('shift_id');
            }
        });
    }
};
