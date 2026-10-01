<?php

namespace App\Models;

use App\Enums\RecordStatus;
use App\Models\Concerns\HasAddresses;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Bank extends Model
{
    use HasAddresses;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'code', 'name', 'short_name',
        'account_number', 'iban', 'bank_contacts',
        'email', 'phone',
        'notes', 'status',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return ['status' => RecordStatus::class, 'bank_contacts' => 'array'];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('bank');
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
            ->orWhere('code', 'like', "%{$term}%")
            ->orWhere('short_name', 'like', "%{$term}%")
            ->orWhere('account_number', 'like', "%{$term}%")
            ->orWhere('iban', 'like', "%{$term}%"));
    }

    /**
     * Normalize the contacts payload — strips unknown keys, trims strings,
     * drops blank rows. Called by the service before persisting.
     *
     * @param  array<int, array<string, mixed>>  $contacts
     * @return array<int, array<string, string>>
     */
    public static function normalizeContacts(array $contacts): array
    {
        $allowed = ['name', 'role', 'email', 'phone', 'notes'];

        return collect($contacts)
            ->filter(fn ($row) => is_array($row) && ! empty(array_filter(
                [$row['name'] ?? null, $row['email'] ?? null, $row['phone'] ?? null],
                fn ($v) => is_string($v) && trim($v) !== '',
            )))
            ->map(function (array $row) use ($allowed) {
                $clean = [];
                foreach ($allowed as $key) {
                    $value = $row[$key] ?? null;
                    $clean[$key] = is_string($value) ? trim($value) : null;
                }

                return $clean;
            })
            ->values()
            ->all();
    }

    /** @return array<int, array<string, string>> */
    public function getBankContacts(): array
    {
        return is_array($this->bank_contacts) ? $this->bank_contacts : [];
    }

    public function primaryContact(): ?array
    {
        return $this->getBankContacts()[0] ?? null;
    }
}
