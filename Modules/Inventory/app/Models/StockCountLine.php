<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use App\Models\Item;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockCountLine extends Model
{
    protected $table = 'stock_count_lines';

    protected $fillable = [
        'stock_count_id',
        'item_id',
        'position',
        'system_quantity',
        'counted_quantity',
        'variance',
        'unit_cost',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'system_quantity' => 'decimal:4',
            'counted_quantity' => 'decimal:4',
            'variance' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'position' => 'integer',
        ];
    }

    public function stockCount(): BelongsTo
    {
        return $this->belongsTo(StockCount::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function isCounted(): bool
    {
        return $this->counted_quantity !== null;
    }

    public function hasVariance(): bool
    {
        return bccomp((string) $this->variance, '0', 4) !== 0;
    }

    /**
     * Variance expressed in money terms (variance × unit_cost).
     */
    public function varianceValue(): string
    {
        return bcmul((string) $this->variance, (string) $this->unit_cost, 4);
    }

    /**
     * Recompute variance from system_quantity and counted_quantity.
     * Does not save; callers persist explicitly.
     */
    public function recomputeVariance(): void
    {
        if ($this->counted_quantity === null) {
            $this->variance = '0.0000';

            return;
        }

        $this->variance = bcsub(
            (string) $this->counted_quantity,
            (string) $this->system_quantity,
            4,
        );
    }
}
