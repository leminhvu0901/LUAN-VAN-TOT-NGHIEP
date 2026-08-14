<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Cờ đánh dấu lễ tân đã gửi yêu cầu Admin phê duyệt đơn hàng giá trị lớn.
            // true = đang chờ admin duyệt, false, mặc định = không cần duyệt hoặc đã duyệt xong.
            $table->boolean('needs_admin_approval')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('needs_admin_approval');
        });
    }
};
