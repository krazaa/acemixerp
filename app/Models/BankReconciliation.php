<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReconciliationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class BankReconciliation extends Model
{
    use LogsActivity;

    protected $fillable = [
        'bank_account_id', 'statement_date',
        'statement_opening_balance', 'statement_closing_balance',
        'status', 'completed_at', 'completed_by', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReconciliationStatus::class,
            'statement_date' => 'date',
            'statement_opening_balance' => 'decimal:4',
            'statement_closing_balance' => 'decimal:4',
            'completed_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('bank_reconciliation');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function statementLines(): HasMany
    {
        return $this->hasMany(BankStatementLine::class);
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
