<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalaryStructure extends Model
{
    protected $fillable = ['code', 'name', 'basic_salary', 'allowances', 'deductions', 'is_active'];

    protected function casts(): array
    {
        return ['basic_salary' => 'decimal:4', 'allowances' => 'decimal:4', 'deductions' => 'decimal:4', 'is_active' => 'boolean'];
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
