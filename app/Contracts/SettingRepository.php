<?php

declare(strict_types=1);

namespace App\Contracts;

interface SettingRepository
{
    public function get(string $key, mixed $default = null, ?string $group = 'general'): mixed;

    public function set(string $key, mixed $value, string $type = 'string', string $group = 'general'): void;

    public function forget(string $key, ?string $group = 'general'): void;

    public function flush(): void;
}
