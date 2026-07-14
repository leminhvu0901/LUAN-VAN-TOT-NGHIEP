<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->repairInventoryTables();
        $this->addCommerceColumns();
        $this->createInventoryAuditTables();
        $this->addDatabaseConstraints();
    }

    private function repairInventoryTables(): void
    {
        if (Schema::hasTable('material_imports') && Schema::hasTable('materials')) {
            DB::table('material_imports')->whereNotIn('material_id', DB::table('materials')->select('id'))->delete();
        }
        if (Schema::hasTable('product_materials')) {
            DB::table('product_materials')->whereNotIn('product_id', DB::table('products')->select('id'))->delete();
            DB::table('product_materials')->whereNotIn('material_id', DB::table('materials')->select('id'))->delete();
        }
        if (Schema::hasTable('product_images')) {
            DB::table('product_images')->whereNotIn('product_id', DB::table('products')->select('id'))->delete();
        }
        if (Schema::hasTable('user_addresses') && Schema::hasTable('users')) {
            DB::table('user_addresses')->whereNotIn('user_id', DB::table('users')->select('id'))->delete();
        }

        if (DB::getDriverName() === 'mysql') {
            foreach (['materials', 'material_imports', 'product_materials', 'user_addresses'] as $table) {
                if (Schema::hasTable($table)) DB::statement("ALTER TABLE `{$table}` ENGINE=InnoDB");
            }
            if (Schema::hasTable('product_materials')) DB::statement('ALTER TABLE product_materials MODIFY product_id INT NOT NULL');
            if (Schema::hasTable('user_addresses')) DB::statement('ALTER TABLE user_addresses MODIFY user_id INT NOT NULL');
        }

        if (Schema::hasTable('materials') && Schema::hasTable('material_imports')) {
            $stocks = DB::table('material_imports')
                ->selectRaw('material_id, COALESCE(SUM(CASE WHEN quantity > 0 THEN remaining_quantity ELSE 0 END), 0) AS stock')
                ->groupBy('material_id')->get();
            foreach ($stocks as $stock) {
                DB::table('materials')->where('id', $stock->material_id)->update(['current_stock' => max((float) $stock->stock, 0)]);
            }
            DB::table('materials')->whereNotIn('id', $stocks->pluck('material_id'))->update(['current_stock' => 0]);
        }
    }

    private function addCommerceColumns(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'idempotency_key')) $table->string('idempotency_key', 64)->nullable()->unique();
            if (!Schema::hasColumn('orders', 'payment_transaction_id')) $table->string('payment_transaction_id', 100)->nullable()->unique();
            if (!Schema::hasColumn('orders', 'paid_at')) $table->timestamp('paid_at')->nullable();
            if (!Schema::hasColumn('orders', 'inventory_reserved_at')) $table->timestamp('inventory_reserved_at')->nullable();
            if (!Schema::hasColumn('orders', 'inventory_released_at')) $table->timestamp('inventory_released_at')->nullable();
            if (!Schema::hasColumn('orders', 'loyalty_points_awarded')) $table->unsignedInteger('loyalty_points_awarded')->default(0);
            if (!Schema::hasColumn('orders', 'deleted_at')) $table->softDeletes();
        });

        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'product_name')) $table->string('product_name')->nullable();
            if (!Schema::hasColumn('order_items', 'product_sku')) $table->string('product_sku', 50)->nullable();
            if (!Schema::hasColumn('order_items', 'product_image')) $table->string('product_image')->nullable();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::table('order_items')->join('products', 'products.id', '=', 'order_items.product_id')->update([
                'order_items.product_name' => DB::raw('products.name'),
                'order_items.product_sku' => DB::raw('products.sku'),
                'order_items.product_image' => DB::raw('products.image'),
            ]);
        }
    }

    private function createInventoryAuditTables(): void
    {
        $orderUsesBigInt = DB::getDriverName() !== 'mysql'
            || str_contains(strtolower((string) (DB::selectOne("SHOW COLUMNS FROM orders WHERE Field = 'id'")->Type ?? '')), 'bigint');

        if (!Schema::hasTable('order_material_consumptions')) {
            Schema::create('order_material_consumptions', function (Blueprint $table) use ($orderUsesBigInt) {
                $table->id();
                if ($orderUsesBigInt) $table->foreignId('order_id')->constrained()->cascadeOnDelete();
                else {
                    $table->integer('order_id');
                    $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
                }
                $table->foreignId('material_id')->constrained()->restrictOnDelete();
                $table->foreignId('material_import_id')->nullable()->constrained('material_imports')->nullOnDelete();
                $table->decimal('quantity', 12, 3);
                $table->decimal('unit_cost', 12, 2)->default(0);
                $table->timestamp('restored_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('inventory_movements')) {
            Schema::create('inventory_movements', function (Blueprint $table) use ($orderUsesBigInt) {
                $table->id();
                $table->foreignId('material_id')->constrained()->restrictOnDelete();
                $table->foreignId('material_import_id')->nullable()->constrained('material_imports')->nullOnDelete();
                if ($orderUsesBigInt) $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
                else {
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
    }

    private function addDatabaseConstraints(): void
    {
        if (DB::getDriverName() !== 'mysql') return;

        $duplicateSkus = DB::table('products')->select('sku')->whereNotNull('sku')->groupBy('sku')->havingRaw('COUNT(*) > 1')->pluck('sku');
        foreach ($duplicateSkus as $sku) {
            $ids = DB::table('products')->where('sku', $sku)->orderBy('id')->pluck('id');
            foreach ($ids->slice(1) as $id) DB::table('products')->where('id', $id)->update(['sku' => $sku . '-' . $id]);
        }

        if (DB::select("SHOW INDEX FROM products WHERE Key_name = 'products_sku_unique'") === []) {
            DB::statement('ALTER TABLE products ADD UNIQUE INDEX products_sku_unique (sku)');
        }

        $this->addMysqlForeignKey('material_imports', 'material_imports_material_fk', 'material_id', 'materials', 'id', 'CASCADE');
        $this->addMysqlForeignKey('product_materials', 'product_materials_product_fk', 'product_id', 'products', 'id', 'CASCADE');
        $this->addMysqlForeignKey('product_materials', 'product_materials_material_fk', 'material_id', 'materials', 'id', 'RESTRICT');
        $this->addMysqlForeignKey('user_addresses', 'user_addresses_user_fk', 'user_id', 'users', 'id', 'CASCADE');
    }

    private function addMysqlForeignKey(string $table, string $name, string $column, string $target, string $targetColumn, string $delete): void
    {
        $exists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $name)->exists();
        if (!$exists) {
            DB::statement("ALTER TABLE `{$table}` ADD CONSTRAINT `{$name}` FOREIGN KEY (`{$column}`) REFERENCES `{$target}` (`{$targetColumn}`) ON DELETE {$delete}");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('order_material_consumptions');
    }
};
