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
        Schema::table('banners', function (Blueprint $table) {
            if (!Schema::hasColumn('banners', 'image_url')) {
                $table->string('image_url')->nullable()->after('title');
            }
            if (!Schema::hasColumn('banners', 'mobile_image_url')) {
                $table->string('mobile_image_url')->nullable()->after('image_url');
            }
            if (!Schema::hasColumn('banners', 'position')) {
                $table->string('position', 50)->default('home')->after('link_url');
            }
            if (!Schema::hasColumn('banners', 'start_at')) {
                $table->dateTime('start_at')->nullable()->after('is_active');
            }
            if (!Schema::hasColumn('banners', 'end_at')) {
                $table->dateTime('end_at')->nullable()->after('start_at');
            }
        });

        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'image_url')) {
                $table->string('image_url')->nullable()->after('name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            if (Schema::hasColumn('banners', 'image_url')) $table->dropColumn('image_url');
            if (Schema::hasColumn('banners', 'mobile_image_url')) $table->dropColumn('mobile_image_url');
            if (Schema::hasColumn('banners', 'position')) $table->dropColumn('position');
            if (Schema::hasColumn('banners', 'start_at')) $table->dropColumn('start_at');
            if (Schema::hasColumn('banners', 'end_at')) $table->dropColumn('end_at');
        });

        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'image_url')) $table->dropColumn('image_url');
        });
    }
};
