<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Migration bổ sung bảng inventory_movements nếu chưa tồn tại trên production.
// Nguyên nhân: migration harden_commerce_workflows (batch 16) đã fail lần đầu trên Railway
// nên chỉ tạo được order_material_consumptions, inventory_movements bị bỏ sót dù migration
// đã được đánh dấu là "Ran". Migration này tạo lại idempotently.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inventory_movements')) {
            return; // Bảng đã tồn tại (local/môi trường đã đầy đủ) — bỏ qua
        }

        $orderUsesBigInt = DB::getDriverName() !== 'mysql'
            || str_contains(
                strtolower((string) (DB::selectOne("SHOW COLUMNS FROM orders WHERE Field = 'id'")->Type ?? '')),
                'bigint'
            );

        Schema::create('inventory_movements', function (Blueprint $table) use ($orderUsesBigInt) {
            $table->id();
            $table->foreignId('material_id')->constrained()->restrictOnDelete();
            $table->foreignId('material_import_id')->nullable()->constrained('material_imports')->nullOnDelete();
            if ($orderUsesBigInt) {
                $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            } else {
                $table->integer('order_id')->nullable();
                $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
            }
            $table->enum('type', ['import', 'order_reserve', 'order_release', 'dispose', 'adjustment']);
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
