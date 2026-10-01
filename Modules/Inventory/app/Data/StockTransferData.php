<?php

declare(strict_types=1);

namespace Modules\Inventory\Data;

use Carbon\Carbon;
use Illuminate\Http\Request;

final readonly class StockTransferData
{
    /** @param StockTransferLineData[] $lines */
    public function __construct(
        public int $fromWarehouseId,
        public int $toWarehouseId,
        public \DateTimeInterface $transferDate,
        public array $lines,
        public ?\DateTimeInterface $expectedArrivalDate = null,
        public ?string $notes = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $raw = collect($request->input('lines', []))
            ->filter(fn ($row) => is_array($row) && ! empty($row['item_id']))
            ->values();

        $lines = $raw->map(fn ($row, $i) => StockTransferLineData::fromArray($row, $i))->all();

        return new self(
            fromWarehouseId: (int) $request->input('from_warehouse_id'),
            toWarehouseId: (int) $request->input('to_warehouse_id'),
            transferDate: Carbon::parse($request->input('transfer_date')),
            lines: $lines,
            expectedArrivalDate: $request->date('expected_arrival_date'),
            notes: $request->input('notes'),
        );
    }
}
