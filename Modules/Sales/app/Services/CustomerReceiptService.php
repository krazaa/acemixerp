<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use App\Contracts\JournalPoster;
use App\Contracts\SequenceGenerator;
use App\Contracts\SystemAccountManager;
use App\Data\JournalEntryData;
use App\Data\JournalLineData;
use App\Enums\SystemAccountRole;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Sales\Contracts\CustomerReceiptManager;
use Modules\Sales\Data\CustomerReceiptData;
use Modules\Sales\Data\ReceiptAllocationData;
use Modules\Sales\Enums\CustomerReceiptStatus;
use Modules\Sales\Enums\SalesInvoiceStatus;
use Modules\Sales\Exceptions\CustomerReceiptException;
use Modules\Sales\Models\CustomerReceipt;
use Modules\Sales\Models\SalesInvoice;

final class CustomerReceiptService implements CustomerReceiptManager
{
    public function __construct(
        private readonly SequenceGenerator $sequences,
        private readonly JournalPoster $poster,
        private readonly SystemAccountManager $systemAccounts,
    ) {}

    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return CustomerReceipt::query()
            ->with(['customer:id,code,name', 'bankAccount:id,name', 'creator:id,name'])
            ->search($filters['search'] ?? null)
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['customer_id'] ?? null, fn ($q, $c) => $q->where('customer_id', $c))
            ->when($filters['method'] ?? null, fn ($q, $m) => $q->where('payment_method', $m))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->whereDate('receipt_date', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->whereDate('receipt_date', '<=', $d))
            ->orderByDesc('receipt_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(CustomerReceiptData $data, int $userId): CustomerReceipt
    {
        if (bccomp($data->amount, '0', 4) <= 0) {
            throw CustomerReceiptException::zeroAmount();
        }

        if (bccomp($data->allocationTotal(), $data->amount, 4) > 0) {
            throw CustomerReceiptException::allocationExceedsPayment(
                $data->allocationTotal(),
                $data->amount,
            );
        }

        return DB::transaction(function () use ($data, $userId) {
            $receipt = CustomerReceipt::query()->create([
                'number' => $this->sequences->next('customer_receipt', (int) $data->receiptDate->format('Y')),
                'customer_id' => $data->customerId,
                'receipt_date' => $data->receiptDate,
                'currency_code' => $data->currencyCode,
                'amount' => $data->amount,
                'payment_method' => $data->paymentMethod,
                'bank_account_id' => $data->bankAccountId,
                'reference' => $data->reference,
                'notes' => $data->notes,
                'status' => CustomerReceiptStatus::Draft,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            if ($data->allocations !== []) {
                $this->recordPlannedAllocations($receipt, $data->allocations, $userId);
            }

            return $receipt->fresh(['allocations', 'customer']);
        });
    }

    public function update(CustomerReceipt $receipt, CustomerReceiptData $data, int $userId): CustomerReceipt
    {
        if (! $receipt->status->isEditable()) {
            throw CustomerReceiptException::notEditable($receipt);
        }

        if (bccomp($data->allocationTotal(), $data->amount, 4) > 0) {
            throw CustomerReceiptException::allocationExceedsPayment(
                $data->allocationTotal(),
                $data->amount,
            );
        }

        return DB::transaction(function () use ($receipt, $data, $userId) {
            $this->discardPlannedAllocations($receipt);

            $receipt->fill([
                'customer_id' => $data->customerId,
                'receipt_date' => $data->receiptDate,
                'currency_code' => $data->currencyCode,
                'amount' => $data->amount,
                'payment_method' => $data->paymentMethod,
                'bank_account_id' => $data->bankAccountId,
                'reference' => $data->reference,
                'notes' => $data->notes,
                'updated_by' => $userId,
            ])->save();

            if ($data->allocations !== []) {
                $this->recordPlannedAllocations($receipt, $data->allocations, $userId);
            }

            return $receipt->fresh(['allocations']);
        });
    }

    public function submit(CustomerReceipt $receipt, int $userId): CustomerReceipt
    {
        if (! $receipt->status->canSubmit()) {
            throw CustomerReceiptException::invalidTransition($receipt, 'submit');
        }

        $receipt->forceFill([
            'status' => CustomerReceiptStatus::Submitted,
            'submitted_at' => now(),
            'submitted_by' => $userId,
            'updated_by' => $userId,
        ])->save();

        return $receipt->fresh();
    }

    public function approve(CustomerReceipt $receipt, int $userId): CustomerReceipt
    {
        if (! $receipt->status->canApprove()) {
            throw CustomerReceiptException::invalidTransition($receipt, 'approve');
        }

        if ($receipt->submitted_by === $userId) {
            throw CustomerReceiptException::cannotSelfApprove($receipt);
        }

        $receipt->forceFill([
            'status' => CustomerReceiptStatus::Approved,
            'approved_at' => now(),
            'approved_by' => $userId,
            'updated_by' => $userId,
        ])->save();

        return $receipt->fresh();
    }

    public function reject(CustomerReceipt $receipt, int $userId, string $reason): CustomerReceipt
    {
        if ($receipt->status !== CustomerReceiptStatus::Submitted) {
            throw CustomerReceiptException::invalidTransition($receipt, 'reject');
        }

        $receipt->forceFill([
            'status' => CustomerReceiptStatus::Rejected,
            'notes' => trim(($receipt->notes ?? '')."\nRejected: ".$reason),
            'updated_by' => $userId,
        ])->save();

        return $receipt->fresh();
    }

    /**
     * Post the receipt to GL: Debit Bank/Cash, Credit AR.
     */
    public function post(CustomerReceipt $receipt, int $userId): CustomerReceipt
    {
        if (! $receipt->status->canPost()) {
            throw CustomerReceiptException::invalidTransition($receipt, 'post');
        }

        return DB::transaction(function () use ($receipt, $userId) {
            /** @var CustomerReceipt $locked */
            $locked = CustomerReceipt::query()
                ->with(['customer', 'allocations'])
                ->lockForUpdate()
                ->findOrFail($receipt->id);

            // Determine debit account (source of cash)
            if ($locked->payment_method->value === 'cash' || ! $locked->bank_account_id) {
                $debitAccount = $this->systemAccounts->resolve(SystemAccountRole::CashOnHand);
                if (! $debitAccount) {
                    throw CustomerReceiptException::missingSystemAccount('cash_on_hand');
                }
                $debitAccountId = $debitAccount->id;
            } else {
                $locked->loadMissing('bankAccount');
                $bank = $locked->bankAccount;
                if (! $bank?->gl_account_id) {
                    throw CustomerReceiptException::bankAccountMissingGL($locked);
                }
                $debitAccountId = $bank->gl_account_id;
            }

            $arAccount = $this->systemAccounts->resolve(SystemAccountRole::AccountsReceivable);
            if (! $arAccount) {
                throw CustomerReceiptException::missingSystemAccount('accounts_receivable');
            }

            // Invoice payment balances become effective only as part of this
            // atomic posting transaction; draft allocations are merely plans.
            $this->applyPlannedAllocations($locked);

            $journal = $this->poster->post(new JournalEntryData(
                entryDate: $locked->receipt_date,
                description: "Customer Receipt {$locked->number} — {$locked->customer->name}",
                lines: [
                    new JournalLineData(
                        accountId: $debitAccountId,
                        debit: (string) $locked->amount,
                        credit: '0.0000',
                        memo: 'Receipt via '.$locked->payment_method->label(),
                    ),
                    new JournalLineData(
                        accountId: $arAccount->id,
                        debit: '0.0000',
                        credit: (string) $locked->amount,
                        memo: "AR settlement — {$locked->number}",
                        customerId: $locked->customer_id,
                    ),
                ],
                reference: $locked->reference,
            ), [
                'source_type' => CustomerReceipt::class,
                'source_id' => $locked->id,
                'user_id' => $userId,
            ]);

            $locked->forceFill([
                'status' => CustomerReceiptStatus::Posted,
                'posted_at' => now(),
                'posted_by' => $userId,
                'journal_entry_id' => $journal->id,
                'updated_by' => $userId,
            ])->save();

            return $locked->fresh(['journalEntry', 'allocations']);
        });
    }

    public function cancel(CustomerReceipt $receipt, int $userId, ?string $reason = null): CustomerReceipt
    {
        if (! $receipt->status->canCancel()) {
            throw CustomerReceiptException::invalidTransition($receipt, 'cancel');
        }

        return DB::transaction(function () use ($receipt, $userId, $reason) {
            $this->discardPlannedAllocations($receipt);

            $receipt->forceFill([
                'status' => CustomerReceiptStatus::Cancelled,
                'notes' => $reason
                    ? trim(($receipt->notes ?? '')."\nCancelled: ".$reason)
                    : $receipt->notes,
                'updated_by' => $userId,
            ])->save();

            return $receipt->fresh();
        });
    }

    public function reverse(CustomerReceipt $receipt, int $userId, string $reason): CustomerReceipt
    {
        if (! $receipt->status->canReverse()) {
            throw CustomerReceiptException::invalidTransition($receipt, 'reverse');
        }

        return DB::transaction(function () use ($receipt, $userId, $reason) {
            /** @var CustomerReceipt $original */
            $original = CustomerReceipt::query()->lockForUpdate()->findOrFail($receipt->id);

            if ($original->reversed_by_id) {
                throw CustomerReceiptException::alreadyReversed($original);
            }

            if (! $original->journal_entry_id) {
                throw CustomerReceiptException::invalidTransition($original, 'reverse');
            }

            $reversalJournal = $this->poster->reverse(
                $original->journalEntry,
                $userId,
                "Reversal of {$original->number}: {$reason}",
            );

            $this->releasePostedAllocations($original);

            $reversal = CustomerReceipt::query()->create([
                'number' => $this->sequences->next('customer_receipt', (int) now()->format('Y')),
                'customer_id' => $original->customer_id,
                'receipt_date' => now(),
                'currency_code' => $original->currency_code,
                'exchange_rate' => $original->exchange_rate,
                'amount' => bcmul((string) $original->amount, '-1', 4),
                'allocated_amount' => '0.0000',
                'payment_method' => $original->payment_method,
                'bank_account_id' => $original->bank_account_id,
                'reference' => $original->reference,
                'notes' => "Reversal of {$original->number}: {$reason}",
                'status' => CustomerReceiptStatus::Draft,
                'reverses_id' => $original->id,
                'journal_entry_id' => $reversalJournal->id,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $original->forceFill([
                'status' => CustomerReceiptStatus::Reversed,
                'reversed_by_id' => $reversal->id,
                'updated_by' => $userId,
            ])->save();

            return $reversal->fresh(['reverses']);
        });
    }

    public function delete(CustomerReceipt $receipt): void
    {
        if ($receipt->status !== CustomerReceiptStatus::Draft) {
            throw CustomerReceiptException::cannotDelete($receipt);
        }
        DB::transaction(function () use ($receipt) {
            $this->discardPlannedAllocations($receipt);
            $receipt->delete();
        });
    }

    // ─── Allocation ──────────────────────────────────────────────────

    /** @param ReceiptAllocationData[] $allocations */
    private function recordPlannedAllocations(CustomerReceipt $receipt, array $allocations, int $userId): void
    {
        $allocated = '0.0000';

        foreach ($allocations as $alloc) {
            /** @var SalesInvoice $invoice */
            $invoice = SalesInvoice::query()->findOrFail($alloc->salesInvoiceId);

            if ($invoice->customer_id !== $receipt->customer_id) {
                throw CustomerReceiptException::customerMismatch($receipt, $invoice);
            }

            if (! $invoice->status->acceptsPayment()) {
                throw CustomerReceiptException::invoiceNotPayable($invoice);
            }

            $receipt->allocations()->create([
                'payment_type' => CustomerReceipt::class,
                'payment_id' => $receipt->id,
                'allocatable_type' => SalesInvoice::class,
                'allocatable_id' => $invoice->id,
                'amount' => $alloc->amount,
                'allocated_by' => $userId,
            ]);

            $allocated = bcadd($allocated, $alloc->amount, 4);
        }

        $receipt->forceFill(['allocated_amount' => $allocated])->save();
    }

    private function applyPlannedAllocations(CustomerReceipt $receipt): void
    {
        $allocated = '0.0000';

        foreach ($receipt->allocations as $allocation) {
            /** @var SalesInvoice $invoice */
            $invoice = SalesInvoice::query()->lockForUpdate()->findOrFail($allocation->allocatable_id);

            if ($invoice->customer_id !== $receipt->customer_id) {
                throw CustomerReceiptException::customerMismatch($receipt, $invoice);
            }

            if (! $invoice->status->acceptsPayment()) {
                throw CustomerReceiptException::invoiceNotPayable($invoice);
            }

            $outstanding = $invoice->outstanding();
            if (bccomp((string) $allocation->amount, $outstanding, 4) > 0) {
                throw CustomerReceiptException::overAllocation($invoice, (string) $allocation->amount, $outstanding);
            }

            $this->syncInvoicePaymentStatus(
                $invoice,
                bcadd((string) $invoice->paid_amount, (string) $allocation->amount, 4),
            );
            $allocated = bcadd($allocated, (string) $allocation->amount, 4);
        }

        if (bccomp($allocated, (string) $receipt->amount, 4) > 0) {
            throw CustomerReceiptException::allocationExceedsPayment($allocated, (string) $receipt->amount);
        }

        $receipt->forceFill(['allocated_amount' => $allocated])->save();
    }

    private function discardPlannedAllocations(CustomerReceipt $receipt): void
    {
        $receipt->allocations()->delete();
        $receipt->forceFill(['allocated_amount' => '0.0000'])->save();
    }

    private function releasePostedAllocations(CustomerReceipt $receipt): void
    {
        foreach ($receipt->allocations()->with('allocatable')->get() as $allocation) {
            $invoice = $allocation->allocatable;
            if (! $invoice instanceof SalesInvoice) {
                continue;
            }

            $newPaid = bcsub((string) $invoice->paid_amount, (string) $allocation->amount, 4);
            $this->syncInvoicePaymentStatus($invoice, bccomp($newPaid, '0', 4) < 0 ? '0.0000' : $newPaid);

            $allocation->delete();
        }

        $receipt->forceFill(['allocated_amount' => '0.0000'])->save();
    }

    private function syncInvoicePaymentStatus(SalesInvoice $invoice, string $paidAmount): void
    {
        $invoice->forceFill([
            'paid_amount' => $paidAmount,
            'status' => bccomp($paidAmount, (string) $invoice->total, 4) >= 0
                ? SalesInvoiceStatus::Paid
                : (bccomp($paidAmount, '0', 4) > 0
                    ? SalesInvoiceStatus::PartiallyPaid
                    : SalesInvoiceStatus::Posted),
        ])->save();
    }
}
