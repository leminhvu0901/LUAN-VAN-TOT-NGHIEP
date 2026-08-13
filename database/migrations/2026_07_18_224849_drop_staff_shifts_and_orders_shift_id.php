<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Run the migrations.
    public function up(): void
    {
        // Chức năng "Ca làm việc" đã bị loại bỏ khỏi toàn bộ code (routes, controller, model,
        // view, JS) — cột/bảng này không còn được đọc/ghi ở đâu, dọn dẹp schema theo yêu cầu.
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'shift_id')) {
                $table->dropForeign(['shift_id']);
                $table->dropColumn('shift_id');
            }
        });

        Schema::dropIfExists('staff_shifts');
    }

    // Reverse the migrations.
    public function down(): void
    {
        // Khôi phục lại đúng schema gốc (không khôi phục được dữ liệu ca cũ đã mất khi drop).
        $usersUsesBigInt = DB::getDriverName() !== 'mysql'
            || str_contains(strtolower((string) (DB::selectOne("SHOW COLUMNS FROM users WHERE Field = 'id'")->Type ?? '')), 'bigint');

        Schema::create('staff_shifts', function (Blueprint $table) use ($usersUsesBigInt) {
            $table->engine = 'InnoDB';
            $table->id();
            if ($usersUsesBigInt) {
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            } else {
                $table->integer('user_id');
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            }
            $table->timestamp('opened_at');
            $table->decimal('opening_cash', 12, 2)->default(0);
            $table->string('opening_note', 500)->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->decimal('closing_cash', 12, 2)->nullable();
            $table->decimal('expected_cash', 12, 2)->nullable();
            $table->decimal('cash_diff', 12, 2)->nullable();
            $table->string('closing_note', 500)->nullable();
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'shift_id')) {
                $table->foreignId('shift_id')->nullable()->after('created_by')->constrained('staff_shifts')->nullOnDelete();
            }
        });
    }
};
