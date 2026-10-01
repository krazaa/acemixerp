<?php

namespace App\Data;

final readonly class AgingBucket
{
    public function __construct(
        public string $label,
        public int $minDays,
        public ?int $maxDays,
    ) {}

    /** @return AgingBucket[] */
    public static function standard(): array
    {
        return [
            new self('Current', -PHP_INT_MAX, 0),
            new self('1–30', 1, 30),
            new self('31–60', 31, 60),
            new self('61–90', 61, 90),
            new self('90+', 91, null),
        ];
    }

    public function contains(int $daysOverdue): bool
    {
        if ($daysOverdue < $this->minDays) {
            return false;
        }
        if ($this->maxDays !== null && $daysOverdue > $this->maxDays) {
            return false;
        }

        return true;
    }
}
