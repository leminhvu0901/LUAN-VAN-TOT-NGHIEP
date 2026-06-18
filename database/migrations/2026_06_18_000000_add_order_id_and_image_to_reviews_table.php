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
        if (Schema::hasTable('reviews')) {
            Schema::table('reviews', function (Blueprint $table) {
                if (!Schema::hasColumn('reviews', 'order_id')) {
                    $table->unsignedBigInteger('order_id')->nullable()->after('product_id');
                }
                if (!Schema::hasColumn('reviews', 'image')) {
                    $table->string('image')->nullable()->after('comment');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('reviews')) {
            Schema::table('reviews', function (Blueprint $table) {
                if (Schema::hasColumn('reviews', 'order_id')) {
                    $table->dropColumn('order_id');
                }
                if (Schema::hasColumn('reviews', 'image')) {
                    $table->dropColumn('image');
                }
            });
        }
    }
};
