<?php

declare(strict_types=1);

namespace Modules\Manufacturing\Data;

use Illuminate\Http\Request;

final readonly class BomData
{
    /** @param BomLineData[] $lines */
    public function __construct(
        public string $code,
        public string $name,
        public int $revision,
        public int $productId,
        public string $outputQuantity,
        public ?int $outputUnitId,
        public array $lines,
        public string $labourCost = '0.0000',
        public string $overheadCost = '0.0000',
        public ?string $notes = null,
        public string $status = 'draft',
    ) {}

    public static function fromRequest(Request $request): self
    {
        $rawLines = collect($request->input('lines', []))
            ->filter(fn ($row) => is_array($row) && ! empty($row['component_id']))
            ->values();

        $lines = $rawLines->map(fn ($row, $i) => BomLineData::fromArray($row, $i))->all();

        return new self(
            code: strtoupper((string) $request->input('code')),
            name: (string) $request->input('name'),
            revision: (int) $request->input('revision', 1),
            productId: (int) $request->input('product_id'),
            outputQuantity: number_format((float) $request->input('output_quantity', 1), 4, '.', ''),
            outputUnitId: $request->integer('output_unit_id') ?: null,
            lines: $lines,
            labourCost: number_format((float) $request->input('labour_cost', 0), 4, '.', ''),
            overheadCost: number_format((float) $request->input('overhead_cost', 0), 4, '.', ''),
            notes: $request->input('notes'),
            status: (string) ($request->input('status') ?: 'draft'),
        );
    }
}
