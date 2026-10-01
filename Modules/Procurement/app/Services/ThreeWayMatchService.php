<?php

declare(strict_types=1);

namespace Modules\Procurement\Services;

use Modules\Procurement\Enums\ThreeWayMatchResult;
use Modules\Procurement\Models\PurchaseOrderLine;
use Modules\Procurement\Models\SupplierInvoice;
use Modules\Procurement\Models\SupplierInvoiceLine;

final class ThreeWayMatchService
{
    /**
     * Per-line three-way match.
     * Returns a per-line breakdown plus overall status.
     *
     * @return array{
     *   status: 'matched'|'mismatch',
     *   lines: array<int, array{
     *     line_id: int,
     *     result: string,
     *     note: ?string,
     *   }>,
     * }
     */
    public function match(SupplierInvoice $invoice, float $priceTolerancePercent = 0.0): array
    {
        $invoice->loadMissing(['lines.item', 'lines.purchaseOrderLine', 'purchaseOrder.lines']);

        $results = [];
        $hasFailure = false;

        foreach ($invoice->lines as $line) {
            $result = $this->matchLine($line, $priceTolerancePercent);
            $results[] = [
                'line_id' => $line->id,
                'result' => $result->value,
                'note' => $result->label(),
            ];
            if ($result->isFailure()) {
                $hasFailure = true;
            }
        }

        return [
            'status' => $hasFailure ? 'mismatch' : 'matched',
            'lines' => $results,
        ];
    }

    /**
     * Rules, in order:
     *  1. Accepted receipt must exist (accepted_quantity > 0 on PO line via GRNs).
     *  2. Invoiced quantity across all posted invoices ≤ accepted quantity.
     *  3. Unit price ≤ PO unit price * (1 + tolerance/100).
     *  4. Tax rate equals PO tax rate.
     */
    private function matchLine(SupplierInvoiceLine $line, float $priceTolerancePercent): ThreeWayMatchResult
    {
        $poLine = $line->purchaseOrderLine;
        if (! $poLine) {
            return ThreeWayMatchResult::NoReceipt;
        }

        // 1. Receipt exists?
        if (bccomp((string) $poLine->received_quantity, '0', 4) <= 0) {
            return ThreeWayMatchResult::NoReceipt;
        }

        // 2. Quantity within accepted receipt?
        //    Cumulative invoiced across all posted or approved invoices for this PO line.
        $alreadyInvoiced = $this->cumulativeInvoiced($poLine, $line->supplier_invoice_id);
        $proposedTotal = bcadd($alreadyInvoiced, (string) $line->quantity, 4);

        if (bccomp($proposedTotal, (string) $poLine->received_quantity, 4) > 0) {
            return ThreeWayMatchResult::QuantityExceeds;
        }

        // 3. Price tolerance?
        $tolerance = bcadd('1', bcdiv((string) $priceTolerancePercent, '100', 8), 8);
        $maxAllowed = bcmul((string) $poLine->unit_price, $tolerance, 4);

        if (bccomp((string) $line->unit_price, $maxAllowed, 4) > 0) {
            return ThreeWayMatchResult::PriceExceeds;
        }

        // 4. Tax rate match (exact to 6 decimals)
        if (bccomp((string) $line->tax_rate, (string) $poLine->tax_rate, 6) !== 0) {
            return ThreeWayMatchResult::TaxMismatch;
        }

        return ThreeWayMatchResult::Ok;
    }

    private function cumulativeInvoiced(PurchaseOrderLine $poLine, ?int $excludeInvoiceId = null): string
    {
        $query = \DB::table('supplier_invoice_lines')
            ->join('supplier_invoices', 'supplier_invoices.id', '=', 'supplier_invoice_lines.supplier_invoice_id')
            ->whereIn('supplier_invoices.status', ['approved', 'posted', 'partially_paid', 'paid'])
            ->where('supplier_invoice_lines.purchase_order_line_id', $poLine->id);

        if ($excludeInvoiceId) {
            $query->where('supplier_invoices.id', '!=', $excludeInvoiceId);
        }

        $sum = $query->sum('supplier_invoice_lines.quantity');

        return $sum ? (string) $sum : '0.0000';
    }
}
