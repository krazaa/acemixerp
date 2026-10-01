<?php

namespace Modules\Sales\Models;

use App\Models\JournalEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesCreditNote extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['credit_date' => 'date', 'posted_at' => 'datetime', 'subtotal' => 'decimal:4', 'tax' => 'decimal:4', 'wht' => 'decimal:4', 'total' => 'decimal:4'];
    }

    public function salesReturn(): BelongsTo
    {
        return $this->belongsTo(SalesReturn::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
