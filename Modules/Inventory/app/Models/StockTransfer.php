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
use Modules\Inventory\Enums\TransferStatus;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class StockTransfer extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'stock_transfers';

    protected $fillable = [
        'number',
        'from_warehouse_id',
        'to_warehouse_id',
        'transfer_date',
        'expected_arrival_date',
        'notes',
        'status',
        'submitted_at',
        'approved_at',
        'dispatched_at',
        'received_at',
        'submitted_by',
        'approved_by',
        'dispatched_by',
        'received_by',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => TransferStatus::class,
            'transfer_date' => 'date',
            'expected_arrival_date' => 'date',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'dispatched_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'from_warehouse_id', 'to_warehouse_id', 'transfer_date'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('stock_transfer');
    }

    // ─── Relations ───────────────────────────────────────────────────

    public function lines(): HasMany
    {
        return $this->hasMany(StockTransferLine::class)->orderBy('position')->orderBy('id');
    }

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function dispatcher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatched_by');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
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

        return $q->where('number', 'like', "%{$term}%");
    }

    public function scopeOpen(Builder $q): Builder
    {
        return $q->whereIn('status', [
            TransferStatus::Draft->value,
            TransferStatus::Submitted->value,
            TransferStatus::Approved->value,
            TransferStatus::InTransit->value,
        ]);
    }

    // ─── Helpers ─────────────────────────────────────────────────────

    public function totalQuantity(): string
    {
        return (string) $this->lines->sum('quantity');
    }

    public function totalValue(): string
    {
        return (string) $this->lines->sum(fn ($l) => bcmul((string) $l->quantity, (string) $l->unit_cost, 4));
    }
}
