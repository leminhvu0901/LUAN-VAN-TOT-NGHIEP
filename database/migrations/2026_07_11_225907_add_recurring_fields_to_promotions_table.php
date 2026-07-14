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
            $table->boolean('is_recurring')->default(0)->after('end_at');
            $table->json('recurring_days')->nullable()->after('is_recurring');
            $table->time('recurring_start_time')->nullable()->after('recurring_days');
            $table->time('recurring_end_time')->nullable()->after('recurring_start_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn([
                'is_recurring',
                'recurring_days',
                'recurring_start_time',
                'recurring_end_time'
            ]);
        });
    }
};
