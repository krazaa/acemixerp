<?php

namespace Modules\Sales\Exceptions;

use App\Exceptions\DomainException;
use Modules\Sales\Models\Quotation;

final class QuotationException extends DomainException
{
    public static function noLines(): self
    {
        return new self('A quotation must have at least one line.');
    }

    public static function notEditable(Quotation $q): self
    {
        return new self("Quotation {$q->number} is {$q->status->label()} and cannot be edited.");
    }

    public static function invalidTransition(Quotation $q, string $action): self
    {
        return new self("Cannot {$action} quotation {$q->number} from status {$q->status->label()}.");
    }

    public static function cannotDelete(Quotation $q): self
    {
        return new self('Only draft quotations can be deleted.');
    }
}
