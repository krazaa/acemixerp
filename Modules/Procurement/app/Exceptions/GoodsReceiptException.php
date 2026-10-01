<?php

declare(strict_types=1);

namespace Modules\Procurement\Exceptions;

use App\Exceptions\DomainException;
use Modules\Procurement\Models\GoodsReceipt;
use Modules\Procurement\Models\GoodsReceiptLine;
use Modules\Procurement\Models\PurchaseOrder;

final class GoodsReceiptException extends DomainException
{
    public static function noLines(): self
    {
        return new self('A goods receipt must have at least one line.');
    }

    public static function notEditable(GoodsReceipt $grn): self
    {
        return new self("GRN {$grn->number} is {$grn->status->label()} and cannot be edited.");
    }

    public static function notPostable(GoodsReceipt $grn): self
    {
        return new self("GRN {$grn->number} cannot be posted from status {$grn->status->label()}.");
    }

    public static function notCancellable(GoodsReceipt $grn): self
    {
        return new self('Only draft GRNs can be cancelled.');
    }

    public static function cannotDelete(GoodsReceipt $grn): self
    {
        return new self('Only draft GRNs can be deleted.');
    }

    public static function poNotReceivable(PurchaseOrder $po): self
    {
        return new self("PO {$po->number} is {$po->status->label()} and cannot accept receipts.");
    }

    public static function poMissingWarehouse(PurchaseOrder $po): self
    {
        return new self("PO {$po->number} has no destination warehouse set.");
    }

    public static function lineMismatch(GoodsReceipt $grn, GoodsReceiptLine $line): self
    {
        return new self("Line on GRN {$grn->number} references a PO line that is not part of this PO.");
    }

    public static function overReceipt(string $itemName, string $ordered, string $proposed): self
    {
        return new self(
            "Over-receipt on {$itemName}: ordered {$ordered}, proposed total {$proposed}. ".
            'Adjust the accepted quantity or create a new PO for the excess.'
        );
    }
}
