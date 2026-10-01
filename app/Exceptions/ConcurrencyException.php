<?php

namespace App\Exceptions;

final class ConcurrencyException extends DomainException
{
    public function httpStatus(): int
    {
        return 409;
    }

    public function userMessage(): string
    {
        return 'A concurrent operation conflicted. Please retry.';
    }
}
