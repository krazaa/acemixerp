<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use App\Models\Item;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransferLine extends Model
{
    protected $table = 'stock_transfer_lines';

    protected $fillable = [
        'stock_transfer_id',
        'item_id',
        'unit_id',
        'batch_id',
        'position',
        'quantity',
        'dispatched_quantity',
        'received_quantity',
        'unit_cost',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'dispatched_quantity' => 'decimal:4',
            'received_quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'position' => 'integer',
        ];
    }

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class, 'stock_transfer_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(StockBatch::class, 'batch_id');
    }

    public function isFullyReceived(): bool
    {
        return bccomp(
            (string) $this->received_quantity,
            (string) $this->quantity,
            4,
        ) === 0;
    }

    public function pendingQuantity(): string
    {
        $pending = bcsub((string) $this->quantity, (string) $this->received_quantity, 4);

        return bccomp($pending, '0', 4) > 0 ? $pending : '0.0000';
    }
}
