<?php

namespace App\Models;

use App\Enums\VendorStatus;
use App\Models\Concerns\HasAddresses;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Vendor extends Model
{
    use HasAddresses;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'code', 'name', 'contact_person', 'legal_name', 'tax_number', 'registration_number',
        'status', 'category_id',
        'email', 'phone', 'website',
        'bank_name', 'bank_branch', 'bank_account_number', 'bank_iban',
        'credit_days',
        'payment_term_id', 'default_tax_rate_id', 'ap_account_id',
        'is_tax_exempt', 'notes',
        'created_by', 'updated_by',
    ];

    protected $hidden = ['bank_account_number'];

    protected function casts(): array
    {
        return [
            'status' => VendorStatus::class,
            'credit_days' => 'integer',
            'is_tax_exempt' => 'boolean',
            'bank_account_number' => 'encrypted',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('vendor');
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
        return $q->where('status', VendorStatus::Active->value);
    }

    public function scopeTransportProviders(Builder $q): Builder
    {
        return $q->where('status', VendorStatus::Active->value)
            ->where('vendor_type', 'transport');
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

    public function vendorType(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'category_id');
    }
}
