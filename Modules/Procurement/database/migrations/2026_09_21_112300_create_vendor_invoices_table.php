<?php

declare(strict_types=1);

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
        Schema::create('vendor_invoices', function (Blueprint $table) {
            $table->id();

            $table->string('number', 32)->unique();                // SI-2026-000001

            $table->foreignId('vendor_id')
                ->constrained('vendors')
                ->restrictOnDelete();

            // Vendor's own invoice reference
            $table->string('vendor_invoice_number', 64);
            $table->date('invoice_date');
            $table->date('due_date');
            $table->char('billing_month', 7)->index();

            $table->decimal('subtotal', 19, 4)->default(0);
            $table->decimal('tax_total', 19, 4)->default(0);
            $table->decimal('whttax_total', 19, 4)->default(0);
            $table->decimal('total', 19, 4)->default(0);

            // Lifecycle
            $table->enum('status', [
                'draft', 'submitted', 'approved',
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
            $table->index('invoice_date');
            $table->index('due_date');
            $table->index('vendor_id');
            $table->unique(['vendor_id', 'vendor_invoice_number'], 'si_vendor_ref_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_invoices');
    }
};
