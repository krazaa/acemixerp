<?php

namespace Modules\Sales\Models;

use App\Models\Customer;
use App\Models\Department;
use App\Models\PaymentTerm;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Sales\Enums\QuotationStatus;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Quotation extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'number', 'customer_id', 'quotation_date', 'valid_until',
        'salesperson_id', 'department_id', 'warehouse_id', 'payment_term_id',
        'currency_code', 'exchange_rate', 'reference',
        'subtotal', 'discount_total', 'wht_tax_total', 'tax_total', 'total',
        'notes', 'terms', 'status',
        'sent_at', 'accepted_at', 'sent_by', 'accepted_by',
        'converted_to_type', 'converted_to_id',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => QuotationStatus::class,
            'quotation_date' => 'date',
            'valid_until' => 'date',
            'sent_at' => 'datetime',
            'accepted_at' => 'datetime',
            'exchange_rate' => 'decimal:8',
            'subtotal' => 'decimal:4',
            'discount_total' => 'decimal:4',
            'tax_total' => 'decimal:4',
            'wht_tax_total' => 'decimal:4',
            'total' => 'decimal:4',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'total', 'converted_to_type', 'converted_to_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('quotation');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(QuotationLine::class)->orderBy('position')->orderBy('id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class);
    }

    public function salesperson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salesperson_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function accepter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
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
            ->orWhere('reference', 'like', "%{$term}%"));
    }

    public function isExpired(): bool
    {
        return $this->valid_until !== null
            && $this->status === QuotationStatus::Sent
            && $this->valid_until->isPast();
    }
}
