<?php

declare(strict_types=1);

namespace Modules\FixedAssets\Models;

use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\FixedAssets\Enums\AssetTransactionType;

class AssetTransaction extends Model
{
    protected $table = 'fixed_asset_transactions';

    protected $fillable = ['asset_id', 'type', 'transaction_date', 'amount', 'reference', 'description', 'from_location', 'to_location', 'carrying_amount_before', 'carrying_amount_after', 'journal_entry_id', 'metadata', 'created_by'];

    protected function casts(): array
    {
        return ['type' => AssetTransactionType::class, 'transaction_date' => 'date', 'amount' => 'decimal:4', 'carrying_amount_before' => 'decimal:4', 'carrying_amount_after' => 'decimal:4', 'metadata' => 'array'];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
