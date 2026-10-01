<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\JournalEntryStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class JournalEntry extends Model
{
    use LogsActivity;

    protected $fillable = [
        'number', 'entry_date', 'reference', 'description',
        'period_id', 'financial_year_id', 'currency_code', 'status',
        'reversed_by_id', 'reverses_id',
        'submitted_at', 'approved_at', 'posted_at',
        'created_by', 'submitted_by', 'approved_by', 'posted_by',
        'total_debit', 'total_credit',
        'source_type', 'source_id', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => JournalEntryStatus::class,
            'entry_date' => 'date',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'posted_at' => 'datetime',
            'total_debit' => 'decimal:4',
            'total_credit' => 'decimal:4',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'total_debit', 'total_credit', 'posted_at', 'reference'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('journal_entry');
    }

    // ─── Relations ───────────────────────────────────────────────────
    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class)->orderBy('position')->orderBy('id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class, 'period_id');
    }

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversed_by_id');
    }

    public function reverses(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reverses_id');
    }

    // ─── Scopes ──────────────────────────────────────────────────────
    public function scopePosted(Builder $q): Builder
    {
        return $q->where('status', JournalEntryStatus::Posted->value);
    }

    public function scopeDraft(Builder $q): Builder
    {
        return $q->where('status', JournalEntryStatus::Draft->value);
    }

    public function scopeOnDate(Builder $q, \DateTimeInterface $date): Builder
    {
        return $q->whereDate('entry_date', $date);
    }

    public function scopeBetweenDates(Builder $q, \DateTimeInterface $from, \DateTimeInterface $to): Builder
    {
        return $q->whereBetween('entry_date', [$from->format('Y-m-d'), $to->format('Y-m-d')]);
    }

    public function scopeForAccount(Builder $q, int $accountId): Builder
    {
        return $q->whereHas('lines', fn ($q) => $q->where('account_id', $accountId));
    }

    // ─── Helpers ─────────────────────────────────────────────────────
    public function isBalanced(): bool
    {
        return bccomp((string) $this->total_debit, (string) $this->total_credit, 4) === 0;
    }

    public function isEditable(): bool
    {
        return $this->status->isEditable();
    }

    public function isPosted(): bool
    {
        return $this->status === JournalEntryStatus::Posted;
    }
}
