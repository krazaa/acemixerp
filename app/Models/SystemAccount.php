<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SystemAccountRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemAccount extends Model
{
    protected $fillable = ['role', 'account_id', 'description'];

    protected function casts(): array
    {
        return ['role' => SystemAccountRole::class];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public static function resolve(SystemAccountRole $role): ?Account
    {
        $mapping = static::query()->where('role', $role->value)->first();

        return $mapping?->account;
    }

    /** @return array<string, int> role => account_id */
    public static function allMappings(): array
    {
        return static::query()
            ->get()
            ->mapWithKeys(fn ($row) => [$row->role->value => $row->account_id])
            ->all();
    }
}
