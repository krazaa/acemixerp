<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            // Identity
            $table->string('code', 32)->unique();          // ITM-00001 (concurrency-safe)
            $table->string('sku', 64)->nullable()->unique();
            $table->string('barcode', 64)->nullable()->unique();
            $table->string('name', 192);
            $table->string('description')->nullable();

            // Classification
            $table->enum('item_type', ['stock', 'service', 'asset', 'consumable'])
                ->default('stock');

            $table->foreignId('category_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete();

            $table->foreignId('unit_id')
                ->nullable()
                ->constrained('units')
                ->nullOnDelete();

            // Tax
            $table->foreignId('tax_rate_id')->nullable();          // FK added Phase 2E
            $table->boolean('is_tax_exempt')->default(false);

            // Pricing (money: DECIMAL(19,4))
            $table->decimal('cost_price', 19, 4)->default(0);
            $table->decimal('selling_price', 19, 4)->default(0);
            $table->decimal('minimum_selling_price', 19, 4)->nullable();

            // Inventory policy (only meaningful for stock/consumable)
            $table->boolean('track_inventory')->default(true);
            $table->decimal('reorder_level', 19, 4)->default(0);
            $table->decimal('minimum_stock', 19, 4)->default(0);
            $table->decimal('maximum_stock', 19, 4)->nullable();
            $table->boolean('allow_negative_stock')->default(false);

            // Batch / Serial / Expiry (activated in Phase 6)
            $table->boolean('track_batch')->default(false);
            $table->boolean('track_serial')->default(false);
            $table->boolean('track_expiry')->default(false);

            // Default warehouse (FK added Phase 2D)
            $table->unsignedBigInteger('default_warehouse_id')->nullable();

            // Account mapping (FK added Phase 3)
            $table->unsignedBigInteger('inventory_account_id')->nullable();
            $table->unsignedBigInteger('sales_account_id')->nullable();
            $table->unsignedBigInteger('cogs_account_id')->nullable();
            $table->unsignedBigInteger('expense_account_id')->nullable();

            $table->string('image_path')->nullable();
            $table->boolean('is_sellable')->default(true);
            $table->boolean('is_purchasable')->default(true);

            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for search + filtering
            $table->index('status');
            $table->index('item_type');
            $table->index('category_id');
            $table->index('name');
            $table->index(['item_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
