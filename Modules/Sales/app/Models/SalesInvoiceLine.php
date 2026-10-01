<?php

declare(strict_types=1);

namespace Modules\Sales\Models;

use App\Models\Item;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesInvoiceLine extends Model
{
    protected $table = 'sales_invoice_lines';

    protected $fillable = [
        'sales_invoice_id', 'sales_order_line_id', 'delivery_line_id',
        'item_id', 'unit_id', 'position',
        'quantity', 'unit_price', 'discount_percent', 'discount_amount',
        'tax_rate', 'wht_tax_rate',
        'line_subtotal', 'line_tax', 'line_wht_tax', 'line_total',
        'unit_cost', 'cogs_amount',
        'revenue_account_id', 'tax_account_id', 'cogs_account_id', 'inventory_account_id',
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
            'unit_cost' => 'decimal:4',
            'cogs_amount' => 'decimal:4',
            'position' => 'integer',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    public function salesOrderLine(): BelongsTo
    {
        return $this->belongsTo(SalesOrderLine::class);
    }

    public function deliveryLine(): BelongsTo
    {
        return $this->belongsTo(DeliveryLine::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
