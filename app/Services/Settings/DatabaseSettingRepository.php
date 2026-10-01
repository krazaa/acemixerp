<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Contracts\SettingRepository;
use App\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

final class DatabaseSettingRepository implements SettingRepository
{
    private const CACHE_TTL = 3600;

    public function get(string $key, mixed $default = null, ?string $group = 'general'): mixed
    {
        $cacheKey = $this->cacheKey($group, $key);

        return cache()->remember($cacheKey, self::CACHE_TTL, function () use ($group, $key, $default) {
            $row = Setting::query()->where('group', $group)->where('key', $key)->first();
            if (! $row) {
                return $default;
            }

            return $this->decode($row);
        });
    }

    public function set(string $key, mixed $value, string $type = 'string', string $group = 'general'): void
    {
        $encoded = $this->encode($value, $type);

        Setting::query()->updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => $encoded['value'], 'type' => $type, 'is_encrypted' => $encoded['encrypted']],
        );

        cache()->forget($this->cacheKey($group, $key));
    }

    public function forget(string $key, ?string $group = 'general'): void
    {
        Setting::query()->where('group', $group)->where('key', $key)->delete();
        cache()->forget($this->cacheKey($group, $key));
    }

    public function flush(): void
    {
        Setting::query()->truncate();
        // Cache tags may not be available with all drivers — clear explicitly.
        foreach (Setting::query()->pluck('group')->unique() as $g) {
            cache()->forget("settings.{$g}");
        }
    }

    private function decode(Setting $row): mixed
    {
        $value = $row->value;
        if ($row->is_encrypted && $value !== null) {
            try {
                $value = Crypt::decryptString($value);
            } catch (DecryptException) {
                return null;
            }
        }

        return match ($row->type) {
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode((string) $value, true, 512, JSON_THROW_ON_ERROR),
            default => $value,
        };
    }

    /** @return array{value:?string, encrypted:bool} */
    private function encode(mixed $value, string $type): array
    {
        $encrypted = false;
        $encoded = match ($type) {
            'int' => (string) (int) $value,
            'float' => (string) (float) $value,
            'bool' => $value ? '1' : '0',
            'json' => json_encode($value, JSON_THROW_ON_ERROR),
            default => (string) $value,
        };

        return ['value' => $encoded, 'encrypted' => $encrypted];
    }

    private function cacheKey(?string $group, string $key): string
    {
        return "settings.{$group}.{$key}";
    }
}
