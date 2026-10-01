<?php

namespace Modules\Inventory\Models;

use App\Models\Item;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockBatch extends Model
{
    protected $fillable = ['item_id', 'warehouse_id', 'number', 'manufacturing_date', 'expiry_date'];

    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'manufacturing_date' => 'date',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
