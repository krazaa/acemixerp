<?php

namespace App\Data;

final readonly class SequenceRequestData
{
    public function __construct(
        public string $key,
        public ?int $year = null,
    ) {}
}
