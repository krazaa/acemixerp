<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_payments', function (Blueprint $table) {
            $table->id();

            $table->string('number', 32)->unique();                // PAY-2026-000001

            $table->foreignId('vendor_id')
                ->constrained('vendors')
                ->restrictOnDelete();

            $table->date('payment_date');
            $table->char('currency_code', 3);
            $table->decimal('exchange_rate', 19, 8)->default(1);

            $table->decimal('amount', 19, 4);                       // total paid
            $table->decimal('allocated_amount', 19, 4)->default(0); // sum of allocations

            $table->enum('payment_method', [
                'cash', 'bank_transfer', 'cheque', 'credit_card', 'online_gateway', 'other',
            ])->default('bank_transfer');

            // Source of funds — either a bank account or the cash-on-hand GL account.
            $table->foreignId('bank_account_id')->nullable()
                ->constrained('bank_accounts')->nullOnDelete();

            // Reference: cheque number, transaction ID, etc.
            $table->string('reference', 128)->nullable();

            $table->text('notes')->nullable();

            $table->enum('status', [
                'draft', 'submitted', 'approved', 'posted',
                'rejected', 'cancelled', 'reversed',
            ])->default('draft');

            // Workflow audit
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();

            // GL link
            $table->foreignId('journal_entry_id')->nullable()
                ->constrained('journal_entries')->nullOnDelete();

            // Reversal link (self-referential)
            $table->foreignId('reverses_id')->nullable()
                ->constrained('vendor_payments')->nullOnDelete();
            $table->foreignId('reversed_by_id')->nullable()
                ->constrained('vendor_payments')->nullOnDelete();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('payment_date');
            $table->index('vendor_id');
            $table->index('payment_method');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_payments');
    }
};
