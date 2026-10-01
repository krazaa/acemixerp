<?php

namespace Modules\Procurement\Models;

use App\Models\Item;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Inventory\Models\Brand;
use Modules\Inventory\Models\Origin;
use Modules\Procurement\Models\PurchaseRequisition;

class PurchaseRequisitionLine extends Model
{
    protected $fillable = [
        'brand_id','origin_id',
        'purchase_requisition_id', 'position',
        'item_id', 'unit_id',
        'quantity', 'estimated_unit_price', 'estimated_line_total',
        'required_date', 'specification',
        'source_type', 'source_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'estimated_unit_price' => 'decimal:4',
            'estimated_line_total' => 'decimal:4',
            'required_date' => 'date',
            'position' => 'integer',
        ];
    }

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class, 'purchase_requisition_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class)->withTrashed();
    }

    public function origin(): BelongsTo
    {
        return $this->belongsTo(Origin::class)->withTrashed();
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
