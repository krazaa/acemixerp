<?php

declare(strict_types=1);

namespace Modules\Manufacturing\Models;

use App\Models\Item;
use App\Models\JournalEntry;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Manufacturing\Enums\ProductionOrderStatus;
use Modules\Manufacturing\Enums\ProductionOrderType;
use Modules\Sales\Models\SalesOrder;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ProductionOrder extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'production_orders';

    protected $fillable = [
        'number', 'bill_of_materials_id', 'product_id',
        'planned_quantity', 'produced_quantity',
        'source_warehouse_id',
        'scheduled_start_date', 'scheduled_end_date',
        'actual_start_date', 'actual_end_date',
        'sales_order_id', 'type', 'status', 'notes',
        'planned_at', 'released_at', 'started_at', 'completed_at', 'closed_at',
        'planned_by', 'released_by', 'started_by', 'completed_by', 'closed_by',
        'materials_cost', 'labour_cost', 'overhead_cost', 'total_cost', 'unit_cost',
        'journal_entry_id',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProductionOrderStatus::class,
            'type' => ProductionOrderType::class,
            'planned_quantity' => 'decimal:4',
            'produced_quantity' => 'decimal:4',
            'materials_cost' => 'decimal:4',
            'labour_cost' => 'decimal:4',
            'overhead_cost' => 'decimal:4',
            'total_cost' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'scheduled_start_date' => 'date',
            'scheduled_end_date' => 'date',
            'actual_start_date' => 'date',
            'actual_end_date' => 'date',
            'planned_at' => 'datetime',
            'released_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'planned_quantity', 'produced_quantity', 'total_cost'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('production_order');
    }

    public function bom(): BelongsTo
    {
        return $this->belongsTo(BillOfMaterials::class, 'bill_of_materials_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'product_id');
    }

    public function sourceWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'source_warehouse_id');
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function planner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'planned_by');
    }

    public function releaser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    public function starter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(ProductionOrderLine::class)->orderBy('position')->orderBy('id');
    }

    public function outputs(): HasMany
    {
        return $this->hasMany(ProductionOutput::class);
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (! $term) {
            return $q;
        }

        return $q->where('number', 'like', "%{$term}%");
    }

    public function scopeOpen(Builder $q): Builder
    {
        return $q->whereIn('status', [
            ProductionOrderStatus::Draft->value,
            ProductionOrderStatus::Planned->value,
            ProductionOrderStatus::Released->value,
            ProductionOrderStatus::InProgress->value,
        ]);
    }

    public function remainingQuantity(): string
    {
        $remaining = bcsub((string) $this->planned_quantity, (string) $this->produced_quantity, 4);

        return bccomp($remaining, '0', 4) > 0 ? $remaining : '0.0000';
    }

    public function progressPercent(): int
    {
        if (bccomp((string) $this->planned_quantity, '0', 4) === 0) {
            return 100;
        }
        $ratio = bcdiv((string) $this->produced_quantity, (string) $this->planned_quantity, 4);

        return min(100, (int) round((float) $ratio * 100));
    }
}
