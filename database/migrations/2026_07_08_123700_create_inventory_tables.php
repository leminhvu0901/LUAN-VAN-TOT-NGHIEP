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
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('unit')->comment('e.g., cái, ly, kg, gram, lít');
            $table->decimal('unit_price', 12, 2)->default(0)->comment('Average cost per unit');
            $table->decimal('current_stock', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('material_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('materials')->onDelete('cascade');
            $table->decimal('quantity', 10, 2);
            $table->decimal('total_price', 12, 2);
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('product_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('material_id')->constrained('materials')->onDelete('cascade');
            $table->decimal('quantity_used', 10, 2)->comment('Amount of material used for 1 product');
            $table->timestamps();
            
            $table->unique(['product_id', 'material_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_materials');
        Schema::dropIfExists('material_imports');
        Schema::dropIfExists('materials');
    }
};
