<?php

namespace App\Models;

use App\Enums\FinancialYearStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialYear extends Model
{
    protected $fillable = ['name', 'start_date', 'end_date', 'status', 'is_current', 'closed_at', 'closed_by'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_current' => 'boolean',
            'closed_at' => 'datetime',
            'status' => FinancialYearStatus::class,   // ← add this line
        ];
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function containsDate(\DateTimeInterface $date): bool
    {
        $d = $date->format('Y-m-d');

        return $d >= $this->start_date->format('Y-m-d')
            && $d <= $this->end_date->format('Y-m-d');
    }

    public function periods(): HasMany
    {
        return $this->hasMany(AccountingPeriod::class);
    }
}
