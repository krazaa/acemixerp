<?php

namespace App\Models;

use App\Enums\PaymentTermType;
use App\Enums\RecordStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PaymentTerm extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'code', 'name', 'type', 'days', 'day_of_month',
        'discount_percent', 'discount_days',
        'is_default', 'description', 'status',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => PaymentTermType::class,
            'status' => RecordStatus::class,
            'days' => 'integer',
            'day_of_month' => 'integer',
            'discount_percent' => 'decimal:4',
            'discount_days' => 'integer',
            'is_default' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('payment_term');
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

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (! $term) {
            return $q;
        }

        return $q->where(fn ($q) => $q
            ->where('name', 'like', "%{$term}%")
            ->orWhere('code', 'like', "%{$term}%"));
    }

    /**
     * Compute the due date for a document issued on $issueDate.
     * Deterministic and testable — no side effects.
     */
    public function dueDate(CarbonInterface $issueDate): CarbonInterface
    {
        return match ($this->type) {
            PaymentTermType::DueOnReceipt => $issueDate->copy(),

            PaymentTermType::Net => $issueDate->copy()->addDays($this->days),

            PaymentTermType::EndOfMonth => $issueDate->copy()
                ->endOfMonth()
                ->addDays($this->days),

            PaymentTermType::DayOfMonth => $this->nextDayOfMonth($issueDate),
        };
    }

    public function discountDate(CarbonInterface $issueDate): ?CarbonInterface
    {
        if (! $this->discount_days) {
            return null;
        }

        return $issueDate->copy()->addDays($this->discount_days);
    }

    private function nextDayOfMonth(CarbonInterface $issueDate): CarbonInterface
    {
        $day = $this->day_of_month ?? 1;
        $candidate = $issueDate->copy()->day(min($day, $issueDate->daysInMonth));
        if ($candidate->isBefore($issueDate) || $candidate->equalTo($issueDate)) {
            $candidate = $candidate->copy()->addMonthNoOverflow();
            $candidate = $candidate->day(min($day, $candidate->daysInMonth));
        }

        return $candidate;
    }

    public static function default(): ?self
    {
        return static::query()
            ->where('is_default', true)
            ->where('status', RecordStatus::Active->value)
            ->first();
    }
}
