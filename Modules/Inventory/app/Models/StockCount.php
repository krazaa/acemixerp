<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Inventory\Enums\StockCountStatus;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class StockCount extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'stock_counts';

    protected $fillable = [
        'number',
        'warehouse_id',
        'count_date',
        'scope',
        'status',
        'started_at',
        'submitted_at',
        'approved_at',
        'posted_at',
        'started_by',
        'submitted_by',
        'approved_by',
        'posted_by',
        'stock_adjustment_id',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => StockCountStatus::class,
            'count_date' => 'date',
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'posted_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'count_date', 'warehouse_id', 'stock_adjustment_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('stock_count');
    }

    // ─── Relations ───────────────────────────────────────────────────

    public function lines(): HasMany
    {
        return $this->hasMany(StockCountLine::class)->orderBy('position')->orderBy('id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function stockAdjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class);
    }

    public function starter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
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
                ->orWhere('scope', 'like', "%{$term}%");
        });
    }

    public function scopeOpen(Builder $q): Builder
    {
        return $q->whereIn('status', [
            StockCountStatus::Draft->value,
            StockCountStatus::Counting->value,
            StockCountStatus::Review->value,
        ]);
    }

    public function scopeForWarehouse(Builder $q, int $warehouseId): Builder
    {
        return $q->where('warehouse_id', $warehouseId);
    }

    // ─── Helpers ─────────────────────────────────────────────────────

    /**
     * Total variance across all lines. Positive = overage, negative = shortfall.
     */
    public function totalVariance(): string
    {
        return (string) $this->lines->sum('variance');
    }

    /**
     * Number of lines whose counted quantity differs from the system quantity.
     */
    public function varianceLineCount(): int
    {
        return $this->lines
            ->filter(fn ($line) => bccomp((string) $line->variance, '0', 4) !== 0)
            ->count();
    }

    /**
     * Whether all lines have a counted quantity recorded.
     */
    public function isFullyCounted(): bool
    {
        return ! $this->lines->contains(fn ($line) => $line->counted_quantity === null);
    }

    /**
     * Percentage complete (counted lines / total lines).
     */
    public function progressPercent(): int
    {
        $total = $this->lines->count();
        if ($total === 0) {
            return 100;
        }

        $counted = $this->lines->whereNotNull('counted_quantity')->count();

        return (int) round(($counted / $total) * 100);
    }
}
