<?php

namespace Modules\Procurement\Models;

use App\Models\CostCenter;
use App\Models\Department;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Procurement\Enums\PurchaseRequisitionStatus;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PurchaseRequisition extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'number', 'requested_date', 'required_date',
        'department_id', 'cost_center_id', 'requested_by', 'warehouse_id',
        'purpose', 'status',
        'submitted_at', 'approved_at', 'converted_at',
        'converted_to_type', 'converted_to_id',
        'submitted_by', 'approved_by', 'rejected_by',
        'notes', 'total_estimated',
        'created_by', 'updated_by', 'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PurchaseRequisitionStatus::class,
            'requested_date' => 'date',
            'required_date' => 'date',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'converted_at' => 'datetime',
            'total_estimated' => 'decimal:4',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'total_estimated', 'converted_to_type', 'converted_to_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('purchase_requisition');
    }

    // ─── Relations ───────────────────────────────────────────────────

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseRequisitionLine::class)
            ->orderBy('position')
            ->orderBy('id');
    }

    public function rfqs(): BelongsToMany
    {
        return $this->belongsToMany(
            RequestForQuotation::class,
            'purchase_requisition_rfq',
            'purchase_requisition_id',
            'request_for_quotation_id',
        )->withTimestamps();
    }

    // Conversion target (polymorphic-ish, but scoped for now)
    public function convertedTo(): MorphTo
    {
        return $this->morphTo('converted_to', 'converted_to_type', 'converted_to_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ─── Scopes ──────────────────────────────────────────────────────

    public function scopeStatus(Builder $q, PurchaseRequisitionStatus|string $status): Builder
    {
        return $q->where('status', $status instanceof PurchaseRequisitionStatus ? $status->value : $status);
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (! $term) {
            return $q;
        }

        return $q->where(fn ($q) => $q
            ->where('number', 'like', "%{$term}%")
            ->orWhere('purpose', 'like', "%{$term}%"));
    }

    public function scopePendingApproval(Builder $q): Builder
    {
        return $q->whereIn('status', ['submitted', 'under_review']);
    }

    public function isEditable(): bool
    {
        return $this->status->isEditable();
    }
}
