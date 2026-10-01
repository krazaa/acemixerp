<?php

namespace App\Models;

use App\Enums\AccountType;
use App\Enums\NormalBalance;
use App\Enums\RecordStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Account extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'code', 'name', 'description',
        'type', 'normal_balance', 'parent_id',
        'is_postable', 'is_cash', 'is_bank',
        'requires_cost_center', 'requires_department', 'requires_party',
        'currency_code', 'status',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => AccountType::class,
            'normal_balance' => NormalBalance::class,
            'status' => RecordStatus::class,
            'is_postable' => 'boolean',
            'is_cash' => 'boolean',
            'is_bank' => 'boolean',
            'requires_cost_center' => 'boolean',
            'requires_department' => 'boolean',
            'requires_party' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('account');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function systemRole(): HasMany
    {
        return $this->hasMany(SystemAccount::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', RecordStatus::Active->value);
    }

    public function scopePostable(Builder $q): Builder
    {
        return $q->where('is_postable', true)
            ->where('status', RecordStatus::Active->value);
    }

    public function scopeOfType(Builder $q, AccountType $type): Builder
    {
        return $q->where('type', $type->value);
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

    /** True if any journal line references this account (Phase 3B). */
    public function hasPostings(): bool
    {
        if (! Schema::hasTable('journal_lines')) {
            return false;
        }

        return DB::table('journal_lines')->where('account_id', $this->id)->exists();
    }

    public function isLocked(): bool
    {
        return $this->hasPostings();
    }
}
