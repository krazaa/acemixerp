<?php

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesReturnLine extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_fill_keys(['requested_quantity', 'received_quantity', 'accepted_quantity', 'subtotal', 'tax', 'wht', 'total', 'unit_cost', 'cost'], 'decimal:4');
    }

    public function salesReturn(): BelongsTo
    {
        return $this->belongsTo(SalesReturn::class);
    }

    public function invoiceLine(): BelongsTo
    {
        return $this->belongsTo(SalesInvoiceLine::class, 'sales_invoice_line_id');
    }
}
