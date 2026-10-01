<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use App\Models\Item;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustmentLine extends Model
{
    protected $table = 'stock_adjustment_lines';

    protected $fillable = [
        'stock_adjustment_id',
        'item_id',
        'batch_id',
        'position',
        'quantity',      // signed: positive = add, negative = remove
        'unit_cost',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'position' => 'integer',
        ];
    }

    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class, 'stock_adjustment_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(StockBatch::class, 'batch_id');
    }

    public function isIncrease(): bool
    {
        return bccomp((string) $this->quantity, '0', 4) > 0;
    }

    public function isDecrease(): bool
    {
        return bccomp((string) $this->quantity, '0', 4) < 0;
    }

    public function lineValue(): string
    {
        return bcmul((string) $this->quantity, (string) $this->unit_cost, 4);
    }
}
