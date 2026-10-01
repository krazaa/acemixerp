<?php

declare(strict_types=1);

namespace Modules\Inventory\Data;

use Carbon\Carbon;
use Illuminate\Http\Request;

final readonly class StockAdjustmentData
{
    /** @param StockAdjustmentLineData[] $lines */
    public function __construct(
        public int $warehouseId,
        public \DateTimeInterface $adjustmentDate,
        public string $reason,
        public array $lines,
        public ?string $notes = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $raw = collect($request->input('lines', []))
            ->filter(fn ($row) => is_array($row) && ! empty($row['item_id']))
            ->values();

        $lines = $raw->map(fn ($row, $i) => StockAdjustmentLineData::fromArray($row, $i))->all();

        return new self(
            warehouseId: (int) $request->input('warehouse_id'),
            adjustmentDate: Carbon::parse($request->input('adjustment_date')),
            reason: (string) $request->input('reason'),
            lines: $lines,
            notes: $request->input('notes'),
        );
    }
}
