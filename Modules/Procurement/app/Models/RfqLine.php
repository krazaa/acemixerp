<?php

namespace Modules\Procurement\Models;

use App\Models\Item;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Inventory\Models\Brand;
use Modules\Inventory\Models\Origin;

class RfqLine extends Model
{
    protected $fillable = [
        'brand_id','origin_id',
        'request_for_quotation_id', 'position',
        'item_id', 'unit_id',
        'quantity', 'specification',
        'purchase_requisition_line_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'position' => 'integer',
        ];
    }

    public function awardedQuotationLine(): BelongsTo
    {
        return $this->belongsTo(VendorQuotationLine::class, 'awarded_quotation_line_id');
    }

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(RequestForQuotation::class, 'request_for_quotation_id');
    }

    public function purchaseRequisitionLine(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisitionLine::class);
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

    public function sourceLine(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisitionLine::class, 'purchase_requisition_line_id');
    }
}
