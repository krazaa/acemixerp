<?php

namespace App\Models;

use App\Enums\ItemType;
use App\Enums\RecordStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Product extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'code', 'sku', 'barcode', 'name', 'description',
        'item_type', 'category_id', 'unit_id',
        'tax_rate_id', 'is_tax_exempt',
        'cost_price', 'selling_price', 'minimum_selling_price',
        'track_inventory', 'reorder_level', 'minimum_stock', 'maximum_stock',
        'allow_negative_stock',
        'track_batch', 'track_serial', 'track_expiry',
        'default_warehouse_id',
        'inventory_account_id', 'sales_account_id', 'cogs_account_id', 'expense_account_id',
        'image_path', 'is_sellable', 'is_purchasable', 'status',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'item_type' => ItemType::class,
            'status' => RecordStatus::class,
            'is_tax_exempt' => 'boolean',
            'cost_price' => 'decimal:4',
            'selling_price' => 'decimal:4',
            'minimum_selling_price' => 'decimal:4',
            'track_inventory' => 'boolean',
            'reorder_level' => 'decimal:4',
            'minimum_stock' => 'decimal:4',
            'maximum_stock' => 'decimal:4',
            'allow_negative_stock' => 'boolean',
            'track_batch' => 'boolean',
            'track_serial' => 'boolean',
            'track_expiry' => 'boolean',
            'is_sellable' => 'boolean',
            'is_purchasable' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('item');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', RecordStatus::Active->value);
    }

    public function scopeStock(Builder $q): Builder
    {
        return $q->where('item_type', ItemType::Stock->value);
    }

    public function scopeSellable(Builder $q): Builder
    {
        return $q->where('is_sellable', true)->where('status', RecordStatus::Active->value);
    }

    public function scopePurchasable(Builder $q): Builder
    {
        return $q->where('is_purchasable', true)->where('status', RecordStatus::Active->value);
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (! $term) {
            return $q;
        }

        return $q->where(fn ($q) => $q
            ->where('name', 'like', "%{$term}%")
            ->orWhere('code', 'like', "%{$term}%")
            ->orWhere('sku', 'like', "%{$term}%")
            ->orWhere('barcode', 'like', "%{$term}%"));
    }

    public function tracksInventory(): bool
    {
        return $this->item_type->tracksInventory() && $this->track_inventory;
    }

    public function marginPercent(): ?float
    {
        $cost = (float) $this->cost_price;
        $sell = (float) $this->selling_price;
        if ($cost <= 0 || $sell <= 0) {
            return null;
        }

        return (($sell - $cost) / $sell) * 100.0;
    }
}
