<?php

namespace Modules\Procurement\Data;

use Carbon\Carbon;
use Illuminate\Http\Request;

final readonly class PurchaseRequisitionData
{
    /** @param PurchaseRequisitionLineData[] $lines */
    public function __construct(
        public \DateTimeInterface $requestedDate,
        public string $purpose,
        public array $lines,
        public ?\DateTimeInterface $requiredDate = null,
        public ?int $departmentId = null,
        public ?int $costCenterId = null,
        public ?int $requestedBy = null,
        public ?string $notes = null,
        public ?int $warehouseId = null,
    ) {}

    public static function fromRequest(Request $request, ?int $requestedBy = null): self
    {
        $rawLines = collect($request->input('lines', []))
            ->filter(fn ($row) => is_array($row) && ! empty($row['item_id']))
            ->values();

        $lines = $rawLines
            ->map(fn ($row, $i) => PurchaseRequisitionLineData::fromArray($row, $i))
            ->all();

        return new self(
            requestedDate: Carbon::parse($request->input('requested_date')),
            purpose: (string) $request->input('purpose'),
            lines: $lines,
            requiredDate: $request->date('required_date'),
            departmentId: $request->integer('department_id') ?: null,
            costCenterId: $request->integer('cost_center_id') ?: null,
            requestedBy: $requestedBy ?? ($request->integer('requested_by') ?: null),
            notes: $request->input('notes'),
            warehouseId: $request->integer('warehouse_id') ?: null,
        );
    }

    public function totalEstimated(): string
    {
        return array_reduce(
            $this->lines,
            fn (string $carry, PurchaseRequisitionLineData $line): string => bcadd($carry, $line->lineTotal(), 4),
            '0.0000',
        );
    }
}
