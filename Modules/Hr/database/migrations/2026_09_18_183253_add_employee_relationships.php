<?php

declare(strict_types=1);

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
            $table->foreign('department_id')->references('id')->on('departments')->restrictOnDelete();
            $table->foreign('designation_id')->references('id')->on('designations')->restrictOnDelete();
            $table->foreign('salary_structure_id')->references('id')->on('salary_structures')->nullOnDelete();
            $table->foreign('leave_policy_id')->references('id')->on('leave_policies')->nullOnDelete();
        });
        Schema::table('employee_contracts', function (Blueprint $table) {
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_contracts', fn (Blueprint $table) => $table->dropForeign(['employee_id']));
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['department_id', 'designation_id', 'salary_structure_id', 'leave_policy_id']);
        });
    }
};
