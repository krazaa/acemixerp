<?php

declare(strict_types=1);

namespace Modules\Expense\Services;

use App\Contracts\JournalPoster;
use App\Contracts\SequenceGenerator;
use App\Contracts\SystemAccountManager;
use App\Data\JournalEntryData;
use App\Data\JournalLineData;
use App\Enums\PaymentMethod;
use App\Enums\SystemAccountRole;
use App\Exceptions\BusinessRuleException;
use App\Models\Account;
use App\Models\BankAccount;
use Illuminate\Support\Facades\DB;
use Modules\Procurement\Enums\VendorPaymentStatus;
use Modules\Procurement\Models\VendorInvoice;
use Modules\Procurement\Models\VendorPayment;

final class VendorInvoiceWorkflowService
{
    public function __construct(
        private readonly JournalPoster $journals,
        private readonly SystemAccountManager $systemAccounts,
        private readonly SequenceGenerator $sequences,
    ) {}

    public function submit(VendorInvoice $invoice, int $userId): VendorInvoice
    {
        return DB::transaction(function () use ($invoice, $userId): VendorInvoice {
            $locked = VendorInvoice::query()->lockForUpdate()->findOrFail($invoice->id);
            if ($locked->status !== 'draft') {
                throw BusinessRuleException::make('Only draft vendor invoices can be submitted.');
            }
            $locked->update(['status' => 'submitted', 'updated_by' => $userId]);

            return $locked->fresh();
        });
    }

    public function approve(VendorInvoice $invoice, int $userId): VendorInvoice
    {
        return DB::transaction(function () use ($invoice, $userId): VendorInvoice {
            $locked = VendorInvoice::query()->lockForUpdate()->findOrFail($invoice->id);
            if ($locked->status !== 'submitted') {
                throw BusinessRuleException::make('Only submitted vendor invoices can be approved.');
            }
            $locked->update(['status' => 'approved', 'approved_at' => now(), 'approved_by' => $userId, 'updated_by' => $userId]);

            return $locked->fresh();
        });
    }

    public function ownerReview(VendorInvoice $invoice, int $userId, ?string $rejectionReason = null): VendorInvoice
    {
        return DB::transaction(function () use ($invoice, $userId, $rejectionReason): VendorInvoice {
            $locked = VendorInvoice::query()->lockForUpdate()->findOrFail($invoice->id);
            if ($locked->status !== 'approved' || $locked->owner_approved_at !== null) {
                throw BusinessRuleException::make('Only invoices awaiting owner review can be reviewed.');
            }
            $locked->update($rejectionReason !== null
                ? ['status' => 'rejected', 'rejection_reason' => $rejectionReason, 'updated_by' => $userId]
                : ['owner_approved_at' => now(), 'owner_approved_by' => $userId, 'updated_by' => $userId]);

            return $locked->fresh();
        });
    }

    public function post(VendorInvoice $invoice, int $userId): VendorInvoice
    {
        if ($invoice->status !== 'approved') {
            throw BusinessRuleException::make('Only approved vendor invoices can be posted to GL.');
        }

        return DB::transaction(function () use ($invoice, $userId): VendorInvoice {
            $locked = VendorInvoice::query()->with(['lines', 'vendor'])->lockForUpdate()->findOrFail($invoice->id);
            if ($locked->status !== 'approved' || $locked->owner_approved_at === null) {
                throw BusinessRuleException::make('Owner approval is required before posting to GL.');
            }
            $line = $locked->lines->first();
            if ($line === null || $line->debit_account_id === null) {
                throw BusinessRuleException::make('Select a postable Cargo/Freight expense account before submitting the vendor invoice.');
            }

            $expenseAccount = Account::query()->active()->postable()->find($line->debit_account_id);
            $inputTax = $this->systemAccounts->resolve(SystemAccountRole::InputTax);
            $accountsPayable = $this->systemAccounts->resolve(SystemAccountRole::AccountsPayable);
            if ($expenseAccount === null) {
                throw BusinessRuleException::make('The selected Cargo/Freight expense account is not active and postable.');
            }
            if ($inputTax === null || $accountsPayable === null) {
                throw BusinessRuleException::make('Input Tax and Accounts Payable system accounts must be mapped before posting.');
            }

            $line->update(['input_tax_account_id' => $inputTax->id]);
            $journalLines = [new JournalLineData($expenseAccount->id, (string) $locked->subtotal, '0.0000', $this->lineMemo('Cargo/Freight', $locked))];
            if (bccomp((string) $locked->tax_total, '0.0000', 4) > 0) {
                $journalLines[] = new JournalLineData($inputTax->id, (string) $locked->tax_total, '0.0000', $this->lineMemo('Input tax', $locked));
            }
            if (bccomp((string) $locked->whttax_total, '0.0000', 4) > 0) {
                $withholdingPayable = $this->systemAccounts->resolve(SystemAccountRole::WithholdingTaxPayable);
                if ($withholdingPayable === null) {
                    throw BusinessRuleException::make('Map a Withholding Tax Payable system account before posting an invoice with WHT.');
                }
                $journalLines[] = new JournalLineData($withholdingPayable->id, '0.0000', (string) $locked->whttax_total, $this->lineMemo('WHT payable', $locked));
            }
            $journalLines[] = new JournalLineData($accountsPayable->id, '0.0000', (string) $locked->total, $this->lineMemo('Accounts payable', $locked), vendorId: $locked->vendor_id);

            $journal = $this->journals->post(new JournalEntryData(
                entryDate: $locked->invoice_date,
                description: "Vendor Invoice {$locked->number}",
                lines: $journalLines,
                reference: $locked->vendor_invoice_number,
            ), ['source_type' => VendorInvoice::class, 'source_id' => $locked->id, 'user_id' => $userId]);

            $locked->update(['status' => 'posted', 'posted_at' => now(), 'posted_by' => $userId, 'journal_entry_id' => $journal->id, 'updated_by' => $userId]);

            return $locked->fresh('journalEntry');
        });
    }

