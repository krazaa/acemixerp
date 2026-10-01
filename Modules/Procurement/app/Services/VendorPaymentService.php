<?php

namespace Modules\Procurement\Services;

use App\Contracts\JournalPoster;
use App\Contracts\SequenceGenerator;
use App\Contracts\SystemAccountManager;
use App\Data\JournalEntryData;
use App\Data\JournalLineData;
use App\Enums\SystemAccountRole;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Procurement\Contracts\VendorPaymentManager;
use Modules\Procurement\Data\VendorPaymentData;
use Modules\Procurement\Enums\VendorPaymentStatus;
use Modules\Procurement\Exceptions\VendorPaymentException;
use Modules\Procurement\Models\VendorPayment;

final class VendorPaymentService implements VendorPaymentManager
{
    public function __construct(
        private readonly SequenceGenerator $sequences,
        private readonly JournalPoster $poster,
        private readonly SystemAccountManager $systemAccounts,
        private readonly PaymentAllocationService $allocator,
    ) {}

    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return VendorPayment::query()
            ->with(['vendor:id,code,name', 'bankAccount:id,name', 'creator:id,name'])
            ->search($filters['search'] ?? null)
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['vendor_id'] ?? null, fn ($q, $v) => $q->where('vendor_id', $v))
            ->when($filters['method'] ?? null, fn ($q, $m) => $q->where('payment_method', $m))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->whereDate('payment_date', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->whereDate('payment_date', '<=', $d))
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(VendorPaymentData $data, int $userId): VendorPayment
    {
        if (bccomp($data->amount, '0', 4) <= 0) {
            throw VendorPaymentException::zeroAmount();
        }

        if (bccomp($data->allocationTotal(), $data->amount, 4) > 0) {
            throw VendorPaymentException::allocationExceedsPayment(
                $data->allocationTotal(),
                $data->amount,
            );
        }

        return DB::transaction(function () use ($data, $userId) {
            $payment = VendorPayment::query()->create([
                'number' => $this->sequences->next('vendor_payment', (int) $data->paymentDate->format('Y')),
                'vendor_id' => $data->vendorId,
                'payment_date' => $data->paymentDate,
                'billing_month' => $data->billingMonth,
                'currency_code' => $data->currencyCode,
                'amount' => $data->amount,
                'payment_method' => $data->paymentMethod,
                'bank_account_id' => $data->bankAccountId,
                'reference' => $data->reference,
                'notes' => $data->notes,
                'status' => VendorPaymentStatus::Draft,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            if ($data->allocations !== []) {
                $this->allocator->allocate($payment, $data->allocations, $userId);
            }

            return $payment->fresh(['allocations', 'vendor']);
        });
    }

    public function update(VendorPayment $payment, VendorPaymentData $data, int $userId): VendorPayment
    {
        if (! $payment->status->isEditable()) {
            throw VendorPaymentException::notEditable($payment);
        }

        if (bccomp($data->amount, '0', 4) <= 0) {
            throw VendorPaymentException::zeroAmount();
        }

        return DB::transaction(function () use ($payment, $data, $userId) {
            // Deallocate old first — the amount may be changing.
            $this->allocator->deallocate($payment);

            $payment->fill([
                'vendor_id' => $data->vendorId,
                'payment_date' => $data->paymentDate,
                'billing_month' => $data->billingMonth,
                'currency_code' => $data->currencyCode,
                'amount' => $data->amount,
                'payment_method' => $data->paymentMethod,
                'bank_account_id' => $data->bankAccountId,
                'reference' => $data->reference,
                'notes' => $data->notes,
                'updated_by' => $userId,
            ])->save();

            if ($data->allocations !== []) {
                $this->allocator->allocate($payment, $data->allocations, $userId);
            }

            return $payment->fresh(['allocations', 'vendor']);
        });
    }

    public function submit(VendorPayment $payment, int $userId): VendorPayment
    {
        if (! $payment->status->canSubmit()) {
            throw VendorPaymentException::invalidTransition($payment, 'submit');
        }

        $payment->forceFill([
            'status' => VendorPaymentStatus::Submitted,
            'submitted_at' => now(),
            'submitted_by' => $userId,
            'updated_by' => $userId,
        ])->save();

        return $payment->fresh();
    }

    public function approve(VendorPayment $payment, int $userId): VendorPayment
    {
        if (! $payment->status->canApprove()) {
            throw VendorPaymentException::invalidTransition($payment, 'approve');
        }

        // Segregation of duties: the submitter cannot approve their own payment.
        if ($payment->submitted_by === $userId) {
            throw VendorPaymentException::cannotSelfApprove($payment);
        }

        $payment->forceFill([
            'status' => VendorPaymentStatus::Approved,
            'approved_at' => now(),
            'approved_by' => $userId,
            'updated_by' => $userId,
        ])->save();

        return $payment->fresh();
    }

    public function reject(VendorPayment $payment, int $userId, string $reason): VendorPayment
    {
        if ($payment->status !== VendorPaymentStatus::Submitted) {
            throw VendorPaymentException::invalidTransition($payment, 'reject');
        }

        $payment->forceFill([
            'status' => VendorPaymentStatus::Rejected,
            'notes' => trim(($payment->notes ?? '')."\nRejected: ".$reason),
            'updated_by' => $userId,
        ])->save();

        return $payment->fresh();
    }

    public function post(VendorPayment $payment, int $userId): VendorPayment
    {
        if (! $payment->status->canPost()) {
            throw VendorPaymentException::invalidTransition($payment, 'post');
        }

        return DB::transaction(function () use ($payment, $userId) {
            /** @var VendorPayment $locked */
            $locked = VendorPayment::query()->lockForUpdate()->findOrFail($payment->id);

            // ── Determine credit account (source of funds) ─────────
            if ($locked->payment_method->value === 'cash' || ! $locked->bank_account_id) {
                $creditAccount = $this->systemAccounts->resolve(SystemAccountRole::CashOnHand);
                if (! $creditAccount) {
                    throw VendorPaymentException::missingSystemAccount('cash_on_hand');
                }
                $creditAccountId = $creditAccount->id;
            } else {
                $locked->loadMissing('bankAccount');
                $bank = $locked->bankAccount;
                if (! $bank?->gl_account_id) {
                    throw VendorPaymentException::bankAccountMissingGL($locked);
                }
                $creditAccountId = $bank->gl_account_id;
            }

            // ── AP debit ───────────────────────────────────────────
            $apAccount = $this->systemAccounts->resolve(SystemAccountRole::AccountsPayable);
            if (! $apAccount) {
                throw VendorPaymentException::missingSystemAccount('accounts_payable');
            }

            // ── Build and post the journal entry ───────────────────
            $journal = $this->poster->post(new JournalEntryData(
                entryDate: $locked->payment_date,
                description: "Vendor Payment {$locked->number} — {$locked->vendor->name}",
                lines: [
                    new JournalLineData(
                        accountId: $apAccount->id,
                        debit: (string) $locked->amount,
                        credit: '0.0000',
                        memo: "AP settlement — {$locked->number}",
                        vendorId: $locked->vendor_id,
                    ),
                    new JournalLineData(
                        accountId: $creditAccountId,
                        debit: '0.0000',
                        credit: (string) $locked->amount,
                        memo: 'Payment via '.$locked->payment_method->label(),
                    ),
                ],
                reference: $locked->reference,
            ), [
                'source_type' => VendorPayment::class,
                'source_id' => $locked->id,
                'user_id' => $userId,
            ]);

            $locked->forceFill([
                'status' => VendorPaymentStatus::Posted,
                'posted_at' => now(),
                'posted_by' => $userId,
                'journal_entry_id' => $journal->id,
                'updated_by' => $userId,
            ])->save();

            return $locked->fresh(['journalEntry', 'allocations', 'vendor']);
        });
    }

    public function cancel(VendorPayment $payment, int $userId, ?string $reason = null): VendorPayment
    {
        if (! $payment->status->canCancel()) {
            throw VendorPaymentException::invalidTransition($payment, 'cancel');
        }

        return DB::transaction(function () use ($payment, $userId, $reason) {
            // Deallocate first so the affected invoices are released.
            $this->allocator->deallocate($payment);

            $payment->forceFill([
                'status' => VendorPaymentStatus::Cancelled,
                'notes' => $reason
                    ? trim(($payment->notes ?? '')."\nCancelled: ".$reason)
                    : $payment->notes,
                'updated_by' => $userId,
            ])->save();

            return $payment->fresh();
        });
    }

    public function delete(VendorPayment $payment): void
    {
        if ($payment->status !== VendorPaymentStatus::Draft) {
            throw VendorPaymentException::cannotDelete($payment);
        }

        DB::transaction(function () use ($payment) {
            $this->allocator->deallocate($payment);
            $payment->delete();
        });
    }

    /**
     * Reverse a posted payment.
     *
     * Two operations:
     *  1. Reverse the underlying journal entry (debit/credit swapped, linked).
     *  2. Deallocate every allocation so the settled invoices return to Posted.
     *
     * The original payment document is marked `reversed` and linked to a
     * mirror `reversal` payment that stays in Draft until an operator
     * submits/approves/posts it — this keeps the two-step audit trail intact.
     */
    public function reverse(VendorPayment $payment, int $userId, string $reason): VendorPayment
    {
        if (! $payment->status->canReverse()) {
            throw VendorPaymentException::invalidTransition($payment, 'reverse');
        }

        return DB::transaction(function () use ($payment, $userId, $reason) {
            /** @var VendorPayment $original */
            $original = VendorPayment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($original->reversed_by_id) {
                throw VendorPaymentException::alreadyReversed($original);
            }

            if (! $original->journal_entry_id) {
                throw VendorPaymentException::cannotReverseWithoutJournal($original);
            }

            // Reverse the GL entry.
            $reversalJournal = $this->poster->reverse(
                $original->journalEntry,
                $userId,
                "Reversal of {$original->number}: {$reason}",
            );

            // Release the invoice allocations.
            $this->allocator->deallocate($original);

            // Create the mirror payment document for traceability.
            $reversal = VendorPayment::query()->create([
                'number' => $this->sequences->next('vendor_payment', (int) now()->format('Y')),
                'vendor_id' => $original->vendor_id,
                'payment_date' => now()->toDateString(),
                'billing_month' => $original->billing_month ?? now()->format('Y-m'),
                'currency_code' => $original->currency_code,
                'exchange_rate' => $original->exchange_rate,
                'amount' => $original->amount,
                'allocated_amount' => '0.0000',
                'payment_method' => $original->payment_method,
                'bank_account_id' => $original->bank_account_id,
                'reference' => $original->reference,
                'notes' => "Reversal of {$original->number}: {$reason}",
                'status' => VendorPaymentStatus::Draft,
                'reverses_id' => $original->id,
                'journal_entry_id' => $reversalJournal->id,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $original->forceFill([
                'status' => VendorPaymentStatus::Reversed,
                'reversed_by_id' => $reversal->id,
                'updated_by' => $userId,
            ])->save();

            return $reversal->fresh(['vendor', 'reverses']);
        });
    }
}
