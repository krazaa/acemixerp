<?php

namespace Modules\Sales\Exceptions;

use App\Exceptions\DomainException;
use Modules\Sales\Models\Quotation;
use Modules\Sales\Models\SalesOrder;

final class SalesOrderException extends DomainException
{
    public static function noLines(): self
    {
        return new self('A sales order must have at least one line.');
    }

    public static function notEditable(SalesOrder $o): self
    {
        return new self("Sales order {$o->number} is {$o->status->label()} and cannot be edited.");
    }

    public static function invalidTransition(SalesOrder $o, string $action): self
    {
        return new self("Cannot {$action} sales order {$o->number} from status {$o->status->label()}.");
    }

    public static function cannotSelfApprove(SalesOrder $o): self
    {
        return new self(
            "You submitted sales order {$o->number}, so you cannot approve it. ".
            'Ask another approver to review it.'
        );
    }

    public static function cannotDelete(SalesOrder $o): self
    {
        return new self("Only draft sales orders can be deleted. Cancel {$o->number} instead.");
    }

    public static function lineDoesNotBelong(SalesOrder $order): self
    {
        return new self("A submitted line does not belong to sales order {$order->number}.");
    }

    public static function cannotRemoveDeliveredLine(SalesOrder $order): self
    {
        return new self("Sales order {$order->number} has a line linked to a delivery and it cannot be removed.");
    }

    public static function quotationNotConvertible(Quotation $q): self
    {
        return new self(
            "Quotation {$q->number} is {$q->status->label()} and cannot be converted to a Sales Order. ".
            'The quotation must be accepted first.'
        );
    }
}
