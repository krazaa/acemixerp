<?php

namespace Modules\Procurement\Exceptions;

use App\Exceptions\DomainException;
use Modules\Procurement\Models\PurchaseOrder;
use Modules\Procurement\Models\RequestForQuotation;

final class PurchaseOrderException extends DomainException
{
    public static function noLines(): self
    {
        return new self('A purchase order must have at least one line.');
    }

    public static function notEditable(PurchaseOrder $po): self
    {
        return new self("Purchase order {$po->number} is {$po->status->label()} and cannot be edited.");
    }

    public static function invalidTransition(PurchaseOrder $po, string $action): self
    {
        return new self("Cannot {$action} PO {$po->number} from status {$po->status->label()}.");
    }

    public static function cannotSelfApprove(PurchaseOrder $po): self
    {
        return new self(
            "You submitted purchase order {$po->number}, so you cannot approve it. ".
            'Ask another approver to review it.'
        );
    }

    public static function cannotDelete(PurchaseOrder $po): self
    {
        return new self("Only draft POs can be deleted. Cancel {$po->number} instead.");
    }

    public static function rfqNotAwarded(RequestForQuotation $rfq): self
    {
        return new self("RFQ {$rfq->number} is not awarded and cannot be converted to a PO.");
    }
}
