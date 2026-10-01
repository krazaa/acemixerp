<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_invoices', function (Blueprint $table) {
            $table->id();

            $table->string('number', 32)->unique();                // SI-2026-000001

            $table->foreignId('vendor_id')
                ->constrained('vendors')
                ->restrictOnDelete();

            $table->foreignId('purchase_order_id')
                ->constrained('purchase_orders')
                ->restrictOnDelete();

            // Vendor's own invoice reference
            $table->string('vendor_invoice_number', 64);
            $table->date('invoice_date');
            $table->date('due_date');

            $table->char('currency_code', 3);

            $table->decimal('subtotal', 19, 4)->default(0);
            $table->decimal('tax_total', 19, 4)->default(0);
            $table->decimal('total', 19, 4)->default(0);

            // Three-way-match summary
            $table->enum('match_status', ['pending', 'matched', 'mismatch'])
                ->default('pending');
            $table->text('match_notes')->nullable();
            $table->timestamp('matched_at')->nullable();
            $table->foreignId('matched_by')->nullable()->constrained('users')->nullOnDelete();

            // Lifecycle
            $table->enum('status', [
                'draft', 'matched', 'mismatch', 'approved',
                'posted', 'partially_paid', 'paid',
                'rejected', 'disputed', 'cancelled',
            ])->default('draft');

            // Posting audit
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();

            // The journal entry produced on posting
            $table->foreignId('journal_entry_id')->nullable()
                ->constrained('journal_entries')->nullOnDelete();

            // Payment tracking
            $table->decimal('paid_amount', 19, 4)->default(0);

            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('match_status');
            $table->index('invoice_date');
            $table->index('due_date');
            $table->index('vendor_id');
            $table->index('purchase_order_id');
            $table->unique(['vendor_id', 'vendor_invoice_number'], 'si_vendor_ref_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_invoices');
    }
};
