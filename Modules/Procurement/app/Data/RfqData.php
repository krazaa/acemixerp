<?php

namespace Modules\Procurement\Data;

use Carbon\Carbon;
use Illuminate\Http\Request;

final readonly class RfqData
{
    /** @param RfqLineData[] $lines  @param int[] $vendorIds  @param int[] $sourceRequisitionIds */
    public function __construct(
        public \DateTimeInterface $issueDate,
        public \DateTimeInterface $dueDate,
        public string $purpose,
        public string $currencyCode,
        public array $lines,
        public array $vendorIds,
        public ?int $departmentId = null,
        public ?int $costCenterId = null,
        public ?string $terms = null,
        public array $sourceRequisitionIds = [],
    ) {}

    public static function fromRequest(Request $request): self
    {
        $rawLines = collect($request->input('lines', []))
            ->filter(fn ($row) => is_array($row) && ! empty($row['item_id']))
            ->values();

        $lines = $rawLines->map(fn ($row, $i) => RfqLineData::fromArray($row, $i))->all();

        return new self(
            issueDate: Carbon::parse($request->input('issue_date')),
            dueDate: Carbon::parse($request->input('due_date')),
            purpose: (string) $request->input('purpose'),
            currencyCode: (string) ($request->input('currency_code') ?: 'PKR'),
            lines: $lines,
            vendorIds: array_map('intval', (array) $request->input('vendor_ids', [])),
            departmentId: $request->integer('department_id') ?: null,
            costCenterId: $request->integer('cost_center_id') ?: null,
            terms: $request->input('terms'),
            sourceRequisitionIds: array_map('intval', (array) $request->input('source_requisition_ids', [])),
        );
    }
}
