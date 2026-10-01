<?php

namespace App\Models;

use App\Models\Concerns\HasAddresses;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Organization extends Model
{
    use HasAddresses;
    use LogsActivity;

    public const SINGLETON_ID = 1;

    /** Cache key holds an int primary key — never a serialized model. */
    private const CACHE_KEY = 'organization.singleton_id';

    /** Legacy key that may hold a poisoned payload from earlier code. */
    private const LEGACY_CACHE_KEY = 'organization.current';

    protected $fillable = [
        'name', 'legal_name', 'tax_number', 'registration_number',
        'email', 'phone', 'website', 'logo_path',
        'country',
        'currency_code', 'currency_symbol', 'currency_decimals',
        'timezone', 'date_format', 'fiscal_year_start_month',
        'inventory_valuation_method', 'allow_negative_stock',
        'require_approval_for_journal', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'allow_negative_stock' => 'boolean',
            'require_approval_for_journal' => 'boolean',
            'currency_decimals' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('organization');
    }

    /**
     * Resolve the singleton organization.
     *
     * Caches ONLY the primary key. The model is always fetched fresh, which:
     *   - survives schema/model/cast changes between deploys
     *   - is immune to `__PHP_Incomplete_Class` from stale serialized models
     *   - still avoids a per-request DB hit for the id lookup itself
     *
     * @throws ModelNotFoundException
     */
    public static function current(): self
    {
        $id = Cache::rememberForever(
            self::CACHE_KEY,
            static fn (): int => self::query()->value('id') ?? self::SINGLETON_ID,
        );

        // Guard: if a corrupted / legacy value sneaks in, self-heal once.
        if (! is_int($id) || $id < 1) {
            Cache::forget(self::CACHE_KEY);
            $id = self::SINGLETON_ID;
        }

        /** @var self $model */
        $model = self::query()->findOrFail($id);

        return $model;
    }

    /**
     * View-safe variant. Returns null if the singleton has not been seeded.
     * Use in Blade/layouts/composers where a missing row must not break rendering.
     * Business logic should call {@see self::current()} to fail loudly.
     */
    public static function currentOrNull(): ?self
    {
        try {
            return self::current();
        } catch (ModelNotFoundException) {
            return null;
        }
    }

    /**
     * Invalidate the singleton cache. Called by the updater service and by
     * the installer after the organization row is created.
     */
    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::LEGACY_CACHE_KEY); // purge any old payload
    }

    /** Fields that cannot be changed once any financial activity exists. */
    public static function lockedAfterPosting(): array
    {
        return ['currency_code', 'currency_decimals', 'inventory_valuation_method'];
    }
}
