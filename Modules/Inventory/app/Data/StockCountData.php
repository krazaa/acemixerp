<?php

declare(strict_types=1);

namespace Modules\Inventory\Data;

use Carbon\Carbon;
use Illuminate\Http\Request;

final readonly class StockCountData
{
    public function __construct(
        public int $warehouseId,
        public \DateTimeInterface $countDate,
        public ?string $scope = null,
        public ?string $notes = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            warehouseId: (int) $request->input('warehouse_id'),
            countDate: Carbon::parse($request->input('count_date')),
            scope: $request->input('scope'),
            notes: $request->input('notes'),
        );
    }
}
