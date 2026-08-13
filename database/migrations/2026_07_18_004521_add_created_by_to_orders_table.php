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
        // unsignedBigInteger, gây lỗi "incompatible" khi ràng buộc khóa ngoại. Cùng pattern đã dùng
        // cho delivery_staff_id/assigned_by ở migration 2026_07_17_090100.
        $usersUsesBigInt = DB::getDriverName() !== 'mysql'
            || str_contains(strtolower((string) (DB::selectOne("SHOW COLUMNS FROM users WHERE Field = 'id'")->Type ?? '')), 'bigint');

        Schema::table('orders', function (Blueprint $table) use ($usersUsesBigInt) {
            if (!Schema::hasColumn('orders', 'created_by')) {
                if ($usersUsesBigInt) {
                    $table->foreignId('created_by')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
                } else {
                    $table->integer('created_by')->nullable()->after('user_id');
                    $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
                }
            }
        }); 
    }

    // Reverse the migrations.
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'created_by')) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            }
        });
    }
};
