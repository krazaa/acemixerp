<?php

declare(strict_types=1);

namespace Modules\Sales\Exceptions;

use App\Exceptions\DomainException;
use Modules\Sales\Models\SalesInvoice;
use Modules\Sales\Models\SalesOrder;

final class SalesInvoiceException extends DomainException
{
    public static function noLines(): self
    {
        return new self('A sales invoice must have at least one line.');
    }

    public static function notEditable(SalesInvoice $i): self
    {
        return new self("Invoice {$i->number} is {$i->status->label()} and cannot be edited.");
    }

    public static function invalidTransition(SalesInvoice $i, string $action): self
    {
        return new self("Cannot {$action} invoice {$i->number} from status {$i->status->label()}.");
    }

    public static function cannotApprove(SalesInvoice $i): self
    {
        return new self("Invoice {$i->number} cannot be approved from status {$i->status->label()}.");
    }

    public static function mismatchRequiresOverride(SalesInvoice $i): self
    {
        return new self(
            "Invoice {$i->number} has a three-way-match mismatch. ".
            'A manager must explicitly override before approval.'
        );
    }

    public static function cannotPost(SalesInvoice $i): self
    {
        return new self("Invoice {$i->number} cannot be posted from status {$i->status->label()}.");
    }

    public static function cannotReverse(SalesInvoice $i): self
    {
        return new self("Invoice {$i->number} cannot be reversed from status {$i->status->label()}.");
    }

    public static function missingSystemAccount(string $role): self
    {
        return new self("System account [{$role}] is not configured.");
    }

    public static function orderNotInvoiceable(SalesOrder $o): self
    {
        return new self(
            "Sales order {$o->number} is {$o->status->label()} and cannot be invoiced."
        );
    }
}
