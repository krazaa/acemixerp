<?php

declare(strict_types=1);

namespace Modules\Procurement\Data;

use Carbon\Carbon;
use Illuminate\Http\Request;

final readonly class GoodsReceiptData
{
    /** @param GoodsReceiptLineData[] $lines */
    public function __construct(
        public int $purchaseOrderId,
        public \DateTimeInterface $receivedDate,
        public array $lines,
        public ?string $supplierDeliveryNote = null,
        public ?string $carrier = null,
        public ?string $notes = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $raw = collect($request->input('lines', []))
            ->filter(fn ($row) => is_array($row) && ! empty($row['purchase_order_line_id']))
            ->values();

        $lines = $raw->map(fn ($row, $i) => GoodsReceiptLineData::fromArray($row, $i))->all();

        return new self(
            purchaseOrderId: (int) $request->input('purchase_order_id'),
            receivedDate: Carbon::parse($request->input('received_date')),
            lines: $lines,
            supplierDeliveryNote: $request->input('supplier_delivery_note'),
            carrier: $request->input('carrier'),
            notes: $request->input('notes'),
        );
    }
}
