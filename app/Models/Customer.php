<?php

namespace App\Models;

use App\Enums\CustomerStatus;
use App\Models\Concerns\HasAddresses;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Customer extends Model
{
    use HasAddresses;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'code', 'name', 'c_person', 'legal_name', 'tax_number', 'registration_number',
        'status', 'category_id',
        'email', 'phone', 'cp_phone',
        'currency_code', 'credit_limit', 'credit_days',
        'payment_term_id', 'default_tax_rate_id', 'ar_account_id',
        'is_tax_exempt', 'notes',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => CustomerStatus::class,
            'credit_limit' => 'decimal:4',
            'credit_days' => 'integer',
            'is_tax_exempt' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('customer');
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
        return $q->where('status', CustomerStatus::Active->value);
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (! $term) {
            return $q;
        }

        return $q->where(fn ($q) => $q
            ->where('name', 'like', "%{$term}%")
            ->orWhere('code', 'like', "%{$term}%")
            ->orWhere('email', 'like', "%{$term}%")
            ->orWhere('tax_number', 'like', "%{$term}%"));
    }

    public function canTransact(): bool
    {
        return $this->status->canTransact();
    }

    public function hasCreditFor(float $amount): bool
    {
        if ((float) $this->credit_limit <= 0) {
            return true;
        }

        // Phase 5 will subtract actual outstanding AR balance.
        return $amount <= (float) $this->credit_limit;
    }
}
