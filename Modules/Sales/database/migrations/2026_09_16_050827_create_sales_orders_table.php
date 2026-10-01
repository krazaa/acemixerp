<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();

            $table->string('number', 32)->unique();             // SO-2026-000001

            $table->foreignId('customer_id')
                ->constrained('customers')
                ->restrictOnDelete();

            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();

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
                'draft', 'submitted', 'approved', 'on_hold', 'confirmed',
                'partially_delivered', 'delivered', 'closed',
                'rejected', 'cancelled',
            ])->default('draft');

            // Credit check audit
            $table->decimal('credit_limit_at_submission', 19, 4)->nullable();
            $table->decimal('outstanding_ar_at_submission', 19, 4)->nullable();
            $table->text('credit_hold_reason')->nullable();
            $table->timestamp('credit_hold_at')->nullable();
            $table->foreignId('credit_hold_released_by')->nullable()->constrained('users')->nullOnDelete();

            // Workflow timestamps
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();

            // Source document (Quotation)
            $table->string('source_type', 128)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('order_date');
            $table->index('customer_id');
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};
