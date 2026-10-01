<?php

declare(strict_types=1);

namespace App\Contracts;

interface AuditLogger
{
    public function log(string $event, string $description, array $properties = [], ?object $subject = null): void;
}
