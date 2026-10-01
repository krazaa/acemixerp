<?php

declare(strict_types=1);

namespace Modules\Manufacturing\Models;

use App\Models\Item;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Inventory\Models\StockBatch;

class ProductionOrderLine extends Model
{
    protected $table = 'production_order_lines';

    protected $fillable = [
        'production_order_id', 'component_id', 'batch_id', 'unit_id',
        'required_quantity', 'issued_quantity', 'returned_quantity', 'waste_quantity',
        'unit_cost', 'line_cost', 'position', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'required_quantity' => 'decimal:4',
            'issued_quantity' => 'decimal:4',
            'returned_quantity' => 'decimal:4',
            'waste_quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'line_cost' => 'decimal:4',
            'position' => 'integer',
        ];
    }

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'component_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(StockBatch::class, 'batch_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function remainingToIssue(): string
    {
        $remaining = bcsub((string) $this->required_quantity, (string) $this->issued_quantity, 4);

        return bccomp($remaining, '0', 4) > 0 ? $remaining : '0.0000';
    }
}
