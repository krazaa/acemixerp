<?php

namespace Modules\Procurement\Models;

use App\Enums\PaymentMethod;
use App\Models\BankAccount;
use App\Models\JournalEntry;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Procurement\Enums\VendorPaymentStatus;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class VendorPayment extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'number', 'vendor_id', 'payment_date', 'billing_month',
        'currency_code', 'exchange_rate',
        'amount', 'allocated_amount',
        'payment_method', 'bank_account_id', 'reference',
        'notes', 'status',
        'submitted_at', 'approved_at', 'posted_at',
        'submitted_by', 'approved_by', 'posted_by',
        'journal_entry_id',
        'reverses_id', 'reversed_by_id',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => VendorPaymentStatus::class,
            'payment_method' => PaymentMethod::class,
            'payment_date' => 'date',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'posted_at' => 'datetime',
            'exchange_rate' => 'decimal:8',
            'amount' => 'decimal:4',
            'allocated_amount' => 'decimal:4',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'amount', 'allocated_amount', 'journal_entry_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('vendor_payment');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
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
        return $this->morphMany(PaymentAllocation::class, 'payment');
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (! $term) {
            return $q;
        }

        return $q->where(fn ($q) => $q
            ->where('number', 'like', "%{$term}%")
            ->orWhere('reference', 'like', "%{$term}%"));
    }

    public function unallocatedAmount(): string
    {
        return bcsub((string) $this->amount, (string) $this->allocated_amount, 4);
    }

    public function isFullyAllocated(): bool
    {
        return bccomp((string) $this->amount, (string) $this->allocated_amount, 4) === 0;
    }
}
