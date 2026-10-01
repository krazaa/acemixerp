<?php

namespace Modules\Sales\Models;

use App\Models\Item;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationLine extends Model
{
    protected $fillable = [
        'quotation_id', 'position',
        'item_id', 'unit_id',
        'quantity', 'unit_price',
        'discount_percent', 'discount_amount',
        'tax_rate', 'wht_tax_rate',
        'line_subtotal', 'line_tax', 'line_wht_tax', 'line_total',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'discount_percent' => 'decimal:6',
            'discount_amount' => 'decimal:4',
            'tax_rate' => 'decimal:6',
            'wht_tax_rate' => 'decimal:6',
            'line_subtotal' => 'decimal:4',
            'line_tax' => 'decimal:4',
            'line_wht_tax' => 'decimal:4',
            'line_total' => 'decimal:4',
            'position' => 'integer',
        ];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function recalculate(): void
    {
        $gross = bcmul((string) $this->quantity, (string) $this->unit_price, 4);
        $discount = bcmul($gross, bcdiv((string) $this->discount_percent, '100', 8), 4);
        $subtotal = bcsub($gross, $discount, 4);

        $taxRate = bcdiv((string) $this->tax_rate, '100', 8);
        $whtRate = bcdiv((string) $this->wht_tax_rate, '100', 8);

        $tax = bcmul($subtotal, $taxRate, 4);
        $whtAmount = bcmul(bcadd($subtotal, $tax, 4), $whtRate, 4);

        $total = bcsub(bcadd($subtotal, $tax, 4), $whtAmount, 4);

        $this->forceFill([
            'discount_amount' => $discount,
            'line_subtotal' => $subtotal,
            'line_tax' => $tax,
            'line_wht_tax' => $whtAmount,
            'line_total' => $total,
        ])->save();
    }
}
