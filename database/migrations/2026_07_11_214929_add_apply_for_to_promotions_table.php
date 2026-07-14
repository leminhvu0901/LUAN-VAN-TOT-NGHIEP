<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->enum('apply_for', ['all', 'new', 'silver', 'gold', 'diamond'])
                  ->default('all')
                  ->after('value')
                  ->comment('Hạng thành viên áp dụng: all, new, silver, gold, diamond');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn('apply_for');
        });
    }
};
