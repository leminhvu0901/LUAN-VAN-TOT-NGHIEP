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
            // Thời điểm đơn hàng chuyển sang trạng thái hoàn thành, dùng để thống kê số đơn giao theo ngày/tuần/tháng/năm
            $table->timestamp('completed_at')->nullable()->after('delivery_failed_at');
        });
    }

    // Reverse the migrations.
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('completed_at');
        });
    }
};
