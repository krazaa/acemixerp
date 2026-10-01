<?php

namespace Modules\Procurement\Exceptions;

use App\Exceptions\DomainException;
use Modules\Procurement\Models\RequestForQuotation;

final class RfqException extends DomainException
{
    public static function noLines(): self
    {
        return new self('An RFQ must contain at least one line.');
    }

    public static function noVendors(): self
    {
        return new self('An RFQ must include at least one vendor.');
    }

    public static function notEditable(RequestForQuotation $rfq): self
    {
        return new self("RFQ {$rfq->number} is {$rfq->status->label()} and cannot be edited.");
    }

    public static function invalidTransition(
        RequestForQuotation $rfq,
        string $action,
    ): self {
        return new self("RFQ {$rfq->number} cannot {$action} from status {$rfq->status->label()}.");
    }

    public static function cannotDelete(RequestForQuotation $rfq): self
    {
        return new self("RFQ {$rfq->number} cannot be deleted unless it is a draft.");
    }
}
