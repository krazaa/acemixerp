<?php

declare(strict_types=1);

namespace Modules\Manufacturing\Data;

use Illuminate\Http\Request;

final readonly class ProductionOrderData
{
    public function __construct(
        public int $bomId,
        public int $productId,
        public string $plannedQuantity,
        public int $sourceWarehouseId,
        public ?\DateTimeInterface $scheduledStartDate = null,
        public ?\DateTimeInterface $scheduledEndDate = null,
        public ?int $salesOrderId = null,
        public string $type = 'standard',
        public ?string $notes = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            bomId: (int) $request->input('bill_of_materials_id'),
            productId: (int) $request->input('product_id'),
            plannedQuantity: number_format((float) $request->input('planned_quantity'), 4, '.', ''),
            sourceWarehouseId: (int) $request->input('source_warehouse_id'),
            scheduledStartDate: $request->date('scheduled_start_date'),
            scheduledEndDate: $request->date('scheduled_end_date'),
            salesOrderId: $request->integer('sales_order_id') ?: null,
            type: (string) ($request->input('type') ?: 'standard'),
            notes: $request->input('notes'),
        );
    }
}
