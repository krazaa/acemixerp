<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use App\Contracts\JournalPoster;
use App\Contracts\SequenceGenerator;
use App\Contracts\StockLedger;
use App\Contracts\SystemAccountManager;
use App\Data\JournalEntryData;
use App\Data\JournalLineData;
use App\Data\StockMovementData;
use App\Enums\StockMovementType;
use App\Enums\SystemAccountRole;
use App\Exceptions\BusinessRuleException;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Modules\Sales\Models\SalesCreditNote;
use Modules\Sales\Models\SalesInvoice;
use Modules\Sales\Models\SalesReturn;
use Modules\Sales\Models\SalesReturnLine;

class SalesReturnWorkflow
{
    public function __construct(
        private readonly SequenceGenerator $sequences,
        private readonly StockLedger $stock,
        private readonly JournalPoster $journals,
        private readonly SystemAccountManager $accounts,
    ) {}

    public function create(array $data, User $user): SalesReturn
    {
        Gate::forUser($user)->authorize('create', SalesReturn::class);

        return DB::transaction(function () use ($data, $user): SalesReturn {
            $invoice = SalesInvoice::query()->lockForUpdate()->findOrFail($data['sales_invoice_id']);
            $this->eligibleInvoice($invoice);
            if (! Warehouse::query()->whereKey($data['warehouse_id'])->where('status', 'active')->exists()) {
                throw BusinessRuleException::make('Select an active receiving warehouse.');
            }
            if ($data['return_date'] < $invoice->invoice_date->toDateString()) {
                throw BusinessRuleException::make('Return date cannot precede the original invoice.');
            }
            $lines = $invoice->lines()->with('item')->get()->keyBy('id');
            $return = SalesReturn::create([
                'number' => $this->sequences->next('sales_return'),
                'sales_invoice_id' => $invoice->id, 'warehouse_id' => $data['warehouse_id'],
                'return_date' => $data['return_date'], 'reason' => $data['reason'], 'status' => 'requested', 'created_by' => $user->id,
            ]);
            foreach ($data['lines'] as $row) {
                $row['quantity'] = (string) $row['quantity'];
                $line = $lines->get($row['sales_invoice_line_id']);
                if (! $line || ! $line->item?->item_type->tracksInventory()) {
                    throw BusinessRuleException::make('Every return line must be a stock item on the selected invoice.');
                }
                $reserved = SalesReturnLine::query()->where('sales_invoice_line_id', $line->id)
                    ->whereHas('salesReturn', fn ($q) => $q->where('status', '!=', 'rejected'))
                    ->with('salesReturn')->get()->reduce(fn (string $sum, SalesReturnLine $r): string => bcadd($sum,
                        in_array($r->salesReturn->status, ['accepted', 'credited', 'posted'], true) ? $r->accepted_quantity : $r->requested_quantity, 4), '0.0000');
                if (bccomp($row['quantity'], '0', 4) <= 0 || bccomp(bcadd($reserved, $row['quantity'], 4), $line->quantity, 4) > 0) {
                    throw BusinessRuleException::make('Requested quantity exceeds the remaining returnable quantity for '.$line->item->name.'.');
                }
                $return->lines()->create(['sales_invoice_line_id' => $line->id, 'requested_quantity' => $row['quantity']]);
            }

            return $return;
        });
    }

    public function transition(SalesReturn $return, string $step, array $data, User $user): SalesReturn
    {
        return DB::transaction(function () use ($return, $step, $data, $user): SalesReturn {
            // Serialize requests and credit posting against the original invoice.
            $invoice = SalesInvoice::query()->lockForUpdate()->findOrFail($return->sales_invoice_id);
            $locked = SalesReturn::query()->lockForUpdate()->findOrFail($return->id);
            $expected = ['approve' => 'requested', 'reject' => 'requested', 'receive' => 'approved', 'inspect' => 'received', 'accept' => 'inspected', 'credit' => 'accepted', 'post' => 'credited'];
            if (($expected[$step] ?? null) !== $locked->status) {
                throw BusinessRuleException::make('This action is not available at the current return stage.');
            }
            Gate::forUser($user)->authorize($step, $locked);
            $this->eligibleInvoice($invoice);
            $locked->load('lines.invoiceLine.item');
            switch ($step) {
                case 'approve':
                    $locked->update(['status' => 'approved', 'approved_by' => $user->id, 'approved_at' => now()]);
                    break;
                case 'reject':
                    $locked->update(['status' => 'rejected', 'rejection_reason' => $data['rejection_reason'], 'rejected_by' => $user->id, 'rejected_at' => now()]);
                    break;
                case 'receive':
                case 'inspect':
                    $this->quantities($locked, $step, $data['lines'], $user);
                    break;
                case 'accept':
                    $this->accept($locked, $invoice, $user);
                    break;
                case 'credit':
                    $this->credit($locked, $invoice, $user);
                    break;
                case 'post':
                    $this->post($locked, $invoice, $user);
                    break;
                default:
                    throw BusinessRuleException::make('Unknown return action.');
            }

            return $locked->fresh();
        });
    }