    public function pay(VendorInvoice $invoice, string $amount, string $method, ?int $bankAccountId, ?string $reference, int $userId): VendorPayment
    {
        return DB::transaction(function () use ($invoice, $amount, $method, $bankAccountId, $reference, $userId): VendorPayment {
            $locked = VendorInvoice::query()->lockForUpdate()->findOrFail($invoice->id);
            if (! in_array($locked->status, ['posted', 'partially_paid'], true)) {
                throw BusinessRuleException::make('Only posted vendor invoices can be paid.');
            }
            $outstanding = bcsub((string) $locked->total, (string) $locked->paid_amount, 4);
            if (bccomp($amount, '0.0000', 4) <= 0 || bccomp($amount, $outstanding, 4) > 0) {
                throw BusinessRuleException::make("Payment must be greater than zero and not exceed the outstanding balance of {$outstanding}.");
            }

            $paymentMethod = PaymentMethod::from($method);
            $creditAccountId = null;
            if ($paymentMethod === PaymentMethod::Cash) {
                $creditAccountId = $this->systemAccounts->resolve(SystemAccountRole::CashOnHand)?->id;
            } else {
                $bank = BankAccount::query()->active()->find($bankAccountId);
                $creditAccountId = $bank?->gl_account_id;
            }
            if ($creditAccountId === null) {
                throw BusinessRuleException::make('Select an active bank account linked to a GL account, or use Cash.');
            }
            $apAccount = $this->systemAccounts->resolve(SystemAccountRole::AccountsPayable);
            if ($apAccount === null) {
                throw BusinessRuleException::make('Accounts Payable system account must be mapped before payment.');
            }

            $payment = VendorPayment::query()->create([
                'number' => $this->sequences->next('vendor_payment', (int) now()->format('Y')),
                'vendor_id' => $locked->vendor_id,
                'payment_date' => now()->toDateString(),
                'currency_code' => 'PKR',
                'amount' => $amount,
                'allocated_amount' => $amount,
                'payment_method' => $paymentMethod,
                'bank_account_id' => $bankAccountId,
                'reference' => $reference,
                'status' => VendorPaymentStatus::Posted,
                'posted_at' => now(),
                'posted_by' => $userId,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
            $journal = $this->journals->post(new JournalEntryData(
                entryDate: now(),
                description: "Vendor Payment {$payment->number} — {$locked->number}",
                lines: [
                    new JournalLineData($apAccount->id, $amount, '0.0000', "AP settlement — {$locked->number}", vendorId: $locked->vendor_id),
                    new JournalLineData($creditAccountId, '0.0000', $amount, 'Vendor payment'),
                ],
                reference: $reference ?: $payment->number,
                currencyCode: 'PKR',
            ), ['source_type' => VendorPayment::class, 'source_id' => $payment->id, 'user_id' => $userId]);
            $payment->update(['journal_entry_id' => $journal->id]);
            $payment->allocations()->create(['allocatable_type' => VendorInvoice::class, 'allocatable_id' => $locked->id, 'amount' => $amount, 'allocated_by' => $userId]);

            $paidAmount = bcadd((string) $locked->paid_amount, $amount, 4);
            $locked->update(['paid_amount' => $paidAmount, 'status' => bccomp($paidAmount, (string) $locked->total, 4) >= 0 ? 'paid' : 'partially_paid', 'updated_by' => $userId]);

            return $payment->fresh('journalEntry');
        });
    }

    private function lineMemo(string $entryType, VendorInvoice $invoice): string
    {
        return "{$entryType} — {$invoice->number} · {$invoice->vendor_invoice_number} · {$invoice->vendor?->name}";
    }
}
