<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use App\Models\Department;
use App\Models\Designation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $fillable = [
        'number',
        'first_name',
        'last_name',
        'email',
        'personal_email',
        'phone',
        'personal_phone',
        'department_id',
        'designation_id',
        'salary_structure_id',
        'leave_policy_id',
        'joining_date',
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
        'attendance_enabled',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $hidden = ['bank_account_number'];

    protected function casts(): array
    {
        return [
            'joining_date' => 'date',
            'date_of_birth' => 'date',
            'hire_date' => 'date',
            'bank_account_number' => 'encrypted',
            'attendance_enabled' => 'boolean',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function salaryStructure(): BelongsTo
    {
        return $this->belongsTo(SalaryStructure::class);
    }

    public function leavePolicy(): BelongsTo
    {
        return $this->belongsTo(LeavePolicy::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(EmployeeContract::class);
    }

    public function currentContract(): HasMany
    {
        return $this->hasMany(EmployeeContract::class)->where('is_current', true);
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }
}
