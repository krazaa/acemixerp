<?php

declare(strict_types=1);

namespace Modules\Manufacturing\Models;

use App\Models\Item;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Inventory\Models\StockBatch;

class BomLine extends Model
{
    protected $table = 'bom_lines';

    protected $fillable = [
        'bill_of_materials_id', 'component_id', 'batch_id', 'unit_id',
        'quantity', 'scrap_percent', 'position', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'scrap_percent' => 'decimal:6',
            'position' => 'integer',
        ];
    }

    public function bom(): BelongsTo
    {
        return $this->belongsTo(BillOfMaterials::class, 'bill_of_materials_id');
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

    /** Quantity needed including scrap. */
    public function effectiveQuantity(): string
    {
        $base = (string) $this->quantity;
        $scrap = bcadd('1', bcdiv((string) $this->scrap_percent, '100', 8), 8);

        return bcmul($base, $scrap, 4);
    }
}
