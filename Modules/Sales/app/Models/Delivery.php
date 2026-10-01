<?php

namespace Modules\Sales\Models;

use App\Models\Customer;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Sales\Enums\DeliveryStatus;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Delivery extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'number', 'sales_order_id', 'customer_id', 'warehouse_id',
        'delivery_date', 'expected_date',
        'reference', 'carrier', 'vendor_id', 'tracking_number', 'shipping_address',
        'vehicle_number', 'driver_name', 'driver_contact', 'driver_cnic', 'bilty_number',
        'notes', 'status',
        'picked_at', 'dispatched_at', 'delivered_at',
        'picked_by', 'dispatched_by', 'delivered_by',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => DeliveryStatus::class,
            'delivery_date' => 'date',
            'expected_date' => 'date',
            'picked_at' => 'datetime',
            'dispatched_at' => 'datetime',
            'delivered_at' => 'datetime',
            'driver_cnic' => 'encrypted',
        ];
    }

    protected $hidden = [
        'driver_cnic',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'dispatched_at', 'delivered_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('delivery');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(DeliveryLine::class)->orderBy('position')->orderBy('id');
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function picker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'picked_by');
    }

    public function dispatcher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatched_by');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function deliverer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivered_by');
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
            ->orWhere('reference', 'like', "%{$term}%")
            ->orWhere('tracking_number', 'like', "%{$term}%"));
    }

    public function isFullyDispatched(): bool
    {
        return $this->hasBeenDispatched();
    }

    public function hasBeenDispatched(): bool
    {
        return $this->dispatched_at !== null;
    }
}
