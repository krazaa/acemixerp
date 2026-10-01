<?php

declare(strict_types=1);

namespace Modules\Manufacturing\Data;

final readonly class BomLineData
{
    public function __construct(
        public int $componentId,
        public ?int $batchId,
        public ?int $unitId,
        public string $quantity,
        public string $scrapPercent = '0.000000',
        public ?string $notes = null,
        public int $position = 0,
    ) {}

    /** @param array<string, mixed> $row */
    public static function fromArray(array $row, int $position): self
    {
        return new self(
            componentId: (int) $row['component_id'],
            batchId: ! empty($row['batch_id']) ? (int) $row['batch_id'] : null,
            unitId: ! empty($row['unit_id']) ? (int) $row['unit_id'] : null,
            quantity: number_format((float) ($row['quantity'] ?? 0), 4, '.', ''),
            scrapPercent: number_format((float) ($row['scrap_percent'] ?? 0), 6, '.', ''),
            notes: $row['notes'] ?? null,
            position: $position,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'component_id' => $this->componentId,
            'batch_id' => $this->batchId,
            'unit_id' => $this->unitId,
            'quantity' => $this->quantity,
            'scrap_percent' => $this->scrapPercent,
            'notes' => $this->notes,
            'position' => $this->position,
        ];
    }
}
