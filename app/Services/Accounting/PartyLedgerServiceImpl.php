<?php

namespace App\Services\Accounting;

use App\Contracts\PartyLedgerService;
use App\Enums\JournalEntryStatus;
use App\Models\Customer;
use App\Models\JournalLine;
use App\Models\Vendor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Procurement\Models\SupplierInvoice;
use Modules\Procurement\Models\VendorInvoice;
use Modules\Procurement\Models\VendorPayment;

final class PartyLedgerServiceImpl implements PartyLedgerService
{
    public function forCustomer(Customer $customer, ?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): Collection
    {
        return $this->linesForParty('customer_id', $customer->id, $from, $to);
    }

    public function customerBalance(Customer $customer): string
    {
        return $this->balanceForParty('customer_id', $customer->id);
    }

    public function forVendor(Vendor $vendor, ?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): Collection
    {
        return $this->linesForParty('vendor_id', $vendor->id, $from, $to);
    }

    /** @return array{lines: Collection<int, object>, opening_balance: string, total_debits: string, total_credits: string, total_invoices: string, total_payments: string, total_wht: string, total_adjustments: string, closing_balance: string} */
    public function vendorStatement(Vendor $vendor, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $prior = JournalLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_entries.status', JournalEntryStatus::Posted->value)
            ->where('journal_lines.vendor_id', $vendor->id)
            ->whereDate('journal_entries.entry_date', '<', $from->format('Y-m-d'))
            ->selectRaw('COALESCE(SUM(journal_lines.credit), 0) AS credits, COALESCE(SUM(journal_lines.debit), 0) AS debits')
            ->first();

        $opening = bcsub((string) $prior->credits, (string) $prior->debits, 4);
        $running = $opening;
        $debits = '0.0000';
        $credits = '0.0000';
        $lines = $this->forVendor($vendor, $from, $to)
            ->groupBy('journal_entry_id')
            ->map(function (Collection $entries): object {
                $row = clone $entries->first();
                $row->debit = $entries->reduce(fn (string $sum, object $entry): string => bcadd($sum, (string) $entry->debit, 4), '0.0000');
                $row->credit = $entries->reduce(fn (string $sum, object $entry): string => bcadd($sum, (string) $entry->credit, 4), '0.0000');

                return $row;
            })->values();

        $invoiceTypes = [SupplierInvoice::class, (new SupplierInvoice)->getMorphClass(), VendorInvoice::class, (new VendorInvoice)->getMorphClass()];
        $paymentTypes = [VendorPayment::class, (new VendorPayment)->getMorphClass()];
        $invoices = collect();
        foreach ([SupplierInvoice::class => 'wht_tax_total', VendorInvoice::class => 'whttax_total'] as $model => $taxColumn) {
            $journalIds = $lines->filter(fn (object $line): bool => in_array($line->source_type, [$model, (new $model)->getMorphClass()], true))->pluck('journal_entry_id');
            if ($journalIds->isNotEmpty()) {
                $records = $model::query()->where('vendor_id', $vendor->id)->whereIn('journal_entry_id', $journalIds)
                    ->get(['journal_entry_id', 'number', 'vendor_invoice_number', $taxColumn]);
                foreach ($records as $invoice) {
                    $invoices->put($invoice->journal_entry_id, (object) [
                        'number' => $invoice->number,
                        'reference' => $invoice->vendor_invoice_number,
                        'wht' => (string) $invoice->{$taxColumn},
                    ]);
                }
            }
        }

        $totals = ['total_invoices' => '0.0000', 'total_payments' => '0.0000', 'total_wht' => '0.0000', 'total_adjustments' => '0.0000'];
        foreach ($lines as $line) {
            $debits = bcadd($debits, (string) $line->debit, 4);
            $credits = bcadd($credits, (string) $line->credit, 4);
            $change = bcsub((string) $line->credit, (string) $line->debit, 4);
            $line->invoice_amount = '0.0000';
            $line->payment = '0.0000';
            $line->wht = '0.0000';
            $line->adjustment = '0.0000';
            if (in_array($line->source_type, $invoiceTypes, true)) {
                $invoice = $invoices->get($line->journal_entry_id);
                $line->wht = $invoice?->wht ?? '0.0000';
                $line->invoice_amount = bcadd($change, $line->wht, 4);
                if ($invoice) {
                    $line->number = $invoice->number;
                    $line->reference = $invoice->reference ?: $line->reference;
                }
            } elseif (in_array($line->source_type, $paymentTypes, true)) {
                $line->payment = bcsub('0.0000', $change, 4);
            } else {
                $line->adjustment = $change;
            }
            $running = bcadd($running, $change, 4);
            $line->running_balance = $running;
            $line->outstanding = $running;
            foreach (['total_invoices' => 'invoice_amount', 'total_payments' => 'payment', 'total_wht' => 'wht', 'total_adjustments' => 'adjustment'] as $total => $field) {
                $totals[$total] = bcadd($totals[$total], (string) $line->{$field}, 4);
            }
        }

        return [
            'lines' => $lines,
            'opening_balance' => $opening,
            'total_debits' => $debits,
            'total_credits' => $credits,
            'closing_balance' => $running,
        ] + $totals;
    }

