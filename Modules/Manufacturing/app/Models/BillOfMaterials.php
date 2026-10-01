<?php

declare(strict_types=1);

namespace Modules\Manufacturing\Models;

use App\Models\Item;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Manufacturing\Enums\BomStatus;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class BillOfMaterials extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'bill_of_materials';

    protected $fillable = [
        'code', 'name', 'revision', 'product_id', 'output_quantity', 'output_unit_id',
        'labour_cost', 'overhead_cost', 'notes', 'status', 'superseded_by_id',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => BomStatus::class,
            'output_quantity' => 'decimal:4',
            'labour_cost' => 'decimal:4',
            'overhead_cost' => 'decimal:4',
            'revision' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'revision', 'product_id', 'output_quantity'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('bill_of_materials');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'product_id');
    }

    public function outputUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'output_unit_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function supersededBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'superseded_by_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BomLine::class)->orderBy('position')->orderBy('id');
    }

    public function productionOrders(): HasMany
    {
        return $this->hasMany(ProductionOrder::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', BomStatus::Active->value);
    }

    public function scopeForProduct(Builder $q, int $productId): Builder
    {
        return $q->where('product_id', $productId);
    }

    /** Approximate materials cost using current item costs. */
    public function estimatedMaterialsCost(): string
    {
        $total = '0.0000';
        foreach ($this->lines as $line) {
            $componentCost = (string) ($line->component?->cost_price ?? '0.0000');
            $lineCost = bcmul((string) $line->quantity, $componentCost, 4);
            $total = bcadd($total, $lineCost, 4);
        }

        return $total;
    }

    public function estimatedUnitCost(): string
    {
        $output = (string) $this->output_quantity;
        if (bccomp($output, '0', 4) === 0) {
            return '0.0000';
        }

        $total = bcadd(
            bcadd($this->estimatedMaterialsCost(), (string) $this->labour_cost, 4),
            (string) $this->overhead_cost,
            4,
        );

        return bcdiv($total, $output, 4);
    }
}
