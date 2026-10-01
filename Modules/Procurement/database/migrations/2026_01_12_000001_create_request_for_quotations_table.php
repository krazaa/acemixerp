<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_for_quotations', function (Blueprint $table) {
            $table->id();

            $table->string('number', 32)->unique();          // RFQ-2026-000001

            $table->date('issue_date');
            $table->date('due_date');                        // vendor deadline

            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained('cost_centers')->nullOnDelete();

            $table->char('currency_code', 3);

            $table->string('purpose', 500);
            $table->text('terms')->nullable();

            $table->enum('status', ['draft', 'issued', 'receiving', 'awarded', 'closed', 'cancelled'])
                ->default('draft');

            // Award tracking
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('awarded_at')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('awarded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('awarded_quotation_id')->nullable();  // FK added after vendor_quotations exists

            // Conversion tracking (into a PO in Phase 4C)
            $table->string('converted_to_type', 128)->nullable();
            $table->unsignedBigInteger('converted_to_id')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('issue_date');
            $table->index('due_date');
            $table->index(['converted_to_type', 'converted_to_id']);
        });

        // Link RFQ → source PRs (many-to-many since one RFQ can consolidate multiple PRs)
        Schema::create('purchase_requisition_rfq', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_for_quotation_id')
                ->constrained('request_for_quotations')
                ->cascadeOnDelete();
            $table->foreignId('purchase_requisition_id')
                ->constrained('purchase_requisitions')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(
                ['request_for_quotation_id', 'purchase_requisition_id'],
                'pr_rfq_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_requisition_rfq');
        Schema::dropIfExists('request_for_quotations');
    }
};
