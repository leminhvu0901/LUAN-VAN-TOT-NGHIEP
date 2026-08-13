<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    // Run the migrations.
    public function up(): void
    {
        Schema::table('promotion_buy_x_get_y', function (Blueprint $table) {
            //
        });
    }

    // Reverse the migrations.
    public function down(): void
    {
        Schema::table('promotion_buy_x_get_y', function (Blueprint $table) {
            //
        });
    }
};
