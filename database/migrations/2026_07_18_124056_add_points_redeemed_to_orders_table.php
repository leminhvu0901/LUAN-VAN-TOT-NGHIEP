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
            if (!Schema::hasColumn('orders', 'points_redeemed')) {
                $table->unsignedInteger('points_redeemed')->default(0)->after('loyalty_points_awarded');
            }
        });
    }

    // Reverse the migrations.
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'points_redeemed')) {
                $table->dropColumn('points_redeemed');
            }
        });
    }
};
