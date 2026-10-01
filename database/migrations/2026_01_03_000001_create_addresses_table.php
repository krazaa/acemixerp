<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->morphs('addressable');

            $table->enum('type', ['billing', 'shipping', 'office', 'other'])->default('billing');
            $table->boolean('is_primary')->default(false);
            $table->string('label', 64)->nullable();

            $table->string('contact_name', 128)->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 32)->nullable();

            $table->string('address_line1');
            $table->string('address_line2')->nullable();
            $table->string('city', 100);
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 2)->default('US');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['addressable_type', 'addressable_id', 'type'], 'addr_owner_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
