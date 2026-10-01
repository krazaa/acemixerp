<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();

            $table->string('number', 32)->unique();             // QTN-2026-000001

            $table->foreignId('customer_id')
                ->constrained('customers')
                ->restrictOnDelete();

            $table->date('quotation_date');
            $table->date('valid_until')->nullable();

            $table->foreignId('salesperson_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('payment_term_id')->nullable()->constrained('payment_terms')->nullOnDelete();

            $table->char('currency_code', 3);
            $table->decimal('exchange_rate', 19, 8)->default(1);

            $table->string('reference', 64)->nullable();

            // Totals
            $table->decimal('subtotal', 19, 4)->default(0);
            $table->decimal('discount_total', 19, 4)->default(0);
            $table->decimal('tax_total', 19, 4)->default(0);
            $table->decimal('wht_tax_total', 19, 4)->default(0);
            $table->decimal('total', 19, 4)->default(0);

            $table->text('notes')->nullable();
            $table->text('terms')->nullable();

            $table->enum('status', [
                'draft', 'sent', 'accepted', 'rejected',
                'expired', 'converted', 'cancelled',
            ])->default('draft');

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('accepted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('converted_to_type', 128)->nullable();
            $table->unsignedBigInteger('converted_to_id')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('quotation_date');
            $table->index('customer_id');
            $table->index(['converted_to_type', 'converted_to_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
