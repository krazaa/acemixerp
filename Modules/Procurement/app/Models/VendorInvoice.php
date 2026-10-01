<?php

namespace Modules\Procurement\Models;

use App\Models\JournalEntry;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

// use Modules\Procurement\Database\Factories\VendorInvoiceFactory;

class VendorInvoice extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['number', 'vendor_id', 'vendor_invoice_number', 'invoice_date', 'due_date', 'billing_month', 'subtotal', 'tax_total', 'whttax_total', 'total', 'status', 'approved_at', 'approved_by', 'owner_approved_at', 'owner_approved_by', 'rejection_reason', 'posted_at', 'posted_by', 'journal_entry_id', 'paid_amount', 'notes', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'approved_at' => 'datetime',
            'owner_approved_at' => 'datetime',
            'posted_at' => 'datetime',
            'subtotal' => 'decimal:4',
            'tax_total' => 'decimal:4',
            'whttax_total' => 'decimal:4',
            'total' => 'decimal:4',
            'paid_amount' => 'decimal:4',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(VendorInvoiceLine::class)->orderBy('position');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function paymentAllocations(): MorphMany
    {
        return $this->morphMany(PaymentAllocation::class, 'allocatable');
    }

    // protected static function newFactory(): VendorInvoiceFactory
    // {
    //     // return VendorInvoiceFactory::new();
    // }
}
