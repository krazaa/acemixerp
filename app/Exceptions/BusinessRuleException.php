<?php

declare(strict_types=1);

namespace App\Exceptions;

final class BusinessRuleException extends DomainException
{
    public static function make(string $message): self
    {
        return new self($message);
    }

    public function httpStatus(): int
    {
        return 422;
    }
}
