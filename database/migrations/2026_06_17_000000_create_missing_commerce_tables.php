<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'phone')) $table->string('phone', 20)->nullable()->unique();
            if (!Schema::hasColumn('users', 'address')) $table->text('address')->nullable();
            if (!Schema::hasColumn('users', 'points')) $table->unsignedInteger('points')->default(0);
            if (!Schema::hasColumn('users', 'membership_level')) $table->enum('membership_level', ['new', 'silver', 'gold', 'diamond'])->default('new');
            if (!Schema::hasColumn('users', 'role')) $table->enum('role', ['customer', 'admin', 'staff'])->default('customer');
            if (!Schema::hasColumn('users', 'is_active')) $table->boolean('is_active')->default(true);
            if (!Schema::hasColumn('users', 'oauth_provider')) $table->string('oauth_provider', 50)->nullable();
            if (!Schema::hasColumn('users', 'oauth_id')) $table->string('oauth_id')->nullable();
            if (!Schema::hasColumn('users', 'google_id')) $table->string('google_id')->nullable();
        });

        if (!Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('display_order')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('banners')) {
            Schema::create('banners', function (Blueprint $table) {
                $table->id();
                $table->string('title')->nullable();
                $table->string('image');
                $table->string('link_url')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('display_order')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->id();
                $table->string('sku', 50)->nullable()->unique();
                $table->string('slug')->unique();
                $table->string('name');
                $table->decimal('base_price', 12, 2);
                $table->string('image')->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('product_sizes')) {
            Schema::create('product_sizes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->string('size_name', 50);
                $table->decimal('price_adjustment', 12, 2)->default(0);
                $table->unique(['product_id', 'size_name']);
            });
        }

        if (!Schema::hasTable('toppings')) {
            Schema::create('toppings', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->decimal('price', 12, 2)->default(0);
                $table->boolean('is_available')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('product_toppings')) {
            Schema::create('product_toppings', function (Blueprint $table) {
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('topping_id')->constrained()->cascadeOnDelete();
                $table->primary(['product_id', 'topping_id']);
            });
        }

        if (!Schema::hasTable('promotions')) {
            Schema::create('promotions', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->nullable()->unique();
                $table->enum('type', ['percent', 'fixed']);
                $table->decimal('value', 12, 2)->default(0);
                $table->decimal('min_order_amount', 12, 2)->nullable();
                $table->dateTime('start_at')->nullable();
                $table->dateTime('end_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->string('description')->nullable();
                $table->unsignedInteger('usage_limit')->nullable();
                $table->unsignedInteger('used_count')->default(0);
                $table->decimal('max_discount_amount', 12, 2)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->id();
                $table->string('order_code', 50)->unique();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('customer_name', 100)->nullable();
                $table->string('customer_phone', 20)->nullable();
                $table->text('delivery_address')->nullable();
                $table->decimal('total_amount', 12, 2)->default(0);
                $table->decimal('discount_amount', 12, 2)->default(0);
                $table->decimal('final_amount', 12, 2)->default(0);
                $table->enum('payment_status', ['unpaid', 'partially_paid', 'paid', 'refunded'])->default('unpaid');
                $table->string('payment_method', 20)->default('cod');
                $table->enum('status', ['pending', 'confirmed', 'shipping', 'completed', 'cancelled'])->default('pending');
                $table->string('coupon_code', 50)->nullable();
                $table->foreignId('promotion_id')->nullable()->constrained()->nullOnDelete();
                $table->enum('delivery_type', ['pickup', 'delivery'])->default('delivery');
                $table->dateTime('estimated_time')->nullable();
                $table->decimal('distance_km', 6, 2)->nullable();
                $table->decimal('weather_fee', 12, 2)->default(0);
                $table->decimal('peak_hour_fee', 12, 2)->default(0);
                $table->decimal('shipping_fee', 12, 2)->default(0);
                $table->text('customer_note')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('order_items')) {
            Schema::create('order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->restrictOnDelete();
                $table->string('size_name', 50)->nullable();
                $table->unsignedInteger('quantity');
                $table->decimal('unit_price', 12, 2);
                $table->string('sugar_level', 20)->nullable();
                $table->string('ice_level', 20)->nullable();
                $table->json('options')->nullable();
                $table->text('note')->nullable();
            });
        }

        if (!Schema::hasTable('carts')) {
            Schema::create('carts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
                $table->string('session_id', 100)->nullable();
                $table->timestamps();
                $table->unique('user_id');
                $table->unique('session_id');
            });
        }

        if (!Schema::hasTable('cart_items')) {
            Schema::create('cart_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->string('size_name', 50)->nullable();
                $table->unsignedInteger('quantity')->default(1);
                $table->string('sugar_level', 20)->nullable();
                $table->string('ice_level', 20)->nullable();
                $table->decimal('unit_price', 12, 2)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('cart_item_toppings')) {
            Schema::create('cart_item_toppings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cart_item_id')->constrained()->cascadeOnDelete();
                $table->foreignId('topping_id')->constrained()->cascadeOnDelete();
                $table->decimal('price', 12, 2)->default(0);
                $table->unique(['cart_item_id', 'topping_id']);
            });
        }

        if (!Schema::hasTable('favorites')) {
            Schema::create('favorites', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['user_id', 'product_id']);
            });
        }

        if (!Schema::hasTable('reviews')) {
            Schema::create('reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->restrictOnDelete();
                $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
                $table->unsignedTinyInteger('rating');
                $table->text('comment')->nullable();
                $table->string('image')->nullable();
                $table->boolean('is_visible')->default(true);
                $table->timestamps();
                $table->unique(['user_id', 'order_id', 'product_id']);
            });
        }
    }

    public function down(): void
    {
        // Fill-only migration: never drop a legacy production table.
    }
};
