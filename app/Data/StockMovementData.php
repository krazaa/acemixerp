<?php

namespace App\Data;

use App\Enums\StockMovementType;

final readonly class StockMovementData
{
    /**
     * @param  string  $quantity  Signed quantity (positive = in, negative = out)
     */
    public function __construct(
        public int $itemId,
        public int $warehouseId,
        public string $quantity,
        public StockMovementType $type,
        public \DateTimeInterface $occurredAt,
        public ?int $batchId = null,
        public ?string $serialNumber = null,
        public ?string $reference = null,
        public ?string $notes = null,
        public ?string $sourceType = null,
        public ?int $sourceId = null,
        public ?string $unitCost = null,
    ) {}

    public function isInbound(): bool
    {
        return bccomp($this->quantity, '0', 4) > 0;
    }

    public function isOutbound(): bool
    {
        return bccomp($this->quantity, '0', 4) < 0;
    }

    public function absoluteQuantity(): string
    {
        return bccomp($this->quantity, '0', 4) < 0
            ? bcmul($this->quantity, '-1', 4)
            : $this->quantity;
    }
}
