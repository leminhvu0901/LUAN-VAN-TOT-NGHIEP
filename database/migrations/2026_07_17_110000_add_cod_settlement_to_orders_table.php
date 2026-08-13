<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Đối soát tiền COD: nhân viên vận chuyển thu tiền mặt từ khách khi hoàn thành đơn COD, sau đó
    // phải nộp lại cho lễ tân/quầy. cod_settled_at/by ghi nhận thời điểm + người xác nhận đã nhận
    // lại tiền — giúp lễ tân biết chính xác đơn nào shipper đã nộp, đơn nào còn giữ trong người.
    public function up(): void
    {
        $usersUsesBigInt = DB::getDriverName() !== 'mysql'
            || str_contains(strtolower((string) (DB::selectOne("SHOW COLUMNS FROM users WHERE Field = 'id'")->Type ?? '')), 'bigint');

        Schema::table('orders', function (Blueprint $table) use ($usersUsesBigInt) {
            if (!Schema::hasColumn('orders', 'cod_settled_at')) {
                $table->timestamp('cod_settled_at')->nullable()->after('delivery_failed_at');
            }
            if (!Schema::hasColumn('orders', 'cod_settled_by')) {
                if ($usersUsesBigInt) {
                    $table->foreignId('cod_settled_by')->nullable()->after('cod_settled_at')->constrained('users')->nullOnDelete();
                } else {
                    $table->integer('cod_settled_by')->nullable()->after('cod_settled_at');
                    $table->foreign('cod_settled_by')->references('id')->on('users')->nullOnDelete();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'cod_settled_by')) {
                $table->dropForeign(['cod_settled_by']);
                $table->dropColumn('cod_settled_by');
            }
            if (Schema::hasColumn('orders', 'cod_settled_at')) {
                $table->dropColumn('cod_settled_at');
            }
        });
    }
};
