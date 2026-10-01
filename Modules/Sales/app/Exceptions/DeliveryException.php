<?php

namespace Modules\Sales\Exceptions;

use App\Exceptions\DomainException;
use Modules\Sales\Models\Delivery;
use Modules\Sales\Models\SalesOrder;

final class DeliveryException extends DomainException
{
    public static function noLines(): self
    {
        return new self('A delivery note must have at least one line.');
    }

    public static function notEditable(Delivery $d): self
    {
        return new self("Delivery {$d->number} is {$d->status->label()} and cannot be edited.");
    }

    public static function invalidTransition(Delivery $d, string $action): self
    {
        return new self("Cannot {$action} delivery {$d->number} from status {$d->status->label()}.");
    }

    public static function alreadyDispatched(Delivery $delivery): self
    {
        return new self("Delivery {$delivery->number} has already been dispatched.");
    }

    public static function cannotDelete(Delivery $d): self
    {
        return new self("Only draft deliveries can be deleted. Cancel {$d->number} instead.");
    }

    public static function orderNotDeliverable(SalesOrder $o): self
    {
        return new self(
            "Sales order {$o->number} is {$o->status->label()} and cannot accept deliveries. ".
            'The order must be confirmed first.'
        );
    }

    public static function orderMissingWarehouse(SalesOrder $o): self
    {
        return new self("Sales order {$o->number} has no source warehouse set.");
    }

    public static function overDelivery(string $itemName, string $open, string $requested): self
    {
        return new self(
            "Over-delivery on {$itemName}: open quantity is {$open}, delivery requested {$requested}."
        );
    }
}
