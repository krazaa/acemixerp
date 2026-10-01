<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_number', 32)->unique();
            $table->string('name', 192);
            $table->text('description')->nullable();
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->unsignedBigInteger('supplier_invoice_id')->nullable();
            $table->date('acquisition_date');
            $table->date('in_service_date')->nullable();
            $table->date('capitalized_at')->nullable();
            $table->decimal('cost', 19, 4);
            $table->decimal('salvage_value', 19, 4)->default(0);
            $table->unsignedInteger('useful_life_months');
            $table->enum('depreciation_method', ['straight_line'])->default('straight_line');
            $table->decimal('accumulated_depreciation', 19, 4)->default(0);
            $table->decimal('carrying_amount', 19, 4);
            $table->enum('status', ['draft', 'capitalized', 'disposed'])->default('draft');
            $table->string('location', 192)->nullable();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('asset_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('accumulated_depreciation_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('depreciation_expense_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('capitalization_journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'in_service_date']);
            $table->index('vendor_id');
        });

        DB::table('document_sequences')->insertOrIgnore([
            'key' => 'fixed_asset', 'prefix' => 'FA', 'pattern' => '{prefix}-{year}-{number}',
            'padding' => 6, 'reset_yearly' => true, 'current_value' => 0,
            'last_reset_year' => null, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fixed_assets');
    }
};
