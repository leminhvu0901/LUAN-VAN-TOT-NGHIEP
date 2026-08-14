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
            // Mã giao dịch HOÀN TIỀN của MoMo, khác payment_transaction_id, mã giao dịch THANH TOÁN gốc.
            if (!Schema::hasColumn('orders', 'refund_transaction_id')) {
                $table->string('refund_transaction_id', 100)->nullable()->unique();
            }
            if (!Schema::hasColumn('orders', 'refunded_at')) {
                $table->timestamp('refunded_at')->nullable();
            }
            // Phân loại lý do khi shipper báo giao thất bại, chỉ 'damaged' mới tự động hoàn tiền.
            if (!Schema::hasColumn('orders', 'delivery_failure_type')) {
                $table->string('delivery_failure_type', 20)->nullable();
            }
        });
    }

    // Reverse the migrations.
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'delivery_failure_type')) {
                $table->dropColumn('delivery_failure_type');
            }
            if (Schema::hasColumn('orders', 'refunded_at')) {
                $table->dropColumn('refunded_at');
            }
            if (Schema::hasColumn('orders', 'refund_transaction_id')) {
                $table->dropColumn('refund_transaction_id');
            }
        });
    }
};
