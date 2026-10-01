<?php

declare(strict_types=1);

namespace Modules\Procurement\Models;

use App\Models\CostCenter;
use App\Models\Department;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Procurement\Enums\RfqStatus;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class RequestForQuotation extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'number', 'issue_date', 'due_date',
        'department_id', 'cost_center_id',
        'currency_code', 'purpose', 'terms', 'status',
        'issued_at', 'issued_by',
        'awarded_at', 'awarded_by', 'awarded_quotation_id',
        'converted_to_type', 'converted_to_id',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => RfqStatus::class,
            'issue_date' => 'date',
            'due_date' => 'date',
            'issued_at' => 'datetime',
            'awarded_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'awarded_quotation_id', 'converted_to_type', 'converted_to_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('rfq');
    }

    // Relations
    public function lines(): HasMany
    {
        return $this->hasMany(RfqLine::class, 'request_for_quotation_id')
            ->orderBy('position')->orderBy('id');
    }

    public function vendors(): HasMany
    {
        return $this->hasMany(RfqVendor::class, 'request_for_quotation_id');
    }

    public function vendorList(): BelongsToMany
    {
        return $this->belongsToMany(
            Vendor::class,
            'rfq_vendors',
            'request_for_quotation_id',
            'vendor_id',
        )->withPivot('status', 'invited_at', 'submitted_at');
    }

    public function purchaseRequisitions(): BelongsToMany
    {
        return $this->belongsToMany(
            PurchaseRequisition::class,
            'purchase_requisition_rfq',
            'request_for_quotation_id',
            'purchase_requisition_id',
        )->withTimestamps();
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'source_id')->where('source_type', self::class);
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(VendorQuotation::class, 'request_for_quotation_id');
    }

    public function awardedQuotation(): BelongsTo
    {
        return $this->belongsTo(VendorQuotation::class, 'awarded_quotation_id');
    }

    public function sourceRequisitions(): BelongsToMany
    {
        return $this->belongsToMany(
            PurchaseRequisition::class,
            'purchase_requisition_rfq',
            'request_for_quotation_id',
            'purchase_requisition_id',
        );
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function awarder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'awarded_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (! $term) {
            return $q;
        }

        return $q->where(fn ($q) => $q
            ->where('number', 'like', "%{$term}%")
            ->orWhere('purpose', 'like', "%{$term}%"));
    }

    public function scopeStatus(Builder $q, RfqStatus|string $status): Builder
    {
        return $q->where('status', $status instanceof RfqStatus ? $status->value : $status);
    }
}
