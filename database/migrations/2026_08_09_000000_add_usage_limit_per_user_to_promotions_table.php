<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            if (!Schema::hasColumn('promotions', 'usage_limit_per_user')) {
                // Số lần TỐI ĐA mà 1 tài khoản được dùng mã này. NULL = không giới hạn.
                // Giữ nguyên hành vi cũ, mỗi người chỉ dùng 1 lần bằng cách backfill = 1 cho mã đã có.
                $table->unsignedInteger('usage_limit_per_user')->nullable()->after('usage_limit');
            }
        });

        DB::table('promotions')->whereNull('usage_limit_per_user')->update(['usage_limit_per_user' => 1]);
    }

    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn('usage_limit_per_user');
        });
    }
};
