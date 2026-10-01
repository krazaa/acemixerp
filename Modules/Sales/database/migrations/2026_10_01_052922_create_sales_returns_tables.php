<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_returns', function (Blueprint $table): void {
            $table->id();
            $table->string('number', 40)->unique();
            $table->foreignId('sales_invoice_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->date('return_date');
            $table->string('status', 24)->default('requested')->index();
            $table->text('reason');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            foreach (['approved', 'received', 'inspected', 'accepted', 'rejected'] as $step) {
                $table->foreignId($step.'_by')->nullable()->constrained('users')->restrictOnDelete();
                $table->timestamp($step.'_at')->nullable();
            }
            $table->foreignId('inventory_journal_id')->nullable()->constrained('journal_entries')->restrictOnDelete();
            $table->timestamps();
        });
        Schema::create('sales_return_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sales_return_id')->constrained()->restrictOnDelete();
            $table->foreignId('sales_invoice_line_id')->constrained()->restrictOnDelete();
            foreach (['requested_quantity', 'received_quantity', 'accepted_quantity', 'subtotal', 'tax', 'wht', 'total', 'unit_cost', 'cost'] as $field) {
                $table->decimal($field, 19, 4)->default(0);
            }
            $table->text('inspection_notes')->nullable();
            $table->timestamps();
            $table->unique(['sales_return_id', 'sales_invoice_line_id'], 'sales_return_invoice_line_unique');
        });
        Schema::create('sales_credit_notes', function (Blueprint $table): void {
            $table->id();
            $table->string('number', 40)->unique();
            $table->foreignId('sales_return_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('sales_invoice_id')->constrained()->restrictOnDelete();
            $table->date('credit_date');
            $table->string('status', 16)->default('draft')->index();
            $table->char('currency_code', 3);
            foreach (['subtotal', 'tax', 'wht', 'total'] as $field) {
                $table->decimal($field, 19, 4)->default(0);
            }
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained()->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_credit_notes');
        Schema::dropIfExists('sales_return_lines');
        Schema::dropIfExists('sales_returns');
    }
};