    public function vendorBalance(Vendor $vendor): string
    {
        return $this->balanceForParty('vendor_id', $vendor->id);
    }

    public function openItemsForCustomer(Customer $customer): Collection
    {
        return $this->openItemsFor('customer_id', $customer->id);
    }

    public function openItemsForVendor(Vendor $vendor): Collection
    {
        return SupplierInvoice::query()
            ->where('vendor_id', $vendor->id)
            ->unpaid()
            ->whereColumn('total', '>', 'paid_amount')
            ->orderBy('invoice_date')
            ->orderBy('id')
            ->get([
                'id',
                'number',
                'invoice_date',
                'total',
                'paid_amount',
                'journal_entry_id',
            ])
            ->map(static fn (SupplierInvoice $invoice): object => (object) [
                'journal_entry_id' => $invoice->journal_entry_id,
                'number' => $invoice->number,
                'entry_date' => $invoice->invoice_date,
                'description' => "Supplier Invoice {$invoice->number}",
                'open_balance' => $invoice->outstanding(),
            ]);
    }

    // ─── Internal ────────────────────────────────────────────────────

    private function linesForParty(string $column, int $partyId, ?\DateTimeInterface $from, ?\DateTimeInterface $to): Collection
    {
        $q = JournalLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->where('journal_entries.status', JournalEntryStatus::Posted->value)
            ->where("journal_lines.{$column}", $partyId);

        if ($from) {
            $q->whereDate('journal_entries.entry_date', '>=', $from->format('Y-m-d'));
        }
        if ($to) {
            $q->whereDate('journal_entries.entry_date', '<=', $to->format('Y-m-d'));
        }

        $rows = $q->orderBy('journal_entries.entry_date')
            ->orderBy('journal_entries.id')
            ->orderBy('journal_lines.id')
            ->get([
                'journal_entries.id as journal_entry_id',
                'journal_entries.number',
                'journal_entries.entry_date',
                'journal_entries.description',
                'journal_entries.source_type',
                'journal_entries.source_id',
                'journal_entries.reference',
                'journal_lines.debit',
                'journal_lines.credit',
                'journal_lines.memo as line_memo',
                'accounts.code as account_code',
                'accounts.name as account_name',
            ]);

        $running = '0.0000';

        return $rows->map(function ($r) use (&$running) {
            $running = bcadd($running, bcsub($r->debit, $r->credit, 4), 4);
            $r->running_balance = $running;

            return $r;
        });
    }

    private function balanceForParty(string $column, int $partyId): string
    {
        $row = JournalLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_entries.status', JournalEntryStatus::Posted->value)
            ->where("journal_lines.{$column}", $partyId)
            ->first([
                DB::raw('COALESCE(SUM(journal_lines.debit), 0)  AS d'),
                DB::raw('COALESCE(SUM(journal_lines.credit), 0) AS c'),
            ]);

        return bcsub((string) $row->d, (string) $row->c, 4);
    }

    /**
     * Open items = invoices/bills that still have an unpaid balance.
     *
     * Convention: invoices debit the party's AR/AP account. The signed sum
     * (debit - credit) across all lines tagged with the party represents the
     * outstanding balance. Per-invoice breakdown is done in Phase 5/4 when
     * invoice documents exist; for now we return the ledger lines grouped by
     * their source document (journal_entries.source_type/source_id).
     */
    private function openItemsFor(string $column, int $partyId): Collection
    {
        return JournalLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_entries.status', JournalEntryStatus::Posted->value)
            ->where("journal_lines.{$column}", $partyId)
            ->groupBy('journal_entries.id', 'journal_entries.number', 'journal_entries.entry_date', 'journal_entries.description', 'journal_entries.source_type', 'journal_entries.source_id')
            ->havingRaw('SUM(journal_lines.debit) - SUM(journal_lines.credit) > 0')
            ->orderBy('journal_entries.entry_date')
            ->get([
                'journal_entries.id as journal_entry_id',
                'journal_entries.number',
                'journal_entries.entry_date',
                'journal_entries.description',
                'journal_entries.source_type',
                'journal_entries.source_id',
                DB::raw('SUM(journal_lines.debit) - SUM(journal_lines.credit) AS open_balance'),
            ]);
    }
}
