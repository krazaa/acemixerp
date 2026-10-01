<?php

declare(strict_types=1);

namespace Modules\Procurement\Exceptions;

use App\Exceptions\DomainException;
use Modules\Procurement\Models\SupplierInvoice;

final class SupplierInvoiceException extends DomainException
{
    public static function noLines(): self
    {
        return new self('A supplier invoice must have at least one line.');
    }

    public static function notEditable(SupplierInvoice $invoice): self
    {
        return new self("Invoice {$invoice->number} is {$invoice->status->label()} and cannot be edited.");
    }

    public static function cannotMatch(SupplierInvoice $invoice): self
    {
        return new self("Invoice {$invoice->number} cannot be matched from status {$invoice->status->label()}.");
    }

    public static function cannotApprove(SupplierInvoice $invoice): self
    {
        return new self("Invoice {$invoice->number} cannot be approved from status {$invoice->status->label()}.");
    }

    public static function mismatchRequiresOverride(SupplierInvoice $invoice): self
    {
        return new self(
            "Invoice {$invoice->number} has a three-way-match mismatch. ".
            'An approver with override authority must confirm before approval.'
        );
    }

    public static function cannotPost(SupplierInvoice $invoice): self
    {
        return new self("Invoice {$invoice->number} cannot be posted from status {$invoice->status->label()}.");
    }

    public static function cannotCancel(SupplierInvoice $invoice): self
    {
        return new self("Invoice {$invoice->number} cannot be cancelled from status {$invoice->status->label()}.");
    }

    public static function cannotDelete(SupplierInvoice $invoice): self
    {
        return new self('Only draft invoices can be deleted.');
    }

    public static function missingSystemAccount(string $role): self
    {
        return new self("System account [{$role}] is not configured. Set it at /system-accounts.");
    }
}
