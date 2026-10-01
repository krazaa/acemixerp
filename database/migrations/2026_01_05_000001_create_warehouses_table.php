<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name', 128)->unique();
            $table->string('description')->nullable();

            $table->enum('type', ['main', 'distribution', 'retail', 'transit', 'quarantine'])
                ->default('main');

            // Contact
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();

            // Management
            $table->foreignId('manager_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('department_id')
                ->nullable()
                ->constrained('departments')
                ->nullOnDelete();

            $table->foreignId('cost_center_id')
                ->nullable()
                ->constrained('cost_centers')
                ->nullOnDelete();

            // Default flag — exactly one row may be true (enforced in service)
            $table->boolean('is_default')->default(false);

            // Account mapping (FK added in Phase 3)
            $table->unsignedBigInteger('inventory_account_id')->nullable();

            // Operational constraints
            $table->boolean('allow_negative_stock')->default(false);
            $table->boolean('is_pickable')->default(true);   // false for quarantine, transit

            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('type');
            $table->index('is_default');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
