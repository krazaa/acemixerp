<?php

namespace Modules\Procurement\Models;

use App\Models\Item;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorQuotationLine extends Model
{
    protected $fillable = [
        'vendor_quotation_id', 'rfq_line_id', 'item_id', 'position',
        'quantity', 'unit_price', 'line_total',
        'tax_rate', 'tax_amount', 'wht_tax_rate', 'wht_tax_amount', 'lead_time_days', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'line_total' => 'decimal:4',
            'tax_rate' => 'decimal:6',
            'tax_amount' => 'decimal:4',
            'wht_tax_rate' => 'decimal:6',
            'wht_tax_amount' => 'decimal:4',
            'lead_time_days' => 'integer',
            'position' => 'integer',
        ];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(VendorQuotation::class, 'vendor_quotation_id');
    }

    public function rfqLine(): BelongsTo
    {
        return $this->belongsTo(RfqLine::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
