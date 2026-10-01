<?php

namespace Modules\Procurement\Exceptions;

use App\Exceptions\DomainException;
use Modules\Procurement\Models\SupplierInvoice;
use Modules\Procurement\Models\VendorPayment;

final class VendorPaymentException extends DomainException
{
    public static function zeroAmount(): self
    {
        return new self('Payment amount must be greater than zero.');
    }

    public static function allocationExceedsPayment(string $allocationTotal, string $paymentAmount): self
    {
        return new self(
            "Allocations ({$allocationTotal}) exceed payment amount ({$paymentAmount})."
        );
    }

    public static function notEditable(VendorPayment $payment): self
    {
        return new self(
            "Vendor payment {$payment->number} is {$payment->status->label()} and cannot be edited."
        );
    }

    public static function invalidTransition(VendorPayment $payment, string $action): self
    {
        return new self(
            "Cannot {$action} vendor payment {$payment->number} from status {$payment->status->label()}."
        );
    }

    public static function cannotSelfApprove(VendorPayment $payment): self
    {
        return new self(
            "You submitted vendor payment {$payment->number}, so you cannot approve it. ".
            'Ask another approver to review it.'
        );
    }

    public static function cannotDelete(VendorPayment $payment): self
    {
        return new self(
            "Only draft payments can be deleted. Cancel {$payment->number} instead."
        );
    }

    public static function missingSystemAccount(string $role): self
    {
        return new self(
            "System account [{$role}] is not configured. Set it at /system-accounts."
        );
    }

    public static function bankAccountMissingGL(VendorPayment $payment): self
    {
        return new self(
            "The bank account linked to payment {$payment->number} has no GL account configured."
        );
    }

    public static function vendorMismatch(VendorPayment $payment, SupplierInvoice $invoice): self
    {
        return new self(
            "Payment {$payment->number} is for vendor #{$payment->vendor_id} ".
            "but invoice {$invoice->number} belongs to vendor #{$invoice->vendor_id}."
        );
    }

    public static function invoiceNotPayable(SupplierInvoice $invoice): self
    {
        return new self(
            "Invoice {$invoice->number} is {$invoice->status->label()} and cannot be paid."
        );
    }

    public static function overAllocation(SupplierInvoice $invoice, string $requested, string $outstanding): self
    {
        return new self(
            "Cannot allocate {$requested} to invoice {$invoice->number}: ".
            "its outstanding balance is {$outstanding}."
        );
    }

    public static function paymentOverAllocated(VendorPayment $payment, string $allocated): self
    {
        return new self(
            "Payment {$payment->number} has been over-allocated: ".
            "total allocations {$allocated} exceed payment amount {$payment->amount}."
        );
    }

    public static function alreadyReversed(VendorPayment $payment): self
    {
        return new self("Payment {$payment->number} has already been reversed.");
    }

    public static function cannotReverseWithoutJournal(VendorPayment $payment): self
    {
        return new self(
            "Payment {$payment->number} has no journal entry and cannot be reversed."
        );
    }
}
