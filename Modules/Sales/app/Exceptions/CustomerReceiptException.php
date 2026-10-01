<?php

declare(strict_types=1);

namespace Modules\Sales\Exceptions;

use App\Exceptions\DomainException;
use Modules\Sales\Models\CustomerReceipt;
use Modules\Sales\Models\SalesInvoice;

final class CustomerReceiptException extends DomainException
{
    public static function zeroAmount(): self
    {
        return new self('Receipt amount must be greater than zero.');
    }

    public static function notEditable(CustomerReceipt $r): self
    {
        return new self("Receipt {$r->number} is {$r->status->label()} and cannot be edited.");
    }

    public static function invalidTransition(CustomerReceipt $r, string $action): self
    {
        return new self("Cannot {$action} receipt {$r->number} from status {$r->status->label()}.");
    }

    public static function cannotSelfApprove(CustomerReceipt $r): self
    {
        return new self(
            "You submitted receipt {$r->number}, so you cannot approve it."
        );
    }

    public static function missingSystemAccount(string $role): self
    {
        return new self("System account [{$role}] is not configured.");
    }

    public static function bankAccountMissingGL(CustomerReceipt $r): self
    {
        return new self(
            "Bank account linked to receipt {$r->number} has no GL account configured."
        );
    }

    public static function customerMismatch(CustomerReceipt $r, SalesInvoice $i): self
    {
        return new self(
            "Receipt {$r->number} is for customer #{$r->customer_id} ".
            "but invoice {$i->number} belongs to customer #{$i->customer_id}."
        );
    }

    public static function invoiceNotPayable(SalesInvoice $i): self
    {
        return new self("Invoice {$i->number} is {$i->status->label()} and cannot receive a payment.");
    }

    public static function overAllocation(SalesInvoice $i, string $requested, string $outstanding): self
    {
        return new self(
            "Cannot allocate {$requested} to invoice {$i->number}: outstanding is {$outstanding}."
        );
    }

    public static function allocationExceedsPayment(string $total, string $amount): self
    {
        return new self("Allocations ({$total}) exceed receipt amount ({$amount}).");
    }

    public static function cannotDelete(CustomerReceipt $r): self
    {
        return new self("Only draft receipts can be deleted. Cancel {$r->number} instead.");
    }

    public static function alreadyReversed(CustomerReceipt $r): self
    {
        return new self("Receipt {$r->number} has already been reversed.");
    }
}
