<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('personal_email')->nullable()->unique()->after('email');
            $table->string('personal_phone', 32)->nullable()->after('phone');
            $table->date('date_of_birth')->nullable()->after('joining_date');
            $table->date('hire_date')->nullable()->after('date_of_birth');
            $table->string('gender', 32)->nullable()->after('hire_date');
            $table->string('address_line1')->nullable()->after('gender');
            $table->string('postal_code', 32)->nullable()->after('address_line1');
            $table->string('city', 100)->nullable()->after('postal_code');
            $table->string('bank_name', 150)->nullable()->after('city');
            $table->text('bank_account_number')->nullable()->after('bank_name');
            $table->string('bank_branch', 150)->nullable()->after('bank_account_number');
            $table->string('tax_number', 100)->nullable()->after('bank_branch');
            $table->string('emergency_contact_name')->nullable()->after('tax_number');
            $table->string('emergency_contact_phone', 32)->nullable()->after('emergency_contact_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique(['personal_email']);
            $table->dropColumn([
                'personal_email',
                'personal_phone',
                'date_of_birth',
                'hire_date',
                'gender',
                'address_line1',
                'postal_code',
                'city',
                'bank_name',
                'bank_account_number',
                'bank_branch',
                'tax_number',
                'emergency_contact_name',
                'emergency_contact_phone',
            ]);
        });
    }
};
