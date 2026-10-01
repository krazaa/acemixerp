<?php

declare(strict_types=1);

namespace Modules\Procurement\Models;

use App\Models\CostCenter;
use App\Models\Department;
use App\Models\PaymentTerm;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Procurement\Enums\PurchaseOrderStatus;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PurchaseOrder extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'number', 'vendor_id', 'order_date', 'expected_date',
        'department_id', 'cost_center_id', 'warehouse_id',
        'currency_code', 'payment_term_id',
        'reference', 'shipping_address', 'notes', 'terms',
        'status', 'subtotal', 'tax_total', 'total',
        'submitted_at', 'approved_at', 'issued_at', 'closed_at',
        'submitted_by', 'approved_by', 'issued_by',
        'source_type', 'source_id',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => PurchaseOrderStatus::class,
            'order_date' => 'date',
            'expected_date' => 'date',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'issued_at' => 'datetime',
            'closed_at' => 'datetime',
            'subtotal' => 'decimal:4',
            'tax_total' => 'decimal:4',
            'total' => 'decimal:4',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'total', 'issued_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('purchase_order');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class)
            ->orderBy('position')->orderBy('id');
    }

    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function purchaseRequisition(): BelongsTo
    {
        return $this->belongsTo(
            PurchaseRequisition::class,
            // 'purchase_requisition_id'
            'converted_to_id'
        );
    }

    public function source(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'source_type', 'source_id');
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
            PurchaseOrderStatus::Issued->value,
            PurchaseOrderStatus::PartiallyReceived->value,
        ]);
    }

    public function isFullyReceived(): bool
    {
        foreach ($this->lines as $line) {
            if (bccomp((string) $line->received_quantity, (string) $line->quantity, 4) < 0) {
                return false;
            }
        }

        return true;
    }

    public function isPartiallyReceived(): bool
    {
        foreach ($this->lines as $line) {
            if (bccomp((string) $line->received_quantity, '0', 4) > 0
                && bccomp((string) $line->received_quantity, (string) $line->quantity, 4) < 0) {
                return true;
            }
        }

        return false;
    }

    public function openQuantity(): string
    {
        $total = '0.0000';
        foreach ($this->lines as $line) {
            $open = bcsub((string) $line->quantity, (string) $line->received_quantity, 4);
            if (bccomp($open, '0', 4) > 0) {
                $total = bcadd($total, $open, 4);
            }
        }

        return $total;
    }
}