    private function eligibleInvoice(SalesInvoice $invoice): void
    {
        if (! in_array($invoice->status->value, ['posted', 'partially_paid', 'paid'], true) || ! $invoice->journal_entry_id
            || $invoice->journalEntry?->status->value !== 'posted') {
            throw BusinessRuleException::make('Returns require an original posted invoice with an unreversed journal.');
        }
        if (bccomp((string) $invoice->exchange_rate, '1', 8) !== 0) {
            throw BusinessRuleException::make('Foreign-exchange returns require an accounting review before processing.');
        }
    }

    private function quantities(SalesReturn $return, string $step, array $rows, User $user): void
    {
        $input = collect($rows)->keyBy('id');
        if ($input->count() !== $return->lines->count() || $return->lines->pluck('id')->diff($input->keys())->isNotEmpty()) {
            throw BusinessRuleException::make('Provide quantities for every line on this return, and no other lines.');
        }
        foreach ($return->lines as $line) {
            $row = $input->get($line->id);
            $row['quantity'] = (string) $row['quantity'];
            $limit = $step === 'receive' ? $line->requested_quantity : $line->received_quantity;
            if (bccomp($row['quantity'], '0', 4) < 0 || bccomp($row['quantity'], $limit, 4) > 0) {
                throw BusinessRuleException::make('Quantity cannot exceed '.($step === 'receive' ? 'the requested' : 'the received').' quantity.');
            }
            if ($step === 'inspect' && bccomp($row['quantity'], $limit, 4) < 0 && blank($row['notes'] ?? null)) {
                throw BusinessRuleException::make('An inspection reason is required for damaged or rejected quantities.');
            }
            $line->update($step === 'receive'
                ? ['received_quantity' => $row['quantity']]
                : ['accepted_quantity' => $row['quantity'], 'inspection_notes' => $row['notes'] ?? null]);
        }
        $stamp = $step === 'receive' ? 'received' : 'inspected';
        $return->update(['status' => $stamp, $stamp.'_by' => $user->id, $stamp.'_at' => now()]);
    }

    private function accept(SalesReturn $return, SalesInvoice $invoice, User $user): void
    {
        $journalLines = [];
        $accepted = '0.0000';
        foreach ($return->lines as $line) {
            if (bccomp($line->accepted_quantity, '0', 4) <= 0) {
                continue;
            }
            $original = $line->invoiceLine;
            $accepted = bcadd($accepted, $line->accepted_quantity, 4);
            $previous = SalesReturnLine::query()->where('sales_invoice_line_id', $original->id)
                ->whereHas('salesReturn', fn ($q) => $q->whereIn('status', ['accepted', 'credited', 'posted']))->get();
            $beforeQuantity = $previous->reduce(fn (string $sum, $row): string => bcadd($sum, $row->accepted_quantity, 4), '0.0000');
            $afterQuantity = bcadd($beforeQuantity, $line->accepted_quantity, 4);
            if (bccomp($afterQuantity, $original->quantity, 4) > 0) {
                throw BusinessRuleException::make('Accepted returns exceed the invoiced quantity.');
            }
            $amounts = [];
            foreach (['subtotal' => 'line_subtotal', 'tax' => 'line_tax', 'wht' => 'line_wht_tax'] as $field => $source) {
                $beforeAmount = $previous->reduce(fn (string $sum, $row): string => bcadd($sum, (string) $row->$field, 4), '0.0000');
                $cumulative = bcdiv(bcmul((string) $original->$source, $afterQuantity, 8), (string) $original->quantity, 4);
                $amounts[$field] = bcsub($cumulative, $beforeAmount, 4);
            }
            $amounts['total'] = bcsub(bcadd($amounts['subtotal'], $amounts['tax'], 4), $amounts['wht'], 4);
            $amounts['unit_cost'] = (string) $original->unit_cost;
            $amounts['cost'] = bcmul($line->accepted_quantity, $amounts['unit_cost'], 4);
            $line->update($amounts);
            if (bccomp($amounts['cost'], '0', 4) > 0) {
                if (! $original->inventory_account_id || ! $original->cogs_account_id) {
                    throw BusinessRuleException::make('Original invoice inventory and COGS account snapshots are required.');
                }
                $journalLines[] = new JournalLineData($original->inventory_account_id, $amounts['cost'], '0.0000', 'Return inventory '.$return->number);
                $journalLines[] = new JournalLineData($original->cogs_account_id, '0.0000', $amounts['cost'], 'Return cost reversal '.$return->number);
            }
            $this->stock->record(new StockMovementData(
                itemId: $original->item_id, warehouseId: $return->warehouse_id, quantity: $line->accepted_quantity,
                type: StockMovementType::Return, occurredAt: now(), reference: $return->number,
                notes: 'Accepted sales return for '.$invoice->number, sourceType: SalesReturnLine::class,
                sourceId: $line->id, unitCost: $amounts['unit_cost'],
            ));
        }
        if (bccomp($accepted, '0', 4) === 0) {
            $return->update(['status' => 'rejected', 'rejected_by' => $user->id, 'rejected_at' => now(), 'rejection_reason' => 'No goods passed quality inspection.']);

            return;
        }
        $journal = $journalLines ? $this->journals->post(new JournalEntryData(
            entryDate: now(), description: 'Sales return inventory '.$return->number, lines: $journalLines,
            reference: $invoice->number, currencyCode: $invoice->currency_code,
        ), ['source_type' => SalesReturn::class, 'source_id' => $return->id, 'user_id' => $user->id]) : null;
        $return->update(['status' => 'accepted', 'accepted_by' => $user->id, 'accepted_at' => now(), 'inventory_journal_id' => $journal?->id]);
    }

