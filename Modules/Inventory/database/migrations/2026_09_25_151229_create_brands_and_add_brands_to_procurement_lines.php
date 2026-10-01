<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const LINE_TABLES = ['purchase_requisition_lines', 'rfq_lines', 'purchase_order_lines', 'goods_receipt_lines', 'supplier_invoice_lines'];

    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 128)->unique();
            $table->string('description', 500)->nullable();
            $table->string('status', 16)->default('active')->index();
            $table->timestamps();
            $table->softDeletes();
        });
        foreach (self::LINE_TABLES as $name) {
            Schema::table($name, function (Blueprint $table): void {
                $table->foreignId('brand_id')->nullable()->constrained('brands')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse(self::LINE_TABLES) as $name) {
            Schema::table($name, function (Blueprint $table): void {
                $table->dropConstrainedForeignId('brand_id');
            });
        }
        Schema::dropIfExists('brands');
    }
};
