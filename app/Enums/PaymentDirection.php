<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentDirection: string
{
    case Inbound = 'inbound';   // receipt from customer
    case Outbound = 'outbound';  // payment to vendor or other

    public function label(): string
    {
        return match ($this) {
            self::Inbound => 'Receipt',
            self::Outbound => 'Payment',
        };
    }
}