    private function credit(SalesReturn $return, SalesInvoice $invoice, User $user): void
    {
        $totals = [];
        foreach (['subtotal', 'tax', 'wht', 'total'] as $field) {
            $totals[$field] = $return->lines->reduce(fn (string $sum, $line): string => bcadd($sum, (string) $line->$field, 4), '0.0000');
        }
        SalesCreditNote::create([
            'number' => $this->sequences->next('sales_credit_note'), 'sales_return_id' => $return->id,
            'sales_invoice_id' => $invoice->id, 'credit_date' => today(), 'currency_code' => $invoice->currency_code,
            'status' => 'draft', 'created_by' => $user->id, ...$totals,
        ]);
        $return->update(['status' => 'credited']);
    }

    private function post(SalesReturn $return, SalesInvoice $invoice, User $user): void
    {
        $note = $return->creditNote()->lockForUpdate()->firstOrFail();
        if ($note->status !== 'draft') {
            throw BusinessRuleException::make('This credit note has already been posted.');
        }
        $returnsAccount = $this->accounts->resolve(SystemAccountRole::SalesReturns);
        if (! $returnsAccount) {
            throw BusinessRuleException::make('Map the Sales Returns system account before posting.');
        }
        $originalLines = $invoice->journalEntry->lines;
        $ar = $originalLines->first(fn ($line) => $line->customer_id === $invoice->customer_id && bccomp((string) $line->debit, '0', 4) > 0);
        if (! $ar) {
            throw BusinessRuleException::make('Original customer receivable journal line was not found.');
        }
        $lines = [];
        if (bccomp($note->subtotal, '0', 4) > 0) {
            $lines[] = new JournalLineData($returnsAccount->id, $note->subtotal, '0.0000', 'Sales return '.$note->number);
        }
        if (bccomp($note->total, '0', 4) > 0) {
            $lines[] = new JournalLineData($ar->account_id, '0.0000', $note->total, 'Customer credit '.$note->number, customerId: $invoice->customer_id);
        }
        foreach ($return->lines as $line) {
            if (bccomp($line->tax, '0', 4) > 0) {
                $taxAccount = $line->invoiceLine->tax_account_id;
                if (! $taxAccount || ! $originalLines->contains('account_id', $taxAccount)) {
                    throw BusinessRuleException::make('Original output tax account snapshot is required.');
                }
                $lines[] = new JournalLineData($taxAccount, $line->tax, '0.0000', 'Returned output tax '.$note->number);
            }
        }
        if (bccomp($note->wht, '0', 4) > 0) {
            $wht = $originalLines->first(fn ($line) => str_starts_with((string) $line->memo, 'WHT withheld') && bccomp((string) $line->debit, '0', 4) > 0);
            if (! $wht) {
                throw BusinessRuleException::make('Original withholding tax journal line was not found.');
            }
            $lines[] = new JournalLineData($wht->account_id, '0.0000', $note->wht, 'Returned WHT '.$note->number);
        }
        if ($lines === []) {
            throw BusinessRuleException::make('A zero-value credit note cannot be posted.');
        }
        $journal = $this->journals->post(new JournalEntryData(
            entryDate: today(),description: 'Sales credit note '.$note->number,lines: $lines,reference: $invoice->number,currencyCode: $note->currency_code,
        ),['source_type' => SalesCreditNote::class, 'source_id' => $note->id, 'user_id' => $user->id]);
        $note->update(['status' => 'posted', 'posted_at' => now(), 'posted_by' => $user->id, 'journal_entry_id' => $journal->id]);
        $return->update(['status' => 'posted']);
    }
}
