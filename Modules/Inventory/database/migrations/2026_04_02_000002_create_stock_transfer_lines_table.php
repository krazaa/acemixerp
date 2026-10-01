<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('stock_transfer_lines')) {
            return;
        }

        Schema::create('stock_transfer_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained('stock_transfers')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->unsignedBigInteger('batch_id')->nullable();

            $table->unsignedSmallInteger('position')->default(0);

            $table->decimal('quantity', 19, 4);
            $table->decimal('dispatched_quantity', 19, 4)->default(0);
            $table->decimal('received_quantity', 19, 4)->default(0);
            $table->decimal('unit_cost', 19, 4)->default(0);

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('stock_transfer_id');
            $table->index('item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_lines');
    }
};
