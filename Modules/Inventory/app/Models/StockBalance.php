<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use App\Models\Item;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cached on-hand balance per item × warehouse × batch × serial.
 *
 * This table is a projection of `stock_movements`. Never write to it directly
 * from business code — the ledger maintains it inside the same transaction
 * that writes each movement. See `DatabaseStockLedger`.
 */
class StockBalance extends Model
{
    protected $table = 'stock_balances';

    protected $fillable = [
        'item_id',
        'warehouse_id',
        'batch_id',
        'serial_number',
        'quantity',
        'total_value',
        'reserved_quantity',
        'last_movement_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'total_value' => 'decimal:4',
            'reserved_quantity' => 'decimal:4',
            'last_movement_at' => 'datetime',
        ];
    }

    // ─── Relations ───────────────────────────────────────────────────

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(StockBatch::class, 'batch_id');
    }

    // ─── Scopes ──────────────────────────────────────────────────────

    public function scopeForItem(Builder $q, int $itemId): Builder
    {
        return $q->where('item_id', $itemId);
    }

    public function scopeInWarehouse(Builder $q, int $warehouseId): Builder
    {
        return $q->where('warehouse_id', $warehouseId);
    }

    public function scopeWithPositiveQuantity(Builder $q): Builder
    {
        return $q->where('quantity', '>', 0);
    }

    public function scopeWithNegativeQuantity(Builder $q): Builder
    {
        return $q->where('quantity', '<', 0);
    }

    /**
     * Items whose on-hand quantity has fallen to or below their reorder level.
     * Requires the joined `items` table — call sites that join should use this
     * scope; bare calls should use the has() variant below.
     */
    public function scopeBelowReorderLevel(Builder $q): Builder
    {
        return $q->whereHas('item', fn ($q) => $q
            ->where('reorder_level', '>', 0)
            ->whereColumn('stock_balances.quantity', '<=', 'items.reorder_level'));
    }

    // ─── Helpers ─────────────────────────────────────────────────────

    /**
     * Quantity available for new commitments (on-hand minus reserved).
     */
    public function availableQuantity(): string
    {
        return bcsub(
            (string) $this->quantity,
            (string) $this->reserved_quantity,
            4,
        );
    }

    /**
     * Average unit cost derived from total_value / quantity.
     * Returns zero when quantity is zero (avoids division by zero).
     */
    public function averageUnitCost(): string
    {
        if (bccomp((string) $this->quantity, '0', 4) === 0) {
            return '0.0000';
        }

        return bcdiv(
            (string) $this->total_value,
            (string) $this->quantity,
            4,
        );
    }

    /**
     * Whether this balance row represents stock that has gone negative
     * (allowed only when both item and warehouse policies permit it).
     */
    public function isNegative(): bool
    {
        return bccomp((string) $this->quantity, '0', 4) < 0;
    }

    /**
     * Whether the row is below the reorder threshold (requires item to be loaded).
     */
    public function isBelowReorderLevel(): bool
    {
        $item = $this->item;
        if (! $item || bccomp((string) $item->reorder_level, '0', 4) <= 0) {
            return false;
        }

        return bccomp((string) $this->quantity, (string) $item->reorder_level, 4) <= 0;
    }

    /**
     * Human-readable label of the location dimension of this balance row.
     * Example: "Main Warehouse · Batch LOT-123".
     */
    public function locationLabel(): string
    {
        $parts = [];

        if ($this->relationLoaded('warehouse') && $this->warehouse) {
            $parts[] = $this->warehouse->name;
        }

        if ($this->batch_id) {
            $parts[] = 'Batch #'.$this->batch_id;
        }

        if ($this->serial_number) {
            $parts[] = 'SN '.$this->serial_number;
        }

        return $parts ? implode(' · ', $parts) : '—';
    }
}
