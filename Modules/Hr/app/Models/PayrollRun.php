<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use App\Models\JournalEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends Model
{
    protected $fillable = ['number', 'period_start', 'period_end', 'status', 'gross_total', 'deduction_total', 'net_total', 'journal_entry_id', 'approved_by', 'approved_at', 'finalized_by', 'finalized_at'];

    protected function casts(): array
    {
        return ['period_start' => 'date', 'period_end' => 'date', 'gross_total' => 'decimal:4', 'deduction_total' => 'decimal:4', 'net_total' => 'decimal:4', 'approved_at' => 'datetime', 'finalized_at' => 'datetime'];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PayrollLine::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
