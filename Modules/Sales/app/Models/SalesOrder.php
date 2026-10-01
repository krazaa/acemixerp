<?php

declare(strict_types=1);

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
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Hr\Models\Employee;
use Modules\Sales\Enums\SalesOrderStatus;
use Modules\Sales\Models\SalesOrderLine;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SalesOrder extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'number', 'customer_id', 'order_date', 'expected_delivery_date',
        'salesperson_id', 'department_id', 'warehouse_id', 'payment_term_id',
        'currency_code', 'exchange_rate', 'reference',
        'subtotal', 'discount_total', 'tax_total', 'wht_tax_total', 'total',
        'notes', 'terms', 'status',
        'credit_limit_at_submission', 'outstanding_ar_at_submission',
        'credit_hold_reason', 'credit_hold_at', 'credit_hold_released_by',
        'submitted_at', 'approved_at', 'confirmed_at',
        'submitted_by', 'approved_by', 'confirmed_by',
        'source_type', 'source_id',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => SalesOrderStatus::class,
            'order_date' => 'date',
            'expected_delivery_date' => 'date',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'credit_hold_at' => 'datetime',
            'exchange_rate' => 'decimal:8',
            'credit_limit_at_submission' => 'decimal:4',
            'outstanding_ar_at_submission' => 'decimal:4',
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
            ->logOnly(['status', 'total', 'confirmed_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('sales_order');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalesOrderLine::class)->orderBy('position')->orderBy('id');
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
        return $this->belongsTo(Employee::class, 'salesperson_id');
    }

    public function salesorder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function holdReleaser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'credit_hold_released_by');
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

    public function scopeOpen(Builder $q): Builder
    {
        return $q->whereIn('status', [
            SalesOrderStatus::Confirmed->value,
            SalesOrderStatus::PartiallyDelivered->value,
        ]);
    }

    public function isFullyDelivered(): bool
    {
        foreach ($this->lines as $line) {
            if (bccomp((string) $line->delivered_quantity, (string) $line->quantity, 4) < 0) {
                return false;
            }
        }

        return true;
    }

    public function isPartiallyDelivered(): bool
    {
        foreach ($this->lines as $line) {
            if (bccomp((string) $line->delivered_quantity, '0', 4) > 0
                && bccomp((string) $line->delivered_quantity, (string) $line->quantity, 4) < 0) {
                return true;
            }
        }

        return false;
    }

    public function openQuantity(): string
    {
        $total = '0.0000';
        foreach ($this->lines as $line) {
            $open = bcsub((string) $line->quantity, (string) $line->delivered_quantity, 4);
            if (bccomp($open, '0', 4) > 0) {
                $total = bcadd($total, $open, 4);
            }
        }

        return $total;
    }

    public function source(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'source_type', 'source_id');
    }
}
