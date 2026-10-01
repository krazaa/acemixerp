<?php

declare(strict_types=1);

namespace Modules\Procurement\Models;

use App\Models\Item;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Inventory\Models\Brand;
use Modules\Inventory\Models\Origin;

class GoodsReceiptLine extends Model
{
    protected $fillable = [
        'brand_id', 'origin_id',
        'goods_receipt_id', 'purchase_order_line_id', 'item_id', 'position',
        'received_quantity', 'accepted_quantity', 'rejected_quantity',
        'batch_number', 'manufacturing_date', 'expiry_date', 'rejection_reason', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'received_quantity' => 'decimal:4',
            'accepted_quantity' => 'decimal:4',
            'rejected_quantity' => 'decimal:4',
            'expiry_date' => 'date',
            'manufacturing_date' => 'date',
            'position' => 'integer',
        ];
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function purchaseOrderLine(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderLine::class);
    }

    public function origin(): BelongsTo
    {
        return $this->belongsTo(Origin::class)->withTrashed();
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class)->withTrashed();
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
