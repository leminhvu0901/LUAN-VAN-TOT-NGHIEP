<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Run the migrations.
    public function up(): void
    {
        // users.id trong DB này là `int` thường, không phải bigint, foreignId() mặc định tạo
        // unsignedBigInteger, gây lỗi "incompatible" khi ràng buộc khóa ngoại. Cùng pattern đã dùng
        // cho created_by/delivery_staff_id ở các migration trước.
        $usersUsesBigInt = DB::getDriverName() !== 'mysql'
            || str_contains(strtolower((string) (DB::selectOne("SHOW COLUMNS FROM users WHERE Field = 'id'")->Type ?? '')), 'bigint');

        Schema::create('staff_shifts', function (Blueprint $table) use ($usersUsesBigInt) {
            // Server MySQL này có default_storage_engine=MyISAM không phải InnoDB mặc định của
            // Laravel, MyISAM không hỗ trợ khóa ngoại thật, âm thầm bỏ qua ràng buộc, khiến bảng
            // orders không thể tạo FK trỏ tới bảng này. Ép InnoDB tường minh, khớp với mọi bảng khác
            // trong dự án, orders, users đều là InnoDB.
            $table->engine = 'InnoDB';
            $table->id();
            if ($usersUsesBigInt) {
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            } else {
                $table->integer('user_id');
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            }
            $table->timestamp('opened_at');
            $table->decimal('opening_cash', 12, 2)->default(0);
            $table->string('opening_note', 500)->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->decimal('closing_cash', 12, 2)->nullable();
            $table->decimal('expected_cash', 12, 2)->nullable();
            $table->decimal('cash_diff', 12, 2)->nullable();
            $table->string('closing_note', 500)->nullable();
            $table->timestamps();
        });
    }

    // Reverse the migrations.
    public function down(): void
    {
        Schema::dropIfExists('staff_shifts');
    }
};
