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
        // users.id trong DB này là `int` thường (không phải bigint) — foreignId() mặc định tạo
        // unsignedBigInteger, gây lỗi "incompatible" khi ràng buộc khóa ngoại. Cùng pattern đã
        // dùng cho orders.id ở migration 2026_07_14_000000_harden_commerce_workflows.php.
        $usersUsesBigInt = DB::getDriverName() !== 'mysql'
            || str_contains(strtolower((string) (DB::selectOne("SHOW COLUMNS FROM users WHERE Field = 'id'")->Type ?? '')), 'bigint');

        Schema::table('orders', function (Blueprint $table) use ($usersUsesBigInt) {
            if (!Schema::hasColumn('orders', 'delivery_staff_id')) {
                if ($usersUsesBigInt) {
                    $table->foreignId('delivery_staff_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
                } else {
                    $table->integer('delivery_staff_id')->nullable()->after('user_id');
                    $table->foreign('delivery_staff_id')->references('id')->on('users')->nullOnDelete();
                }
            }
            if (!Schema::hasColumn('orders', 'assigned_at')) {
                $table->timestamp('assigned_at')->nullable();
            }
            if (!Schema::hasColumn('orders', 'assigned_by')) {
                if ($usersUsesBigInt) {
                    $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
                } else {
                    $table->integer('assigned_by')->nullable();
                    $table->foreign('assigned_by')->references('id')->on('users')->nullOnDelete();
                }
            }
            if (!Schema::hasColumn('orders', 'delivery_failed_reason')) {
                $table->string('delivery_failed_reason', 500)->nullable();
            }
            if (!Schema::hasColumn('orders', 'delivery_failed_at')) {
                $table->timestamp('delivery_failed_at')->nullable();
            }
        });
    }

    // Reverse the migrations.
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'delivery_failed_at')) {
                $table->dropColumn('delivery_failed_at');
            }
            if (Schema::hasColumn('orders', 'delivery_failed_reason')) {
                $table->dropColumn('delivery_failed_reason');
            }
            if (Schema::hasColumn('orders', 'assigned_by')) {
                $table->dropConstrainedForeignId('assigned_by');
            }
            if (Schema::hasColumn('orders', 'assigned_at')) {
                $table->dropColumn('assigned_at');
            }
            if (Schema::hasColumn('orders', 'delivery_staff_id')) {
                $table->dropConstrainedForeignId('delivery_staff_id');
            }
        });
    }
};
