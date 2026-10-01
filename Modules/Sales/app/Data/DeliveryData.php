<?php

namespace Modules\Sales\Data;

use Carbon\Carbon;
use Illuminate\Http\Request;

final readonly class DeliveryData
{
    /** @param DeliveryLineData[] $lines */
    public function __construct(
        public int $salesOrderId,
        public \DateTimeInterface $deliveryDate,
        public array $lines,
        public ?\DateTimeInterface $expectedDate = null,
        public ?string $reference = null,
        public ?string $carrier = null,
        public ?int $vendorId = null,
        public ?string $trackingNumber = null,
        public ?string $shippingAddress = null,
        public ?string $notes = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $raw = collect($request->input('lines', []))
            ->filter(fn ($row) => is_array($row) && ! empty($row['sales_order_line_id']))
            ->values();

        $lines = $raw->map(fn ($row, $i) => DeliveryLineData::fromArray($row, $i))->all();

        return new self(
            salesOrderId: (int) $request->input('sales_order_id'),
            deliveryDate: Carbon::parse($request->input('delivery_date', now())),
            lines: $lines,
            expectedDate: $request->date('expected_date'),
            reference: $request->input('reference'),
            carrier: $request->input('carrier'),
            vendorId: $request->integer('vendor_id') ?: null,
            trackingNumber: $request->input('tracking_number'),
            shippingAddress: $request->input('shipping_address'),
            notes: $request->input('notes'),
        );
    }
}
