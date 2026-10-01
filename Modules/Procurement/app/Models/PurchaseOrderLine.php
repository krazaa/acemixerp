<?php

declare(strict_types=1);

namespace Modules\Procurement\Models;

use App\Models\Item;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Inventory\Models\Brand;
use Modules\Inventory\Models\Origin;

class PurchaseOrderLine extends Model
{
    protected $fillable = [
        'brand_id', 'origin_id',
        'purchase_order_id', 'position',
        'item_id', 'unit_id',
        'quantity', 'received_quantity', 'rejected_quantity', 'invoiced_quantity',
        'unit_price', 'tax_rate', 'wht_tax_rate', 'wht_line_tax', 'line_subtotal', 'line_tax', 'line_total',
        'required_date', 'specification',
        'rfq_line_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'received_quantity' => 'decimal:4',
            'rejected_quantity' => 'decimal:4',
            'invoiced_quantity' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'tax_rate' => 'decimal:6',
            'wht_tax_rate' => 'decimal:6',
            'wht_line_tax' => 'decimal:4',
            'line_subtotal' => 'decimal:4',
            'line_tax' => 'decimal:4',
            'line_total' => 'decimal:4',
            'required_date' => 'date',
            'position' => 'integer',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
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

    public function receiptLines(): HasMany
    {
        return $this->hasMany(GoodsReceiptLine::class);
    }

    public function openQuantity(): string
    {
        $open = bcsub((string) $this->quantity, (string) $this->received_quantity, 4);

        return bccomp($open, '0', 4) > 0 ? $open : '0.0000';
    }
}
