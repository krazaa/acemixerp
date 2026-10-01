<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use App\Models\JournalEntry;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Inventory\Enums\AdjustmentStatus;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class StockAdjustment extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'stock_adjustments';

    protected $fillable = [
        'number',
        'warehouse_id',
        'adjustment_date',
        'reason',
        'notes',
        'status',
        'submitted_at',
        'approved_at',
        'posted_at',
        'submitted_by',
        'approved_by',
        'posted_by',
        'journal_entry_id',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => AdjustmentStatus::class,
            'adjustment_date' => 'date',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'posted_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'adjustment_date', 'warehouse_id', 'journal_entry_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('stock_adjustment');
    }

    // ─── Relations ───────────────────────────────────────────────────

    public function lines(): HasMany
    {
        return $this->hasMany(StockAdjustmentLine::class)->orderBy('position')->orderBy('id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ─── Scopes ──────────────────────────────────────────────────────

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (! $term) {
            return $q;
        }

        return $q->where(function ($q) use ($term) {
            $q->where('number', 'like', "%{$term}%")
                ->orWhere('reason', 'like', "%{$term}%");
        });
    }

    // ─── Helpers ─────────────────────────────────────────────────────

    /**
     * Net quantity added (positive) or removed (negative).
     */
    public function netQuantity(): string
    {
        return (string) $this->lines->sum('quantity');
    }

    /**
     * Total cost value of the adjustment.
     */
    public function totalValue(): string
    {
        return (string) $this->lines->sum(fn ($l) => bcmul((string) $l->quantity, (string) $l->unit_cost, 4));
    }

    public function hasIncreases(): bool
    {
        return $this->lines->contains(fn ($l) => bccomp((string) $l->quantity, '0', 4) > 0);
    }

    public function hasDecreases(): bool
    {
        return $this->lines->contains(fn ($l) => bccomp((string) $l->quantity, '0', 4) < 0);
    }
}
