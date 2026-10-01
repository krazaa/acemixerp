<?php

declare(strict_types=1);

namespace Modules\Manufacturing\Exceptions;

use App\Exceptions\DomainException;
use Modules\Manufacturing\Models\ProductionOrder;

final class ManufacturingException extends DomainException
{
    public static function invalidTransition(ProductionOrder $o, string $action): self
    {
        return new self("Cannot {$action} production order {$o->number} from status {$o->status->label()}.");
    }

    public static function invalidQuantity(string $qty): self
    {
        return new self("Invalid quantity: {$qty}.");
    }

    public static function exceedsRemainingQuantity(string $remaining): self
    {
        return new self("Produced quantity exceeds the remaining quantity of {$remaining}.");
    }

    public static function insufficientComponentStock(string $name, string $onHand, string $required): self
    {
        return new self(
            "Insufficient stock for component {$name}: on hand {$onHand}, required {$required}."
        );
    }

    public static function missingSystemAccount(string $role): self
    {
        return new self("System account [{$role}] is not configured.");
    }
}
