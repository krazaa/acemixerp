<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipt_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('goods_receipt_id')
                ->constrained('goods_receipts')
                ->cascadeOnDelete();

            $table->foreignId('purchase_order_line_id')
                ->constrained('purchase_order_lines')
                ->restrictOnDelete();

            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();

            $table->unsignedSmallInteger('position')->default(0);

            $table->decimal('received_quantity', 19, 4);
            $table->decimal('accepted_quantity', 19, 4);
            $table->decimal('rejected_quantity', 19, 4)->default(0);

            $table->string('rejection_reason', 500)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('goods_receipt_id');
            $table->index('purchase_order_line_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_lines');
    }
};
