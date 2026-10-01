<?php

namespace App\Enums;

enum StockMovementType: string
{
    case Opening = 'opening';
    case Purchase = 'purchase';
    case Sale = 'sale';
    case TransferIn = 'transfer_in';
    case TransferOut = 'transfer_out';
    case Return = 'return';
    case Adjustment = 'adjustment';
    case Consumption = 'consumption';
    case Production = 'production';
    case WriteOff = 'write_off';

    public function label(): string
    {
        return match ($this) {
            self::Opening => 'Opening',
            self::Purchase => 'Purchase',
            self::Sale => 'Sale',
            self::TransferIn => 'Transfer In',
            self::TransferOut => 'Transfer Out',
            self::Return => 'Return',
            self::Adjustment => 'Adjustment',
            self::Consumption => 'Consumption',
            self::Production => 'Production',
            self::WriteOff => 'Write-Off',
        };
    }

    public function direction(): int
    {
        return match ($this) {
            self::Opening, self::Purchase, self::TransferIn,
            self::Return, self::Production => 1,

            self::Sale, self::TransferOut,
            self::Consumption, self::WriteOff => -1,

            self::Adjustment => 0,  // signed per movement
        };
    }
}
