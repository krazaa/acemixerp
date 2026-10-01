<?php

declare(strict_types=1);

namespace Modules\Procurement\Data;

use Carbon\Carbon;

final readonly class GoodsReceiptLineData
{
    public function __construct(
        public int $purchaseOrderLineId,
        public int $itemId,
        public string $receivedQuantity,
        public string $acceptedQuantity,
        public string $rejectedQuantity = '0.0000',
        public ?string $batchNumber = null,
        public ?string $expiry = null,
        public ?string $rejectionReason = null,
        public ?string $notes = null,
        public int $position = 0,
        public ?int $brandId = null,
        public ?int $originId = null,
        public ?string $manufacturingDate = null,
    ) {}

    /** @param array<string, mixed> $row */
    public static function fromArray(array $row, int $position): self
    {
        return new self(
            purchaseOrderLineId: (int) $row['purchase_order_line_id'],
            itemId: (int) $row['item_id'],
            receivedQuantity: self::n($row['received_quantity'] ?? 0),
            acceptedQuantity: self::n($row['accepted_quantity'] ?? 0),
            rejectedQuantity: self::n($row['rejected_quantity'] ?? 0),
            batchNumber: filled($row['batch_number'] ?? null) ? trim((string) $row['batch_number']) : null,
            expiry: filled($row['expiry_date'] ?? null) ? Carbon::parse($row['expiry_date'])->format('Y-m-d') : null,
            manufacturingDate: filled($row['manufacturing_date'] ?? null) ? Carbon::parse($row['manufacturing_date'])->format('Y-m-d') : null,
            rejectionReason: $row['rejection_reason'] ?? null,
            notes: $row['notes'] ?? null,
            position: $position,
            brandId: filled($row['brand_id'] ?? null) ? (int) $row['brand_id'] : null,
            originId: filled($row['origin_id'] ?? null) ? (int) $row['origin_id'] : null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'purchase_order_line_id' => $this->purchaseOrderLineId,
            'item_id' => $this->itemId,
            'brand_id' => $this->brandId,
            'origin_id' => $this->originId,
            'received_quantity' => $this->receivedQuantity,
            'accepted_quantity' => $this->acceptedQuantity,
            'rejected_quantity' => $this->rejectedQuantity,
            'batch_number' => $this->batchNumber,
            'expiry_date' => $this->expiry,
            'manufacturing_date' => $this->manufacturingDate,
            'rejection_reason' => $this->rejectionReason,
            'notes' => $this->notes,
            'position' => $this->position,
        ];
    }

    private static function n(mixed $v): string
    {
        if ($v === null || $v === '') {
            return '0.0000';
        }

        return number_format((float) $v, 4, '.', '');
    }
}
