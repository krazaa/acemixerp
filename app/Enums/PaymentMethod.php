<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case BankTransfer = 'bank_transfer';
    case Cheque = 'cheque';
    case CreditCard = 'credit_card';
    case OnlineGateway = 'online_gateway';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::BankTransfer => 'Bank Transfer',
            self::Cheque => 'Cheque',
            self::CreditCard => 'Credit Card',
            self::OnlineGateway => 'Online Gateway',
            self::Other => 'Other',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Cash => 'success',
            self::BankTransfer => 'primary',
            self::Cheque => 'info',
            self::CreditCard => 'warning',
            self::OnlineGateway => 'info',
            self::Other => 'secondary',
        };
    }

    /** Cash payments post to the Cash on Hand system account. */
    public function usesCashAccount(): bool
    {
        return $this === self::Cash;
    }

    public function requiresBankAccount(): bool
    {
        return ! $this->usesCashAccount();
    }
}
