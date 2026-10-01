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

class Category extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'code', 'name', 'description', 'parent_id', 'status',
        'inventory_account_id', 'sales_account_id', 'cogs_account_id',
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
            ->useLogName('category');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', RecordStatus::Active->value);
    }

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
        return $this->items()->exists() || $this->children()->exists();
    }
}
