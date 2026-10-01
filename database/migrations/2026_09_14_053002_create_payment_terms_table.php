<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_terms', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name', 128);

            $table->enum('type', ['due_on_receipt', 'net', 'end_of_month', 'day_of_month'])
                ->default('net');

            // Days is used by 'net' and 'end_of_month' (EOM offset).
            $table->unsignedSmallInteger('days')->default(0);

            // Day of month (1-31) used by 'day_of_month'.
            $table->unsignedTinyInteger('day_of_month')->nullable();

            // Discount for early payment (e.g. 2% within 10 days). Optional.
            $table->decimal('discount_percent', 9, 4)->nullable();
            $table->unsignedSmallInteger('discount_days')->nullable();

            $table->boolean('is_default')->default(false);
            $table->text('description')->nullable();

            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('type');
            $table->index('is_default');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_terms');
    }
};
