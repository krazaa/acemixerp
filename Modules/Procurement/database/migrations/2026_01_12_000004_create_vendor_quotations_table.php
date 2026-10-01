<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_quotations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('request_for_quotation_id')
                ->constrained('request_for_quotations')
                ->cascadeOnDelete();

            $table->foreignId('vendor_id')
                ->constrained('vendors')
                ->restrictOnDelete();

            $table->string('reference', 64)->nullable();      // vendor's own quote number
            $table->date('quoted_at');
            $table->date('valid_until')->nullable();

            $table->char('currency_code', 3);

            $table->decimal('subtotal', 19, 4)->default(0);
            $table->decimal('tax_total', 19, 4)->default(0);
            $table->decimal('total', 19, 4)->default(0);

            $table->unsignedSmallInteger('lead_time_days')->nullable();

            $table->enum('status', ['draft', 'submitted', 'awarded', 'rejected'])
                ->default('draft');

            $table->text('notes')->nullable();

            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['request_for_quotation_id', 'vendor_id'], 'quotation_rfq_vendor_unique');
            $table->index('status');
        });

        // Now that vendor_quotations exists, add the FK to request_for_quotations.awarded_quotation_id
        Schema::table('request_for_quotations', function (Blueprint $table) {
            $table->foreign('awarded_quotation_id')
                ->references('id')
                ->on('vendor_quotations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('request_for_quotations', function (Blueprint $table) {
            $table->dropForeign(['awarded_quotation_id']);
        });
        Schema::dropIfExists('vendor_quotations');
    }
};
