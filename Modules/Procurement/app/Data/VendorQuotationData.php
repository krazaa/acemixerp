<?php

namespace Modules\Procurement\Data;

use Carbon\Carbon;
use Illuminate\Http\Request;

final readonly class VendorQuotationData
{
    /** @param VendorQuotationLineData[] $lines */
    public function __construct(
        public int $vendorId,
        public \DateTimeInterface $quotedAt,
        public string $currencyCode,
        public array $lines,
        public ?string $reference = null,
        public ?\DateTimeInterface $validUntil = null,
        public ?int $leadTimeDays = null,
        public ?string $notes = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $raw = collect($request->input('lines', []))
            ->filter(fn ($row) => is_array($row) && ! empty($row['rfq_line_id']))
            ->values();

        $lines = $raw->map(fn ($row, $i) => VendorQuotationLineData::fromArray($row, $i))->all();

        return new self(
            vendorId: (int) $request->input('vendor_id'),
            quotedAt: Carbon::parse($request->input('quoted_at', now())),
            currencyCode: (string) ($request->input('currency_code') ?: 'USD'),
            lines: $lines,
            reference: $request->input('reference'),
            validUntil: $request->date('valid_until'),
            leadTimeDays: $request->integer('lead_time_days') ?: null,
            notes: $request->input('notes'),
        );
    }
}
