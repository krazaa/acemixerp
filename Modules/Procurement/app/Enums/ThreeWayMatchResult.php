<?php

namespace Modules\Procurement\Enums;

enum ThreeWayMatchResult: string
{
    case Ok = 'ok';
    case QuantityExceeds = 'quantity_exceeds';
    case PriceExceeds = 'price_exceeds';
    case TaxMismatch = 'tax_mismatch';
    case NoReceipt = 'no_receipt';

    public function label(): string
    {
        return match ($this) {
            self::Ok => 'Matched',
            self::QuantityExceeds => 'Quantity exceeds accepted receipt',
            self::PriceExceeds => 'Unit price exceeds PO price',
            self::TaxMismatch => 'Tax rate differs from PO',
            self::NoReceipt => 'No accepted receipt against this PO line',
        };
    }

    public function isFailure(): bool
    {
        return $this !== self::Ok;
    }
}
