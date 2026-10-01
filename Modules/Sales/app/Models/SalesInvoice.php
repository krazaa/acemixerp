<?php

namespace Modules\Sales\Models;

use App\Models\CostCenter;
use App\Models\Customer;
use App\Models\Department;
use App\Models\JournalEntry;
use App\Models\PaymentAllocation;
use App\Models\PaymentTerm;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Sales\Enums\SalesInvoiceStatus;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SalesInvoice extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'sales_invoices';

    protected $fillable = [
        'number', 'customer_id', 'sales_order_id',
        'invoice_date', 'due_date',
        'warehouse_id', 'department_id', 'cost_center_id', 'payment_term_id',
        'currency_code', 'exchange_rate', 'reference',
        'subtotal', 'discount_total', 'tax_total', 'wht_tax_total',
        'total', 'paid_amount',
        'notes', 'terms', 'status',
        'match_status', 'match_notes', 'matched_at', 'matched_by',
        'approved_at', 'posted_at', 'approved_by', 'posted_by',
        'journal_entry_id', 'reverses_id', 'reversed_by_id',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => SalesInvoiceStatus::class,
            'invoice_date' => 'date',
            'due_date' => 'date',
            'matched_at' => 'datetime',
            'approved_at' => 'datetime',
            'posted_at' => 'datetime',
            'exchange_rate' => 'decimal:8',
            'subtotal' => 'decimal:4',
            'discount_total' => 'decimal:4',
            'tax_total' => 'decimal:4',
            'wht_tax_total' => 'decimal:4',
            'total' => 'decimal:4',
            'paid_amount' => 'decimal:4',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'match_status', 'total', 'paid_amount', 'journal_entry_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('sales_invoice');
    }

    // ─── Relations ───────────────────────────────────────────────────

    public function lines(): HasMany
    {
        return $this->hasMany(SalesInvoiceLine::class)->orderBy('position')->orderBy('id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
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

    public function reverses(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reverses_id');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversed_by_id');
    }

    public function allocations(): MorphMany
    {
        return $this->morphMany(PaymentAllocation::class, 'allocatable');
    }

    // ─── Scopes ──────────────────────────────────────────────────────

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (! $term) {
            return $q;
        }

        return $q->where(function ($q) use ($term) {
            $q->where('number', 'like', "%{$term}%")
                ->orWhere('reference', 'like', "%{$term}%");
        });
    }

    public function scopeUnpaid(Builder $q): Builder
    {
        return $q->whereIn('status', [
            SalesInvoiceStatus::Posted->value,
            SalesInvoiceStatus::PartiallyPaid->value,
        ]);
    }

    public function scopeOverdue(Builder $q): Builder
    {
        return $q->unpaid()->whereDate('due_date', '<', now()->toDateString());
    }

    // ─── Helpers ─────────────────────────────────────────────────────

    public function outstanding(): string
    {
        $balance = bcsub(bcsub((string) $this->total, (string) $this->paid_amount, 4), $this->creditedTotal(), 4);

        return bccomp($balance, '0', 4) > 0 ? $balance : '0.0000';
    }

    public function returns(): HasMany
    {
        return $this->hasMany(SalesReturn::class);
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(SalesCreditNote::class);
    }

    public function creditedTotal(): string
    {
        return (string) ($this->getAttribute('posted_credit_total') ?? $this->creditNotes()->where('status', 'posted')->sum('total'));
    }

    public function isFullyPaid(): bool
    {
        return bccomp($this->outstanding(), '0', 4) === 0;
    }

    public function daysOverdue(): int
    {
        if (! $this->due_date || $this->isFullyPaid()) {
            return 0;
        }
        if ($this->due_date->isFuture()) {
            return 0;
        }

        return (int) $this->due_date->diffInDays(now());
    }
}
