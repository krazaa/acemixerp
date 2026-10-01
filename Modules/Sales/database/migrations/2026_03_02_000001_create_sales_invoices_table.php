<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_invoices', function (Blueprint $table) {
            $table->id();

            $table->string('number', 32)->unique();             // INV-2026-000001

            $table->foreignId('customer_id')
                ->constrained('customers')
                ->restrictOnDelete();

            // Optional source order
            $table->foreignId('sales_order_id')
                ->nullable()
                ->constrained('sales_orders')
                ->nullOnDelete();

            $table->date('invoice_date');
            $table->date('due_date');

            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained('cost_centers')->nullOnDelete();
            $table->foreignId('payment_term_id')->nullable()->constrained('payment_terms')->nullOnDelete();

            $table->char('currency_code', 3);
            $table->decimal('exchange_rate', 19, 8)->default(1);

            $table->string('reference', 64)->nullable();        // customer's PO #

            // Totals
            $table->decimal('subtotal', 19, 4)->default(0);
            $table->decimal('discount_total', 19, 4)->default(0);
            $table->decimal('tax_total', 19, 4)->default(0);
            $table->decimal('wht_tax_total', 19, 4)->default(0);
            $table->decimal('total', 19, 4)->default(0);
            $table->decimal('paid_amount', 19, 4)->default(0);

            $table->text('notes')->nullable();
            $table->text('terms')->nullable();

            $table->enum('status', [
                'draft', 'matched', 'mismatch', 'approved', 'posted',
                'partially_paid', 'paid', 'rejected', 'cancelled', 'reversed',
            ])->default('draft');

            // Three-way match results
            $table->enum('match_status', ['pending', 'matched', 'mismatch'])->default('pending');
            $table->text('match_notes')->nullable();
            $table->timestamp('matched_at')->nullable();
            $table->foreignId('matched_by')->nullable()->constrained('users')->nullOnDelete();

            // Workflow timestamps + actors
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();

            // GL link
            $table->foreignId('journal_entry_id')->nullable()
                ->constrained('journal_entries')->nullOnDelete();

            // Reversal
            $table->foreignId('reverses_id')->nullable()
                ->constrained('sales_invoices')->nullOnDelete();
            $table->foreignId('reversed_by_id')->nullable()
                ->constrained('sales_invoices')->nullOnDelete();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('invoice_date');
            $table->index('due_date');
            $table->index('customer_id');
            $table->index('sales_order_id');
            $table->index(['status', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_invoices');
    }
};
