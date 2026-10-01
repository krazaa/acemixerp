<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Illuminate\Database\Eloquent\Model;

class LeavePolicy extends Model
{
    protected $fillable = ['code', 'name', 'annual_entitlement_days', 'is_active'];

    protected function casts(): array
    {
        return ['annual_entitlement_days' => 'decimal:2', 'is_active' => 'boolean'];
    }
}
