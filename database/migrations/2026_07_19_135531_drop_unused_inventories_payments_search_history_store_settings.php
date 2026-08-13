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
        // 4 bảng không còn được bất kỳ code nào đọc/ghi (đã khảo sát toàn bộ app/, resources/,
        // routes/, tests/) — đều là leftover từ thiết kế cũ đã bị thay thế:
        // - inventories: tồn kho generic cũ, đã thay bằng materials/material_imports.
        // - payments: đã thay bằng các cột payment_status/payment_method/paid_at ngay trong orders.
        // - search_history: không có code nào ghi log tìm kiếm.
        // - store_settings: đã thay bằng bảng settings key-value (Setting::getValue(...)).
        // Đã xác nhận không có bảng nào khác có khóa ngoại trỏ tới 4 bảng này.
        Schema::dropIfExists('inventories');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('search_history');
        Schema::dropIfExists('store_settings');
    }

    // Reverse the migrations.
    public function down(): void
    {
        // Chỉ khôi phục lại đúng cấu trúc bảng gốc — KHÔNG khôi phục được dữ liệu đã mất khi drop.
        $usersUsesBigInt = DB::getDriverName() !== 'mysql'
            || str_contains(strtolower((string) (DB::selectOne("SHOW COLUMNS FROM users WHERE Field = 'id'")->Type ?? '')), 'bigint');

        Schema::create('inventories', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->enum('item_type', ['product', 'topping']);
            $table->integer('item_id');
            $table->integer('quantity')->nullable()->default(0);
            $table->integer('low_stock_threshold')->nullable()->default(5);
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('payments', function (Blueprint $table) use ($usersUsesBigInt) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->integer('order_id');
            if ($usersUsesBigInt) {
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            } else {
                $table->integer('user_id')->nullable();
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            }
            $table->string('payment_method', 50)->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('transaction_code', 100)->nullable();
            $table->enum('status', ['pending', 'success', 'failed', 'refunded'])->nullable()->default('pending');
            $table->text('note')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->enum('provider', ['momo', 'vnpay', 'zalopay', 'cash'])->nullable();
            $table->text('raw_response')->nullable();
            $table->timestamp('paid_at')->nullable()->comment('Thời điểm thanh toán thành công');

            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
        });

        Schema::create('search_history', function (Blueprint $table) use ($usersUsesBigInt) {
            $table->engine = 'InnoDB';
            $table->id();
            if ($usersUsesBigInt) {
                $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            } else {
                $table->integer('user_id')->nullable();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            }
            $table->string('keyword')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('store_settings', function (Blueprint $table) {
            $table->id();
            $table->time('open_time');
            $table->time('close_time');
            $table->boolean('is_open')->nullable()->default(true);
            $table->timestamps();
        });
    }
};
