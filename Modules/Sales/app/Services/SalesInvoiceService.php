<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use App\Contracts\JournalPoster;
use App\Contracts\SequenceGenerator;
use App\Contracts\StockLedger;
use App\Contracts\SystemAccountManager;
use App\Data\JournalEntryData;
use App\Data\JournalLineData;
use App\Enums\SystemAccountRole;
use App\Exceptions\BusinessRuleException;
use App\Models\Item;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\StockBalance;
use Modules\Sales\Contracts\SalesInvoiceManager;
use Modules\Sales\Data\SalesInvoiceData;
use Modules\Sales\Data\SalesInvoiceLineData;
use Modules\Sales\Enums\SalesInvoiceStatus;
use Modules\Sales\Exceptions\SalesInvoiceException;
use Modules\Sales\Models\SalesInvoice;
use Modules\Sales\Models\SalesOrder;

final class SalesInvoiceService implements SalesInvoiceManager
{
    public function __construct(
        private readonly SequenceGenerator $sequences,
        private readonly JournalPoster $poster,
        private readonly SystemAccountManager $systemAccounts,
        private readonly StockLedger $stockLedger,
    ) {}

    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return SalesInvoice::query()
            ->with(['customer:id,code,name', 'salesOrder:id,number', 'creator:id,name'])
            ->withCount('lines')
            ->withSum(['creditNotes as posted_credit_total' => fn ($query) => $query->where('status', 'posted')], 'total')
            ->search($filters['search'] ?? null)
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['customer_id'] ?? null, fn ($q, $c) => $q->where('customer_id', $c))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->whereDate('invoice_date', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->whereDate('invoice_date', '<=', $d))
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(SalesInvoiceData $data, int $userId): SalesInvoice
    {
        if (count($data->lines) === 0) {
            throw SalesInvoiceException::noLines();
        }

        return DB::transaction(function () use ($data, $userId) {
            $invoice = SalesInvoice::query()->create([
                'number' => $this->sequences->next('invoice', (int) $data->invoiceDate->format('Y')),
                'customer_id' => $data->customerId,
                'sales_order_id' => $data->salesOrderId,
                'invoice_date' => $data->invoiceDate,
                'due_date' => $data->dueDate,
                'warehouse_id' => $data->warehouseId,
                'department_id' => $data->departmentId,
                'cost_center_id' => $data->costCenterId,
                'payment_term_id' => $data->paymentTermId,
                'currency_code' => $data->currencyCode,
                'exchange_rate' => $data->exchangeRate,
                'reference' => $data->reference,
                'notes' => $data->notes,
                'terms' => $data->terms,
                'status' => SalesInvoiceStatus::Draft,
                'match_status' => 'pending',
                'subtotal' => $data->subtotal(),
                'discount_total' => $data->discountTotal(),
                'tax_total' => $data->taxTotal(),
                'wht_tax_total' => $data->whtTaxTotal(),
                'total' => $data->total(),
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            foreach ($data->lines as $line) {
                $invoice->lines()->create($line->toArray());
            }

            return $invoice->fresh(['lines.item', 'customer']);
        });
    }

    public function createFromSalesOrder(SalesOrder $order, int $userId): SalesInvoice
    {
        if (! in_array($order->status->value, ['delivered', 'partially_delivered'], true)) {
            throw SalesInvoiceException::orderNotInvoiceable($order);
        }

        $lines = $order->lines
            ->filter(fn ($l) => bccomp((string) $l->delivered_quantity, (string) $l->invoiced_quantity, 4) > 0)
            ->map(function ($l, $i) {
                $open = bcsub((string) $l->delivered_quantity, (string) $l->invoiced_quantity, 4);

                return SalesInvoiceLineData::fromArray([
                    'item_id' => $l->item_id,
                    'unit_id' => $l->unit_id,
                    'quantity' => $open,
                    'unit_price' => (string) $l->unit_price,
                    'discount_percent' => (string) $l->discount_percent,
                    'tax_rate' => (string) $l->tax_rate,
                    'wht_tax_rate' => (string) ($l->wht_tax_rate ?? '0'),
                    'description' => $l->description,
                    'sales_order_line_id' => $l->id,
                ], $i);
            })
            ->values()
            ->all();

        $paymentTerm = $order->paymentTerm;
        $dueDate = $paymentTerm
            ? $paymentTerm->dueDate(now())
            : now()->addDays(30);

        return $this->create(new SalesInvoiceData(
            customerId: $order->customer_id,
            invoiceDate: now(),
            dueDate: $dueDate,
            currencyCode: $order->currency_code,
            lines: $lines,
            salesOrderId: $order->id,
            warehouseId: $order->warehouse_id,
            departmentId: $order->department_id,
            paymentTermId: $order->payment_term_id,
            reference: $order->reference,
            terms: $order->terms,
        ), $userId);
    }

    public function update(SalesInvoice $invoice, SalesInvoiceData $data, int $userId): SalesInvoice
    {
        if (! $invoice->status->isEditable()) {
            throw SalesInvoiceException::notEditable($invoice);
        }

        return DB::transaction(function () use ($invoice, $data, $userId) {
            $invoice->lines()->delete();

            foreach ($data->lines as $line) {
                $invoice->lines()->create($line->toArray());
            }

            $invoice->fill([
                'customer_id' => $data->customerId,
                'sales_order_id' => $data->salesOrderId,
                'invoice_date' => $data->invoiceDate,
                'due_date' => $data->dueDate,
                'warehouse_id' => $data->warehouseId,
                'department_id' => $data->departmentId,
                'cost_center_id' => $data->costCenterId,
                'payment_term_id' => $data->paymentTermId,
                'currency_code' => $data->currencyCode,
                'reference' => $data->reference,
                'notes' => $data->notes,
                'terms' => $data->terms,
                'subtotal' => $data->subtotal(),
                'discount_total' => $data->discountTotal(),
                'tax_total' => $data->taxTotal(),
                'wht_tax_total' => $data->whtTaxTotal(),
                'total' => $data->total(),
                'match_status' => 'pending',
                'match_notes' => null,
                'updated_by' => $userId,
            ])->save();

            return $invoice->fresh(['lines.item']);
        });
    }

    /**
     * Three-way match: SO + Delivery + Invoice.
     * For each invoice line, verify the invoiced quantity ≤ delivered quantity
     * and the price matches the SO line price within tolerance.
     */
    public function match(SalesInvoice $invoice, int $userId): SalesInvoice
    {
        if (! $invoice->status->canMatch()) {
            throw SalesInvoiceException::invalidTransition($invoice, 'match');
        }

        $invoice->loadMissing(['lines.salesOrderLine', 'salesOrder']);

        $hasFailure = false;

        foreach ($invoice->lines as $line) {
            $soLine = $line->salesOrderLine;
            if (! $soLine) {
                $line->update(['description' => trim(($line->description ?? '').' [no SO line]')]);
                $hasFailure = true;

                continue;
            }

            // Quantity must not exceed delivered-and-not-invoiced
            $maxInvoiceable = bcsub((string) $soLine->delivered_quantity, (string) $soLine->invoiced_quantity, 4);

            if (bccomp((string) $line->quantity, $maxInvoiceable, 4) > 0) {
                $hasFailure = true;
                break;
            }

            // Price tolerance: default 0
            if (bccomp((string) $line->unit_price, (string) $soLine->unit_price, 4) !== 0) {
                $hasFailure = true;
                break;
            }
        }

        $invoice->forceFill([
            'match_status' => $hasFailure ? 'mismatch' : 'matched',
            'match_notes' => $hasFailure ? 'Three-way match failed.' : 'All lines matched.',
            'matched_at' => now(),
            'matched_by' => $userId,
            'status' => $hasFailure ? SalesInvoiceStatus::Mismatch : SalesInvoiceStatus::Matched,
            'updated_by' => $userId,
        ])->save();

        return $invoice->fresh();
    }

    public function approve(SalesInvoice $invoice, int $userId, bool $overrideMismatch = false): SalesInvoice
    {
        if (! $invoice->status->canApprove()) {
            throw SalesInvoiceException::cannotApprove($invoice);
        }

        if ($invoice->match_status === 'mismatch' && ! $overrideMismatch) {
            throw SalesInvoiceException::mismatchRequiresOverride($invoice);
        }

        $invoice->forceFill([
            'status' => SalesInvoiceStatus::Approved,
            'approved_at' => now(),
            'approved_by' => $userId,
            'updated_by' => $userId,
        ])->save();

        return $invoice->fresh();
    }

    public function reject(SalesInvoice $invoice, int $userId, string $reason): SalesInvoice
    {
        if (! $invoice->status->canApprove()) {
            throw SalesInvoiceException::cannotApprove($invoice);
        }

        $invoice->forceFill([
            'status' => SalesInvoiceStatus::Rejected,
            'notes' => trim(($invoice->notes ?? '')."\nRejected: ".$reason),
            'updated_by' => $userId,
        ])->save();

        return $invoice->fresh();
    }

    /**
     * Post the invoice to the general ledger.
     *
     * Journal:
     *   Debit  AR                      (invoice total)
     *   Credit Revenue                 (sum of line_subtotal)
     *   Credit Output Tax              (sum of line_tax)
     *   Debit  WHT Receivable?         (optional — Pakistan WHT means the customer withholds)
     *   Debit  COGS                    (for stock items)
     *   Credit Inventory               (for stock items)
     *
     * In Pakistan the WHT the customer withholds is not received by us; it
     * sits as an asset (tax credit recoverable against our own liability).
     * We post: Debit AR = total − WHT; Debit WHT Receivable = WHT.
     * Simpler: Debit AR = total, Debit WHT Receivable = WHT, Credit Revenue + Tax = gross.
     * Net effect on AR is total minus WHT, which is what the customer pays.
     */
    public function post(SalesInvoice $invoice, int $userId): SalesInvoice
    {
        if (! $invoice->status->canPost()) {
            throw SalesInvoiceException::cannotPost($invoice);
        }

        return DB::transaction(function () use ($invoice, $userId) {
            /** @var SalesInvoice $locked */
            $locked = SalesInvoice::query()
                ->with('lines.item')
                ->lockForUpdate()
                ->findOrFail($invoice->id);

            // ── Resolve system accounts ─────────────────────────────
            $arAccount = $this->systemAccounts->resolve(SystemAccountRole::AccountsReceivable);
            $salesAccount = $this->systemAccounts->resolve(SystemAccountRole::SalesRevenue);
            $outputTaxAcct = $this->systemAccounts->resolve(SystemAccountRole::OutputTax);
            $inventoryAcct = $this->systemAccounts->resolve(SystemAccountRole::Inventory);
            $cogsAcct = $this->systemAccounts->resolve(SystemAccountRole::Cogs);

            if (! $arAccount) {
                throw SalesInvoiceException::missingSystemAccount('accounts_receivable');
            }
            if (! $salesAccount) {
                throw SalesInvoiceException::missingSystemAccount('sales_revenue');
            }
            if (! $outputTaxAcct) {
                throw SalesInvoiceException::missingSystemAccount('output_tax');
            }
            if ($locked->lines->contains(fn ($line) => $line->item?->item_type->tracksInventory())) {
                if (! $cogsAcct) {
                    throw SalesInvoiceException::missingSystemAccount('cogs');
                }
                if (! $inventoryAcct) {
                    throw SalesInvoiceException::missingSystemAccount('inventory');
                }
            }

            // ── Journal lines ───────────────────────────────────────
            $journalLines = [];

            // The stored invoice total is net of WHT, so AR reflects the amount due from the customer.
            $journalLines[] = new JournalLineData(
                accountId: $arAccount->id,
                debit: (string) $locked->total,
                credit: '0.0000',
                memo: "AR — {$locked->customer->name} · {$locked->number}",
                customerId: $locked->customer_id,
            );

            // Credit Revenue per line subtotal, and Output Tax per line tax
            $revenueTotals = [];
            $taxTotals = [];
            $cogsTotal = '0.0000';

            foreach ($locked->lines as $line) {
                $revenueAccountId = $line->item?->sales_account_id
                    ?? $salesAccount->id;
                $revenueTotals[$revenueAccountId] = bcadd(
                    $revenueTotals[$revenueAccountId] ?? '0.0000',
                    (string) $line->line_subtotal,
                    4,
                );

                if (bccomp((string) $line->line_tax, '0', 4) > 0) {
                    $taxAccountId = $line->item?->tax_rate_id
                        ? ($outputTaxAcct->id)
                        : $outputTaxAcct->id;
                    $taxTotals[$taxAccountId] = bcadd(
                        $taxTotals[$taxAccountId] ?? '0.0000',
                        (string) $line->line_tax,
                        4,
                    );
                }

                // COGS: for stock items, record the cost basis.
                if ($line->item?->item_type->tracksInventory() && bccomp((string) $line->line_subtotal, '0', 4) > 0) {
                    $unitCost = $this->stockLedger->onHand($line->item_id, $locked->warehouse_id ?? 0) !== '0.0000'
                        ? $this->computeUnitCost($line->item_id, $locked->warehouse_id)
                        : '0.0000';

                    $lineCogs = bcmul((string) $line->quantity, $unitCost, 4);

                    $line->forceFill([
                        'unit_cost' => $unitCost,
                        'cogs_amount' => $lineCogs,
                        'revenue_account_id' => $revenueAccountId,
                        'tax_account_id' => $outputTaxAcct->id,
                        'cogs_account_id' => $cogsAcct?->id,
                        'inventory_account_id' => $inventoryAcct?->id,
                    ])->save();

                    $cogsTotal = bcadd($cogsTotal, $lineCogs, 4);
                }
            }

            foreach ($revenueTotals as $accId => $amount) {
                $journalLines[] = new JournalLineData(
                    accountId: (int) $accId,
                    debit: '0.0000',
                    credit: $amount,
                    memo: "Revenue — {$locked->number}",
                );
            }

            foreach ($taxTotals as $accId => $amount) {
                $journalLines[] = new JournalLineData(
                    accountId: (int) $accId,
                    debit: '0.0000',
                    credit: $amount,
                    memo: "Output Tax — {$locked->number}",
                );
            }

            // Debit COGS / Credit Inventory (for stock items)
            if (bccomp($cogsTotal, '0', 4) > 0) {
                $journalLines[] = new JournalLineData(
                    accountId: $cogsAcct->id,
                    debit: $cogsTotal,
                    credit: '0.0000',
                    memo: "COGS — {$locked->number}",
                );
                $journalLines[] = new JournalLineData(
                    accountId: $inventoryAcct->id,
                    debit: '0.0000',
                    credit: $cogsTotal,
                    memo: "Inventory reduction — {$locked->number}",
                );
            }

            // ── Handle WHT: recognize the tax credit receivable ─────
            // The invoice total already excludes WHT, so do not reduce AR again.
            $whtTotal = (string) $locked->wht_tax_total;

            if (bccomp($whtTotal, '0', 4) > 0) {
                $whtAcct = $this->systemAccounts->resolve(SystemAccountRole::InputTax)
                    ?? $this->systemAccounts->resolve(SystemAccountRole::OutputTax);

                if (! $whtAcct) {
                    throw SalesInvoiceException::missingSystemAccount('input_tax');
                }

                $journalLines[] = new JournalLineData(
                    accountId: $whtAcct->id,
                    debit: $whtTotal,
                    credit: '0.0000',
                    memo: "WHT withheld — {$locked->number}",
                );
            }

            // ── Post ────────────────────────────────────────────────
            $journal = $this->poster->post(new JournalEntryData(
                entryDate: $locked->invoice_date,
                description: "Sales Invoice {$locked->number} — {$locked->customer->name}",
                lines: $journalLines,
                reference: $locked->reference,
            ), [
                'source_type' => SalesInvoice::class,
                'source_id' => $locked->id,
                'user_id' => $userId,
            ]);

            // Advance the SO lines' invoiced quantities
            foreach ($locked->lines as $line) {
                if ($line->salesOrderLine) {
                    $soLine = $line->salesOrderLine;
                    $soLine->forceFill([
                        'invoiced_quantity' => bcadd(
                            (string) $soLine->invoiced_quantity,
                            (string) $line->quantity,
                            4,
                        ),
                    ])->save();
                }
            }

            $locked->forceFill([
                'status' => SalesInvoiceStatus::Posted,
                'posted_at' => now(),
                'posted_by' => $userId,
                'journal_entry_id' => $journal->id,
                'updated_by' => $userId,
            ])->save();

            return $locked->fresh(['lines', 'journalEntry']);
        });
    }

    public function cancel(SalesInvoice $invoice, int $userId, ?string $reason = null): SalesInvoice
    {
        if (! $invoice->status->canCancel()) {
            throw SalesInvoiceException::invalidTransition($invoice, 'cancel');
        }

        $invoice->forceFill([
            'status' => SalesInvoiceStatus::Cancelled,
            'notes' => $reason
                ? trim(($invoice->notes ?? '')."\nCancelled: ".$reason)
                : $invoice->notes,
            'updated_by' => $userId,
        ])->save();

        return $invoice->fresh();
    }

    public function reverse(SalesInvoice $invoice, int $userId, string $reason): SalesInvoice
    {
        if (! $invoice->status->canReverse()) {
            throw SalesInvoiceException::cannotReverse($invoice);
        }

        return DB::transaction(function () use ($invoice, $userId, $reason) {
            /** @var SalesInvoice $original */
            $original = SalesInvoice::query()->lockForUpdate()->findOrFail($invoice->id);

            if ($original->returns()->where('status', '!=', 'rejected')->exists()) {
                throw BusinessRuleException::make('Invoices with active or completed returns cannot be reversed.');
            }

            if ($original->reversed_by_id) {
                throw SalesInvoiceException::cannotReverse($original);
            }

            if (! $original->journal_entry_id) {
                throw SalesInvoiceException::cannotReverse($original);
            }

            // Reverse the GL entry
            $reversalJournal = $this->poster->reverse(
                $original->journalEntry,
                $userId,
                "Reversal of {$original->number}: {$reason}",
            );

            // Create mirror invoice document
            $reversal = SalesInvoice::query()->create([
                'number' => $this->sequences->next('invoice', (int) now()->format('Y')),
                'customer_id' => $original->customer_id,
                'sales_order_id' => $original->sales_order_id,
                'invoice_date' => now(),
                'due_date' => now(),
                'warehouse_id' => $original->warehouse_id,
                'department_id' => $original->department_id,
                'cost_center_id' => $original->cost_center_id,
                'payment_term_id' => $original->payment_term_id,
                'currency_code' => $original->currency_code,
                'exchange_rate' => $original->exchange_rate,
                'reference' => $original->reference,
                'subtotal' => bcmul((string) $original->subtotal, '-1', 4),
                'discount_total' => bcmul((string) $original->discount_total, '-1', 4),
                'tax_total' => bcmul((string) $original->tax_total, '-1', 4),
                'wht_tax_total' => bcmul((string) $original->wht_tax_total, '-1', 4),
                'total' => bcmul((string) $original->total, '-1', 4),
                'notes' => "Reversal of {$original->number}: {$reason}",
                'status' => SalesInvoiceStatus::Posted,
                'match_status' => 'matched',
                'posted_at' => now(),
                'posted_by' => $userId,
                'journal_entry_id' => $reversalJournal->id,
                'reverses_id' => $original->id,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            // Mirror the lines
            foreach ($original->lines as $i => $line) {
                $reversal->lines()->create([
                    'sales_order_line_id' => $line->sales_order_line_id,
                    'delivery_line_id' => $line->delivery_line_id,
                    'item_id' => $line->item_id,
                    'unit_id' => $line->unit_id,
                    'position' => $i,
                    'quantity' => bcmul((string) $line->quantity, '-1', 4),
                    'unit_price' => $line->unit_price,
                    'discount_percent' => $line->discount_percent,
                    'discount_amount' => bcmul((string) $line->discount_amount, '-1', 4),
                    'tax_rate' => $line->tax_rate,
                    'wht_tax_rate' => $line->wht_tax_rate,
                    'line_subtotal' => bcmul((string) $line->line_subtotal, '-1', 4),
                    'line_tax' => bcmul((string) $line->line_tax, '-1', 4),
                    'line_wht_tax' => bcmul((string) $line->line_wht_tax, '-1', 4),
                    'line_total' => bcmul((string) $line->line_total, '-1', 4),
                    'description' => 'Reversal: '.($line->description ?? ''),
                ]);
            }

            // Release SO line invoiced quantities
            foreach ($original->lines as $line) {
                if ($line->salesOrderLine) {
                    $soLine = $line->salesOrderLine;
                    $soLine->forceFill([
                        'invoiced_quantity' => bcsub(
                            (string) $soLine->invoiced_quantity,
                            (string) $line->quantity,
                            4,
                        ),
                    ])->save();
                }
            }

            $original->forceFill([
                'status' => SalesInvoiceStatus::Reversed,
                'reversed_by_id' => $reversal->id,
                'updated_by' => $userId,
            ])->save();

            return $reversal->fresh(['lines', 'reverses']);
        });
    }

    public function delete(SalesInvoice $invoice): void
    {
        if ($invoice->status !== SalesInvoiceStatus::Draft) {
            throw SalesInvoiceException::notEditable($invoice);
        }
        DB::transaction(fn () => $invoice->delete());
    }

    /**
     * Retrieve the current weighted-average unit cost for an item in a warehouse.
     * Falls back to the item's cost_price if no balance exists.
     */
    private function computeUnitCost(int $itemId, ?int $warehouseId): string
    {
        if (! $warehouseId) {
            return '0.0000';
        }

        $balance = StockBalance::query()
            ->where('item_id', $itemId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        if ($balance && bccomp((string) $balance->quantity, '0', 4) > 0) {
            return $balance->averageUnitCost();
        }

        $item = Item::query()->find($itemId);

        return (string) ($item?->cost_price ?? '0.0000');
    }
}
