<?php

namespace App\Models;

use App\Enums\AccountingPeriodStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AccountingPeriod extends Model
{
    use LogsActivity;

    protected $fillable = [
        'financial_year_id', 'name', 'start_date', 'end_date',
        'status', 'closed_at', 'closed_by', 'close_notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => AccountingPeriodStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'closed_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('accounting_period');
    }

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class);
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function scopeOpen(Builder $q): Builder
    {
        return $q->where('status', AccountingPeriodStatus::Open->value);
    }

    public function containsDate(\DateTimeInterface $date): bool
    {
        $d = $date->format('Y-m-d');

        return $d >= $this->start_date->format('Y-m-d')
            && $d <= $this->end_date->format('Y-m-d');
    }

    public static function forDate(\DateTimeInterface $date): ?self
    {
        $d = $date->format('Y-m-d');

        return static::query()
            ->where('start_date', '<=', $d)
            ->where('end_date', '>=', $d)
            ->first();
    }
}
