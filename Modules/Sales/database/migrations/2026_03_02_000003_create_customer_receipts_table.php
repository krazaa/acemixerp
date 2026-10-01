<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_receipts', function (Blueprint $table) {
            $table->id();

            $table->string('number', 32)->unique();             // RCT-2026-000001

            $table->foreignId('customer_id')
                ->constrained('customers')
                ->restrictOnDelete();

            $table->date('receipt_date');
            $table->char('currency_code', 3);
            $table->decimal('exchange_rate', 19, 8)->default(1);

            $table->decimal('amount', 19, 4);
            $table->decimal('allocated_amount', 19, 4)->default(0);

            $table->enum('payment_method', [
                'cash', 'bank_transfer', 'cheque', 'credit_card', 'online_gateway', 'other',
            ])->default('bank_transfer');

            $table->foreignId('bank_account_id')->nullable()
                ->constrained('bank_accounts')->nullOnDelete();

            $table->string('reference', 128)->nullable();
            $table->text('notes')->nullable();

            $table->enum('status', [
                'draft', 'submitted', 'approved', 'posted',
                'rejected', 'cancelled', 'reversed',
            ])->default('draft');

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->foreignId('journal_entry_id')->nullable()
                ->constrained('journal_entries')->nullOnDelete();

            $table->foreignId('reverses_id')->nullable()
                ->constrained('customer_receipts')->nullOnDelete();
            $table->foreignId('reversed_by_id')->nullable()
                ->constrained('customer_receipts')->nullOnDelete();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('receipt_date');
            $table->index('customer_id');
            $table->index('payment_method');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_receipts');
    }
};
