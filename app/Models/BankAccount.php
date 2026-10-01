<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BankAccountType;
use App\Enums\RecordStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class BankAccount extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'bank_id', 'gl_account_id', 'code', 'name',
        'account_number', 'iban', 'swift_code',
        'account_type', 'currency_code',
        'opening_balance', 'opening_balance_date',
        'is_default', 'status',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'account_type' => BankAccountType::class,
            'status' => RecordStatus::class,
            'opening_balance' => 'decimal:4',
            'opening_balance_date' => 'date',
            'is_default' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('bank_account');
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    public function glAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'gl_account_id');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', RecordStatus::Active->value);
    }

    public static function default(): ?self
    {
        return static::query()->where('is_default', true)->where('status', 'active')->first();
    }
}
