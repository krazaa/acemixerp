<?php

namespace App\Models;

use App\Enums\RecordStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Department extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'code', 'name', 'description',
        'parent_id', 'manager_id', 'status',
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
            ->useLogName('department');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function designations(): HasMany
    {
        return $this->hasMany(Designation::class);
    }

    public function costCenters(): HasMany
    {
        return $this->hasMany(CostCenter::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', RecordStatus::Active->value);
    }

    /** All descendant IDs (recursive). Used for validation to prevent cycles. */
    public function descendantIds(): array
    {
        $ids = [];
        $stack = $this->children()->pluck('id')->all();
        while ($stack) {
            $id = array_pop($stack);
            if (in_array($id, $ids, true)) {
                continue;
            }
            $ids[] = $id;
            $stack = array_merge($stack, self::query()->where('parent_id', $id)->pluck('id')->all());
        }

        return $ids;
    }

    public function isReferenced(): bool
    {
        return $this->users()->exists()
            || $this->designations()->exists()
            || $this->costCenters()->exists()
            || $this->children()->exists();
    }
}
