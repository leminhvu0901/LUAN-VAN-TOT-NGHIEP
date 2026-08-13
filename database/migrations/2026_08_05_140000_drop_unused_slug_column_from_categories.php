<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// categories.slug không được CategoryController đọc/ghi ở bất kỳ đâu (chỉ products.slug mới thực sự
// dùng cho route /products/{slug}) - cột này chỉ còn tồn tại trên các CSDL được tạo mới hoàn toàn từ
// migration 2026_06_17_000000 (vd. CSDL test SQLite), khiến CategoryController::store() lỗi NOT NULL
// vì không còn sinh slug nữa (xem commit 1c8be19: cột này đã KHÔNG tồn tại trên CSDL MySQL thật). Xóa
// hẳn cột để CSDL mới tạo khớp với thực tế CSDL đang chạy, tránh lệch schema.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'slug')) {
                // SQLite (CSDL test) không cho xóa cột đang có unique index đè lên trực tiếp - phải
                // xóa index trước rồi mới xóa cột được.
                $table->dropUnique('categories_slug_unique');
                $table->dropColumn('slug');
            }
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'slug')) {
                $table->string('slug')->nullable()->after('name');
            }
        });
    }
};
