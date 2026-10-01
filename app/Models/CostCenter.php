<?php

namespace App\Models;

use App\Enums\RecordStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CostCenter extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'code', 'name', 'description',
        'department_id', 'default_expense_account_id', 'status',
    ];

    protected function casts(): array
    {
        return ['status' => RecordStatus::class];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('cost_center');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', RecordStatus::Active->value);
    }
}
