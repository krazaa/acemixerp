<?php

namespace Modules\Procurement\Services;

use Illuminate\Support\Facades\DB;
use Modules\Procurement\Actions\AwardRfqLines;
use Modules\Procurement\Contracts\VendorQuotationManager;
use Modules\Procurement\Data\VendorQuotationData;
use Modules\Procurement\Enums\QuotationStatus;
use Modules\Procurement\Enums\RfqStatus;
use Modules\Procurement\Enums\RfqVendorStatus;
use Modules\Procurement\Exceptions\RfqException;
use Modules\Procurement\Models\RequestForQuotation;
use Modules\Procurement\Models\RfqLine;
use Modules\Procurement\Models\VendorQuotation;

final class VendorQuotationService implements VendorQuotationManager
{
    public function __construct(private readonly AwardRfqLines $lineAwards) {}

    public function record(RequestForQuotation $rfq, VendorQuotationData $data, int $userId): VendorQuotation
    {
        if (! $rfq->status->canReceive()) {
            throw new RfqException("RFQ {$rfq->number} is not accepting quotations.");
        }

        // Vendor must have been invited
        $invited = $rfq->vendors()->where('vendor_id', $data->vendorId)->exists();
        if (! $invited) {
            throw new RfqException('Vendor is not invited to this RFQ.');
        }

        return DB::transaction(function () use ($rfq, $data) {
            $rfq = RequestForQuotation::query()->lockForUpdate()->findOrFail($rfq->id);
            if (! $rfq->status->canReceive()) {
                throw new RfqException('This RFQ is no longer accepting quotations.');
            }
            $quotation = VendorQuotation::query()->updateOrCreate(
                [
                    'request_for_quotation_id' => $rfq->id,
                    'vendor_id' => $data->vendorId,
                ],
                [
                    'reference' => $data->reference,
                    'quoted_at' => $data->quotedAt,
                    'valid_until' => $data->validUntil,
                    'currency_code' => strtoupper($data->currencyCode),
                    'lead_time_days' => $data->leadTimeDays,
                    'notes' => $data->notes,
                    'status' => QuotationStatus::Draft,
                ],
            );

            $quotation->lines()->delete();

            foreach ($data->lines as $line) {
                $quotation->lines()->create($line->toArray());
            }

            $quotation->recalculateTotals();

            return $quotation->fresh(['lines', 'vendor']);
        });
    }

    public function submit(VendorQuotation $quotation, int $userId): VendorQuotation
    {
        if ($quotation->status !== QuotationStatus::Draft) {
            throw new RfqException("Quotation is {$quotation->status->label()} and cannot be submitted.");
        }

        if ($quotation->lines()->count() === 0) {
            throw new RfqException('A quotation must have at least one line.');
        }

        DB::transaction(function () use ($quotation, $userId) {
            $rfq = RequestForQuotation::query()->lockForUpdate()->findOrFail($quotation->request_for_quotation_id);
            $quotation = VendorQuotation::query()->lockForUpdate()->findOrFail($quotation->id);
            if (! $rfq->status->canReceive() || $quotation->status !== QuotationStatus::Draft) {
                throw new RfqException('This quotation can no longer be submitted.');
            }
            $quotation->forceFill([
                'status' => QuotationStatus::Submitted,
                'submitted_at' => now(),
                'submitted_by' => $userId,
            ])->save();

            // Update the RFQ-vendor pivot
            $quotation->rfq->vendors()
                ->where('vendor_id', $quotation->vendor_id)
                ->update([
                    'status' => RfqVendorStatus::Submitted,
                    'submitted_at' => now(),
                ]);

            // If the RFQ is still in Issued, move it to Receiving
            if ($quotation->rfq->status === RfqStatus::Issued) {
                $quotation->rfq->forceFill(['status' => RfqStatus::Receiving])->save();
            }
        });

        return $quotation->fresh();
    }

    public function award(RequestForQuotation $rfq, VendorQuotation $quotation, int $userId): VendorQuotation
    {
        if ($quotation->request_for_quotation_id !== $rfq->id) {
            throw new RfqException('Quotation does not belong to this RFQ.');
        }
        $this->lineAwards->execute($rfq, $quotation->lines()->pluck('id', 'rfq_line_id')->all(), $userId);

        return $quotation->fresh();
    }

    /**
     * Comparison matrix: rows = RFQ lines, cols = vendors, cells = unit price + line total.
     *
     * @return array{
     *   rfq: RequestForQuotation,
     *   vendors: array<int, array{id:int, name:string}>,
     *   lines: array<int, array{
     *     line: RfqLine,
     *     cells: array<int, array{unit_price:?string, line_total:?string, lead_time_days:?int, quotation_id:int, quotation_line_id:?int, currency_code:string, quantity:?string, ineligible_reason:?string, tax_rate:?string, tax_amount:?string, wht_tax_rate:?string, wht_tax_amount:?string}>,
     *     best_unit_price: ?string,
     *   }>,
     * }
     */
    public function comparison(RequestForQuotation $rfq): array
    {
        $rfq->load(['lines.brand', 'lines.item', 'lines.unit', 'lines.awardedQuotationLine.quotation.vendor', 'quotations.vendor', 'quotations.lines', 'purchaseOrders.vendor', 'awardedQuotation.vendor', 'vendors']);

        $submittedQuotations = $rfq->quotations
            ->whereIn('status', [QuotationStatus::Submitted, QuotationStatus::Awarded]);

        $vendors = $submittedQuotations
            ->map(fn ($q) => ['id' => $q->vendor_id, 'name' => $q->vendor?->name ?? 'Unavailable vendor'])
            ->unique('id')
            ->values()
            ->all();

        $lines = $rfq->lines->map(function ($line) use ($submittedQuotations, $rfq) {
            $cells = [];
            $bestPrice = null;

            foreach ($submittedQuotations as $q) {
                $qLine = $q->lines->firstWhere('rfq_line_id', $line->id);
                $unitPrice = $qLine?->unit_price;
                $lineTotal = $qLine?->line_total;

                $reason = $qLine ? AwardRfqLines::ineligibleReason($rfq, $line, $q, $qLine) : 'No quote';
                if (! $rfq->vendors->contains('vendor_id', $q->vendor_id)) {
                    $reason = 'Vendor not invited';
                }
                if ($unitPrice !== null && $reason === null) {
                    $bestPrice = $bestPrice === null
                        ? (string) $unitPrice
                        : (bccomp((string) $unitPrice, $bestPrice, 4) < 0 ? (string) $unitPrice : $bestPrice);
                }

                $cells[$q->vendor_id] = [
                    'unit_price' => $unitPrice !== null ? (string) $unitPrice : null,
                    'line_total' => $lineTotal !== null ? (string) $lineTotal : null,
                    'lead_time_days' => $qLine?->lead_time_days,
                    'quotation_id' => $q->id,
                    'quotation_line_id' => $qLine?->id,
                    'currency_code' => $q->currency_code,
                    'quantity' => $qLine?->quantity,
                    'ineligible_reason' => $reason,
                    'tax_rate' => $qLine?->tax_rate,
                    'tax_amount' => $qLine?->tax_amount,
                    'wht_tax_rate' => $qLine?->wht_tax_rate,
                    'wht_tax_amount' => $qLine?->wht_tax_amount,
                ];
            }

            return [
                'line' => $line,
                'cells' => $cells,
                'best_unit_price' => $bestPrice,
            ];
        })->all();

        return [
            'rfq' => $rfq,
            'vendors' => $vendors,
            'lines' => $lines,
        ];
    }
}
