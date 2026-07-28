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
        Schema::table('reviews', function (Blueprint $table) {
            // NULL = chưa từng sửa. Khách chỉ được sửa đánh giá của mình ĐÚNG 1 LẦN (xem
            // ReviewController::update) — có giá trị ở đây là đã dùng hết lượt sửa, không cho sửa nữa
            // dù vẫn còn trong hạn 7 ngày.
            if (!Schema::hasColumn('reviews', 'edited_at')) {
                $table->timestamp('edited_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            if (Schema::hasColumn('reviews', 'edited_at')) {
                $table->dropColumn('edited_at');
            }
        });
    }
};
