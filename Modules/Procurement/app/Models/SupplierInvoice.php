<?php

declare(strict_types=1);

namespace Modules\Procurement\Models;

use App\Models\JournalEntry;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Procurement\Enums\SupplierInvoiceStatus;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SupplierInvoice extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'number', 'vendor_id', 'purchase_order_id',
        'vendor_invoice_number', 'invoice_date', 'due_date', 'currency_code',
        'subtotal', 'tax_total', 'wht_tax_total', 'total',
        'match_status', 'match_notes', 'matched_at', 'matched_by',
        'status',
        'approved_at', 'posted_at', 'approved_by', 'posted_by',
        'journal_entry_id',
        'paid_amount', 'notes',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => SupplierInvoiceStatus::class,
            'invoice_date' => 'date',
            'due_date' => 'date',
            'matched_at' => 'datetime',
            'approved_at' => 'datetime',
            'posted_at' => 'datetime',
            'subtotal' => 'decimal:4',
            'tax_total' => 'decimal:4',
            'wht_tax_total' => 'decimal:4',
            'total' => 'decimal:4',
            'paid_amount' => 'decimal:4',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'match_status', 'total', 'journal_entry_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('supplier_invoice');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SupplierInvoiceLine::class)
            ->orderBy('position')->orderBy('id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function paymentAllocations(): MorphMany
    {
        return $this->morphMany(PaymentAllocation::class, 'allocatable');
    }

    public function matcher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'matched_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (! $term) {
            return $q;
        }

        return $q->where(fn ($q) => $q
            ->where('number', 'like', "%{$term}%")
            ->orWhere('vendor_invoice_number', 'like', "%{$term}%"));
    }

    public function scopeUnpaid(Builder $q): Builder
    {
        return $q->whereIn('status', [
            SupplierInvoiceStatus::Posted->value,
            SupplierInvoiceStatus::PartiallyPaid->value,
        ]);
    }

    public function outstanding(): string
    {
        return bcsub((string) $this->total, (string) $this->paid_amount, 4);
    }
}
