<?php

namespace App\Enums;

enum PaymentTermType: string
{
    case DueOnReceipt = 'due_on_receipt';
    case Net = 'net';               // net 15, net 30, net 60...
    case EndOfMonth = 'end_of_month';      // "net 30 EOM"
    case DayOfMonth = 'day_of_month';      // "due on the 15th"

    public function label(): string
    {
        return match ($this) {
            self::DueOnReceipt => 'Due on Receipt',
            self::Net => 'Net Days',
            self::EndOfMonth => 'End of Month + Days',
            self::DayOfMonth => 'Specific Day of Month',
        };
    }
}
