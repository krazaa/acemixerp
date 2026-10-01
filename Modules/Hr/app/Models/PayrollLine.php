<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollLine extends Model
{
    protected $fillable = ['payroll_run_id', 'employee_id', 'basic_salary', 'allowances', 'deductions', 'tax_deduction', 'gross_pay', 'net_pay'];

    protected function casts(): array
    {
        return ['basic_salary' => 'decimal:4', 'allowances' => 'decimal:4', 'deductions' => 'decimal:4', 'tax_deduction' => 'decimal:4', 'gross_pay' => 'decimal:4', 'net_pay' => 'decimal:4'];
    }

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
