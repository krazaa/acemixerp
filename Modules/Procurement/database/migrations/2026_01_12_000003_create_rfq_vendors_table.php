<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfq_vendors', function (Blueprint $table) {
            $table->id();

            $table->foreignId('request_for_quotation_id')
                ->constrained('request_for_quotations')
                ->cascadeOnDelete();

            $table->foreignId('vendor_id')
                ->constrained('vendors')
                ->restrictOnDelete();

            $table->enum('status', ['invited', 'submitted', 'rejected', 'awarded', 'declined'])
                ->default('invited');

            $table->timestamp('invited_at')->nullable();
            $table->timestamp('submitted_at')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['request_for_quotation_id', 'vendor_id'], 'rfq_vendor_unique');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfq_vendors');
    }
};
