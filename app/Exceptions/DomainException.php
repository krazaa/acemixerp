<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

abstract class DomainException extends RuntimeException
{
    /** Safe user-facing message (never leaks internals). */
    public function userMessage(): string
    {
        return $this->getMessage();
    }

    /** HTTP status for API responses. */
    public function httpStatus(): int
    {
        return 422;
    }
}
