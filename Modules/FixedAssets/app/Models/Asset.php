<?php

namespace Modules\FixedAssets\Models;

use App\Models\Account;
use App\Models\Department;
use App\Models\Item;
use App\Models\JournalEntry;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\FixedAssets\Enums\AssetStatus;

class Asset extends Model
{
    use SoftDeletes;

    protected $table = 'fixed_assets';

    protected $fillable = [
        'asset_number', 'name', 'description', 'item_id', 'vendor_id', 'supplier_invoice_id',
        'acquisition_date', 'in_service_date', 'capitalized_at', 'cost', 'salvage_value',
        'useful_life_months', 'depreciation_method', 'accumulated_depreciation', 'carrying_amount',
        'status', 'location', 'department_id', 'asset_account_id',
        'accumulated_depreciation_account_id', 'depreciation_expense_account_id',
        'capitalization_journal_entry_id', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => AssetStatus::class,
            'acquisition_date' => 'date',
            'in_service_date' => 'date',
            'capitalized_at' => 'date',
            'cost' => 'decimal:4',
            'salvage_value' => 'decimal:4',
            'accumulated_depreciation' => 'decimal:4',
            'carrying_amount' => 'decimal:4',
        ];
    }

    public function assetAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'asset_account_id');
    }

    public function accumulatedDepreciationAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'accumulated_depreciation_account_id');
    }

    public function depreciationExpenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'depreciation_expense_account_id');
    }

    public function capitalizationJournalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'capitalization_journal_entry_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(AssetTransaction::class)->orderByDesc('transaction_date')->orderByDesc('id');
    }
}
