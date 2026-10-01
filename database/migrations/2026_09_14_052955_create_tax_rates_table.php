<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name', 128);

            $table->enum('type', ['standard', 'reduced', 'zero', 'exempt'])
                ->default('standard');
            $table->enum('component', ['output', 'input'])->default('output');

            // Percentage. DECIMAL(9,6) → up to 999.999999%.
            $table->decimal('rate', 9, 6)->default(0);

            // Effective dating
            $table->date('effective_from');
            $table->date('effective_to')->nullable();

            // Flags
            $table->boolean('is_default')->default(false);
            $table->boolean('is_compound')->default(false);
            $table->boolean('is_recoverable')->default(true); // input tax recoverable

            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('type');
            $table->index('component');
            $table->index('is_default');
            $table->index(['effective_from', 'effective_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
    }
};
