<?php

declare(strict_types=1);

namespace Modules\Procurement\Services;

use App\Contracts\JournalPoster;
use App\Contracts\SequenceGenerator;
use App\Contracts\SystemAccountManager;
use App\Data\JournalEntryData;
use App\Data\JournalLineData;
use App\Enums\SystemAccountRole;
use App\Models\Organization;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Procurement\Contracts\SupplierInvoiceManager;
use Modules\Procurement\Data\SupplierInvoiceData;
use Modules\Procurement\Enums\SupplierInvoiceStatus;
use Modules\Procurement\Exceptions\SupplierInvoiceException;
use Modules\Procurement\Models\PurchaseOrderLine;
use Modules\Procurement\Models\SupplierInvoice;
use Modules\Procurement\Models\SupplierInvoiceLine;

final class SupplierInvoiceService implements SupplierInvoiceManager
{
    public function __construct(
        private readonly SequenceGenerator $sequences,
        private readonly LineBrandService $brands,
        private readonly ThreeWayMatchService $matcher,
        private readonly JournalPoster $poster,
        private readonly SystemAccountManager $systemAccounts,
    ) {}

    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return SupplierInvoice::query()
            ->with(['vendor:id,code,name', 'purchaseOrder:id,number', 'creator:id,name'])
            ->withCount('lines')
            ->search($filters['search'] ?? null)
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['vendor_id'] ?? null, fn ($q, $v) => $q->where('vendor_id', $v))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->whereDate('invoice_date', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->whereDate('invoice_date', '<=', $d))
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(SupplierInvoiceData $data, int $userId): SupplierInvoice
    {
        if (count($data->lines) === 0) {
            throw SupplierInvoiceException::noLines();
        }

        return DB::transaction(function () use ($data, $userId) {
            $invoice = SupplierInvoice::query()->create([
                'number' => $this->sequences->next('supplier_invoice', (int) $data->invoiceDate->format('Y')),
                'vendor_id' => $data->vendorId,
                'purchase_order_id' => $data->purchaseOrderId,
                'vendor_invoice_number' => $data->vendorInvoiceNumber,
                'invoice_date' => $data->invoiceDate,
                'due_date' => $data->dueDate,
                'currency_code' => $data->currencyCode,
                'subtotal' => $data->subtotal(),
                'tax_total' => $data->taxTotal(),
                'wht_tax_total' => $data->whtTaxTotal(),
                'total' => $data->total(),
                'status' => SupplierInvoiceStatus::Draft,
                'match_status' => $data->purchaseOrderId === null ? 'matched' : 'pending',
                'notes' => $data->notes,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            foreach ($data->lines as $line) {
                $invoice->lines()->create(array_replace($line->toArray(), ['brand_id' => $this->brands->forOrderLine($line->brandId, $line->purchaseOrderLineId, $data->purchaseOrderId, $line->itemId, $line->position)]));
            }

            return $invoice->fresh(['lines.item', 'vendor', 'purchaseOrder']);
        });
    }

    public function update(SupplierInvoice $invoice, SupplierInvoiceData $data, int $userId): SupplierInvoice
    {
        if (! $invoice->status->isEditable()) {
            throw SupplierInvoiceException::notEditable($invoice);
        }

        return DB::transaction(function () use ($invoice, $data, $userId) {
            $invoice->lines()->delete();

            foreach ($data->lines as $line) {
                $invoice->lines()->create(array_replace($line->toArray(), ['brand_id' => $this->brands->forOrderLine($line->brandId, $line->purchaseOrderLineId, $data->purchaseOrderId, $line->itemId, $line->position)]));
            }

            $invoice->fill([
                'vendor_id' => $data->vendorId,
                'purchase_order_id' => $data->purchaseOrderId,
                'vendor_invoice_number' => $data->vendorInvoiceNumber,
                'invoice_date' => $data->invoiceDate,
                'due_date' => $data->dueDate,
                'currency_code' => $data->currencyCode,
                'subtotal' => $data->subtotal(),
                'tax_total' => $data->taxTotal(),
                'wht_tax_total' => $data->whtTaxTotal(),
                'total' => $data->total(),
                'notes' => $data->notes,
                'match_status' => $data->purchaseOrderId === null ? 'matched' : 'pending',
                'match_notes' => null,
                'updated_by' => $userId,
            ])->save();

            return $invoice->fresh(['lines.item']);
        });
    }

    public function match(SupplierInvoice $invoice, int $userId): SupplierInvoice
    {
        if (! $invoice->status->canMatch()) {
            throw SupplierInvoiceException::cannotMatch($invoice);
        }

        $tolerance = (float) (Organization::current()->price_tolerance_percent ?? 0);

        $result = $this->matcher->match($invoice, $tolerance);

        return DB::transaction(function () use ($invoice, $result, $userId) {
            $invoice->loadMissing('lines');

            // Persist per-line result
            foreach ($result['lines'] as $lineResult) {
                SupplierInvoiceLine::query()
                    ->whereKey($lineResult['line_id'])
                    ->update([
                        'match_result' => $lineResult['result'],
                        'match_note' => $lineResult['note'],
                    ]);
            }

            $invoice->forceFill([
                'match_status' => $result['status'],
                'match_notes' => $result['status'] === 'mismatch'
                    ? 'Three-way match failed on one or more lines.'
                    : 'All lines matched.',
                'matched_at' => now(),
                'matched_by' => $userId,
                'status' => $result['status'] === 'matched'
                    ? SupplierInvoiceStatus::Matched
                    : SupplierInvoiceStatus::Mismatch,
                'updated_by' => $userId,
            ])->save();

            return $invoice->fresh(['lines']);
        });
    }

    public function approve(SupplierInvoice $invoice, int $userId, bool $overrideMismatch = false): SupplierInvoice
    {
        if (! $invoice->status->canApprove()
            && ! ($invoice->purchase_order_id === null && $invoice->status === SupplierInvoiceStatus::Draft)) {
            throw SupplierInvoiceException::cannotApprove($invoice);
        }

        if ($invoice->match_status === 'mismatch' && ! $overrideMismatch) {
            throw SupplierInvoiceException::mismatchRequiresOverride($invoice);
        }

        $invoice->forceFill([
            'status' => SupplierInvoiceStatus::Approved,
            'approved_at' => now(),
            'approved_by' => $userId,
            'updated_by' => $userId,
        ])->save();

        return $invoice->fresh();
    }

    public function reject(SupplierInvoice $invoice, int $userId, string $reason): SupplierInvoice
    {
        if (! $invoice->status->canApprove()) {
            throw SupplierInvoiceException::cannotApprove($invoice);
        }

        $invoice->forceFill([
            'status' => SupplierInvoiceStatus::Rejected,
            'notes' => trim(($invoice->notes ?? '')."\nRejected: ".$reason),
            'updated_by' => $userId,
        ])->save();

        return $invoice->fresh();
    }

    public function post(SupplierInvoice $invoice, int $userId): SupplierInvoice
    {
        if (! $invoice->status->canPost()) {
            throw SupplierInvoiceException::cannotPost($invoice);
        }

        return DB::transaction(function () use ($invoice, $userId) {
            /** @var SupplierInvoice $locked */
            $locked = SupplierInvoice::query()->lockForUpdate()->findOrFail($invoice->id);

            // Resolve accounts per line
            $debitTotals = [];   // account_id => amount
            $taxTotals = [];   // account_id => amount

            foreach ($locked->lines as $line) {
                $debitAccountId = $this->resolveDebitAccount($line);
                $taxAccountId = $this->resolveTaxAccount($line);

                $line->forceFill([
                    'debit_account_id' => $debitAccountId,
                    'input_tax_account_id' => $taxAccountId,
                ])->save();

                $debitTotals[$debitAccountId] = bcadd(
                    $debitTotals[$debitAccountId] ?? '0.0000',
                    (string) $line->line_subtotal,
                    4,
                );

                if (bccomp((string) $line->line_tax, '0', 4) > 0) {
                    $taxTotals[$taxAccountId] = bcadd(
                        $taxTotals[$taxAccountId] ?? '0.0000',
                        (string) $line->line_tax,
                        4,
                    );
                }

                // Update PO line cumulative invoiced quantity
                $poLine = PurchaseOrderLine::query()->lockForUpdate()->find($line->purchase_order_line_id);
                if ($poLine) {
                    $poLine->forceFill([
                        'invoiced_quantity' => bcadd(
                            (string) $poLine->invoiced_quantity,
                            (string) $line->quantity,
                            4,
                        ),
                    ])->save();
                }
            }

            // Build journal lines
            $journalLines = [];

            foreach ($debitTotals as $accountId => $amount) {
                $journalLines[] = new JournalLineData(
                    accountId: (int) $accountId,
                    debit: $amount,
                    credit: '0.0000',
                    memo: "Supplier invoice {$locked->number}",
                );
            }

            foreach ($taxTotals as $accountId => $amount) {
                $journalLines[] = new JournalLineData(
                    accountId: (int) $accountId,
                    debit: $amount,
                    credit: '0.0000',
                    memo: "Input tax — {$locked->number}",
                );
            }

            if (bccomp((string) $locked->wht_tax_total, '0', 4) > 0) {
                $withholdingTaxAccount = $this->systemAccounts->resolve(SystemAccountRole::WithholdingTaxPayable);
                if (! $withholdingTaxAccount) {
                    throw SupplierInvoiceException::missingSystemAccount('withholding_tax_payable');
                }

                $journalLines[] = new JournalLineData(
                    accountId: $withholdingTaxAccount->id,
                    debit: '0.0000',
                    credit: (string) $locked->wht_tax_total,
                    memo: "Withholding tax payable — {$locked->number}",
                );
            }

            // Credit AP
            $apAccount = $this->systemAccounts->resolve(SystemAccountRole::AccountsPayable);
            if (! $apAccount) {
                throw SupplierInvoiceException::missingSystemAccount('accounts_payable');
            }

            $journalLines[] = new JournalLineData(
                accountId: $apAccount->id,
                debit: '0.0000',
                credit: (string) $locked->total,
                memo: "AP — {$locked->vendor->name} · {$locked->number}",
                vendorId: $locked->vendor_id,
            );

            // Post to GL
            $journal = $this->poster->post(new JournalEntryData(
                entryDate: $locked->invoice_date,
                description: "Supplier Invoice {$locked->number} — {$locked->vendor->name}",
                lines: $journalLines,
                reference: $locked->vendor_invoice_number,
            ), [
                'source_type' => SupplierInvoice::class,
                'source_id' => $locked->id,
                'user_id' => $userId,
            ]);

            $locked->forceFill([
                'status' => SupplierInvoiceStatus::Posted,
                'posted_at' => now(),
                'posted_by' => $userId,
                'journal_entry_id' => $journal->id,
                'updated_by' => $userId,
            ])->save();

            return $locked->fresh(['lines', 'journalEntry']);
        });
    }

    public function cancel(SupplierInvoice $invoice, int $userId, ?string $reason = null): SupplierInvoice
    {
        if (! $invoice->status->canCancel()) {
            throw SupplierInvoiceException::cannotCancel($invoice);
        }

        $invoice->forceFill([
            'status' => SupplierInvoiceStatus::Cancelled,
            'notes' => $reason
                ? trim(($invoice->notes ?? '')."\nCancelled: ".$reason)
                : $invoice->notes,
            'updated_by' => $userId,
        ])->save();

        return $invoice->fresh();
    }

    public function delete(SupplierInvoice $invoice): void
    {
        if ($invoice->status !== SupplierInvoiceStatus::Draft) {
            throw SupplierInvoiceException::cannotDelete($invoice);
        }
        DB::transaction(fn () => $invoice->delete());
    }

    // ─── Account resolution ─────────────────────────────────────────

    private function resolveDebitAccount(SupplierInvoiceLine $line): int
    {
        if ($line->expense_account_id !== null) {
            return (int) $line->expense_account_id;
        }

        $item = $line->item;

        // Item-level override first
        if ($item?->item_type->tracksInventory() && $item->inventory_account_id) {
            return (int) $item->inventory_account_id;
        }
        if (! $item?->item_type->tracksInventory() && $item->expense_account_id) {
            return (int) $item->expense_account_id;
        }

        // Category defaults next
        $category = $item?->category;
        if ($category) {
            if ($item->item_type->tracksInventory() && $category->inventory_account_id) {
                return (int) $category->inventory_account_id;
            }
            if (! $item->item_type->tracksInventory() && $category->cogs_account_id) {
                // Using COGS as an expense proxy — category should carry an expense_account_id
                // when Phase 3 extends category columns. For now fall through to system role.
            }
        }

        // System-role fallbacks
        $role = $item?->item_type->tracksInventory()
            ? SystemAccountRole::Inventory
            : SystemAccountRole::PayrollExpense;    // generic "expense" role

        $account = $this->systemAccounts->resolve($role);
        if (! $account) {
            throw SupplierInvoiceException::missingSystemAccount($role->value);
        }

        return $account->id;
    }

    private function resolveTaxAccount(SupplierInvoiceLine $line): int
    {
        $account = $this->systemAccounts->resolve(SystemAccountRole::InputTax);
        if (! $account) {
            throw SupplierInvoiceException::missingSystemAccount('input_tax');
        }

        return $account->id;
    }
}
