<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_quotation_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('vendor_quotation_id')
                ->constrained('vendor_quotations')
                ->cascadeOnDelete();

            $table->foreignId('rfq_line_id')
                ->constrained('rfq_lines')
                ->restrictOnDelete();

            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();

            $table->unsignedSmallInteger('position')->default(0);

            $table->decimal('quantity', 19, 4);
            $table->decimal('unit_price', 19, 4)->default(0);
            $table->decimal('line_total', 19, 4)->default(0);
            $table->decimal('tax_rate', 9, 6)->default(0);       // % — informational
            $table->decimal('tax_amount', 19, 4)->default(0);

            $table->unsignedSmallInteger('lead_time_days')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['vendor_quotation_id', 'rfq_line_id'], 'vql_quotation_line_unique');
            $table->index('rfq_line_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_quotation_lines');
    }
};
