<?php

namespace App\Contracts;

use App\Exceptions\ConcurrencyException;

interface SequenceGenerator
{
    /**
     * Generate the next document number atomically.
     *
     * @throws ConcurrencyException
     */
    public function next(string $key, ?int $year = null): string;

    public function peek(string $key, ?int $year = null): string;

    public function register(string $key, string $prefix, int $padding = 6, bool $resetYearly = true): void;
}
