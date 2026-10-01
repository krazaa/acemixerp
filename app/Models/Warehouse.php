<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RecordStatus;
use App\Enums\WarehouseType;
use App\Models\Concerns\HasAddresses;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Warehouse extends Model
{
    use HasAddresses;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'code', 'name', 'description',
        'type',
        'email', 'phone',
        'manager_id', 'department_id', 'cost_center_id',
        'is_default',
        'inventory_account_id',
        'allow_negative_stock', 'is_pickable',
        'status',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => WarehouseType::class,
            'status' => RecordStatus::class,
            'is_default' => 'boolean',
            'allow_negative_stock' => 'boolean',
            'is_pickable' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('warehouse');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
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

    public function scopePickable(Builder $q): Builder
    {
        return $q->where('is_pickable', true)
            ->where('status', RecordStatus::Active->value);
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (! $term) {
            return $q;
        }

        return $q->where(fn ($q) => $q
            ->where('name', 'like', "%{$term}%")
            ->orWhere('code', 'like', "%{$term}%")
            ->orWhere('email', 'like', "%{$term}%"));
    }

    public static function default(): ?self
    {
        return static::query()->where('is_default', true)->first();
    }
}
