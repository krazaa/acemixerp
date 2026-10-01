<?php

declare(strict_types=1);

namespace Modules\Procurement\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Procurement\Enums\QuotationStatus;
use Modules\Procurement\Enums\RfqStatus;
use Modules\Procurement\Enums\RfqVendorStatus;
use Modules\Procurement\Exceptions\RfqException;
use Modules\Procurement\Models\RequestForQuotation;
use Modules\Procurement\Models\RfqLine;
use Modules\Procurement\Models\VendorQuotation;
use Modules\Procurement\Models\VendorQuotationLine;

final class AwardRfqLines
{
    /** @param array<int|string, int|string> $selections RFQ line ID => quotation line ID */
    public function execute(RequestForQuotation $rfq, array $selections, int $userId): RequestForQuotation
    {
        return DB::transaction(function () use ($rfq, $selections, $userId): RequestForQuotation {
            $locked = RequestForQuotation::query()->lockForUpdate()->findOrFail($rfq->id);
            if (! $locked->status->canAward()) {
                throw RfqException::invalidTransition($locked, 'award');
            }
            $lines = $locked->lines()->lockForUpdate()->get();
            if ($lines->isEmpty() || count($selections) !== $lines->count()
                || array_diff(array_keys($selections), $lines->modelKeys()) !== []) {
                throw new RfqException('Select one vendor quotation for every RFQ item.');
            }
            $quotations = $locked->quotations()->lockForUpdate()->get()->keyBy('id');
            $quotes = VendorQuotationLine::query()->whereIn('vendor_quotation_id', $quotations->keys())
                ->whereIn('id', array_values($selections))->lockForUpdate()->get()->keyBy('id');
            $invited = $locked->vendors()->pluck('vendor_id')->all();
            $winningIds = [];
            foreach ($lines as $line) {
                $quote = $quotes->get($selections[$line->id] ?? null);
                $quotation = $quote ? $quotations->get($quote->vendor_quotation_id) : null;
                if (! $quote || ! $quotation || $quotation->status !== QuotationStatus::Submitted
                    || ! in_array($quotation->vendor_id, $invited, true)
                    || self::ineligibleReason($locked, $line, $quotation, $quote) !== null) {
                    throw new RfqException('Each selection must be a valid submitted quote for the same RFQ item, quantity, and currency. Expired quotes cannot be awarded.');
                }
                $line->forceFill(['awarded_quotation_line_id' => $quote->id])->save();
                $winningIds[] = $quotation->id;
            }
            $winningIds = array_values(array_unique($winningIds));
            $winningVendorIds = $quotations->only($winningIds)->pluck('vendor_id')->all();
            $locked->quotations()->whereIn('id', $winningIds)->update(['status' => QuotationStatus::Awarded]);
            $locked->quotations()->whereNotIn('id', $winningIds)->where('status', QuotationStatus::Submitted)->update(['status' => QuotationStatus::Rejected]);
            $locked->vendors()->whereIn('vendor_id', $winningVendorIds)->update(['status' => RfqVendorStatus::Awarded]);
            $locked->vendors()->whereNotIn('vendor_id', $winningVendorIds)->where('status', RfqVendorStatus::Submitted)->update(['status' => RfqVendorStatus::Rejected]);
            $locked->forceFill([
                'status' => RfqStatus::Awarded,
                'awarded_at' => now(), 'awarded_by' => $userId, 'updated_by' => $userId,
                'awarded_quotation_id' => count($winningIds) === 1 ? $winningIds[0] : null,
            ])->save();

            return $locked->fresh();
        });
    }

    public static function ineligibleReason(RequestForQuotation $rfq, RfqLine $line, VendorQuotation $quotation, VendorQuotationLine $quote): ?string
    {
        if ($quote->rfq_line_id !== $line->id || $quote->item_id !== $line->item_id) {
            return 'Different item';
        }
        if ($quotation->currency_code !== $rfq->currency_code) {
            return 'Different currency';
        }
        if (bccomp((string) $quote->quantity, (string) $line->quantity, 4) !== 0) {
            return 'Different quantity';
        }
        if (bccomp((string) $quote->unit_price, '0', 4) < 0) {
            return 'Invalid rate';
        }
        if ($quotation->valid_until && $quotation->valid_until->toDateString() < now()->toDateString()) {
            return 'Expired quotation';
        }

        return null;
    }
}
