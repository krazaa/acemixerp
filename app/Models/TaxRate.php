<?php

namespace App\Models;

use App\Enums\RecordStatus;
use App\Enums\TaxRateComponent;
use App\Enums\TaxRateType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TaxRate extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'code', 'name',
        'type', 'component', 'rate',
        'effective_from', 'effective_to',
        'is_default', 'is_compound', 'is_recoverable',
        'description', 'status',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => TaxRateType::class,
            'component' => TaxRateComponent::class,
            'status' => RecordStatus::class,
            'rate' => 'decimal:6',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_default' => 'boolean',
            'is_compound' => 'boolean',
            'is_recoverable' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('tax_rate');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', RecordStatus::Active->value);
    }

    public function scopeOutput(Builder $q): Builder
    {
        return $q->where('component', TaxRateComponent::Output->value);
    }

    public function scopeInput(Builder $q): Builder
    {
        return $q->where('component', TaxRateComponent::Input->value);
    }

    public function scopeEffectiveOn(Builder $q, \DateTimeInterface $date): Builder
    {
        $d = $date->format('Y-m-d');

        return $q->where('effective_from', '<=', $d)
            ->where(fn ($q) => $q
                ->whereNull('effective_to')
                ->orWhere('effective_to', '>=', $d));
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (! $term) {
            return $q;
        }

        return $q->where(fn ($q) => $q
            ->where('name', 'like', "%{$term}%")
            ->orWhere('code', 'like', "%{$term}%"));
    }

    public function isCurrentlyEffective(): bool
    {
        $today = now()->startOfDay();
        if ($this->effective_from->isAfter($today)) {
            return false;
        }
        if ($this->effective_to && $this->effective_to->isBefore($today)) {
            return false;
        }

        return true;
    }

    public static function defaultFor(TaxRateComponent $component): ?self
    {
        return static::query()
            ->where('component', $component->value)
            ->where('is_default', true)
            ->where('status', RecordStatus::Active->value)
            ->first();
    }
}
