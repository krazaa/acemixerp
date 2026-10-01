<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfq_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('request_for_quotation_id')
                ->constrained('request_for_quotations')
                ->cascadeOnDelete();

            $table->unsignedSmallInteger('position')->default(0);

            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();

            $table->decimal('quantity', 19, 4);
            $table->string('specification', 500)->nullable();

            // Link back to source PR line (nullable — RFQ can be created standalone)
            $table->foreignId('purchase_requisition_line_id')
                ->nullable()
                ->constrained('purchase_requisition_lines')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('request_for_quotation_id');
            $table->index('item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfq_lines');
    }
};
