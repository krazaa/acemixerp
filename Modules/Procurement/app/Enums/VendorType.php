<?php

namespace Modules\Procurement\Enums;

enum VendorType: string
{
    case Supplier = 'supplier';
    case ServiceProvider = 'service_provider';
    case Transport = 'transport';
    case Contractor = 'contractor';
    case Consultant = 'consultant';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Supplier => 'Supplier',
            self::ServiceProvider => 'Service Provider',
            self::Transport => 'Transport Provider',
            self::Contractor => 'Contractor',
            self::Consultant => 'Consultant',
            self::Other => 'Other',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Supplier => 'primary',
            self::ServiceProvider => 'info',
            self::Transport => 'success',
            self::Contractor => 'warning',
            self::Consultant => 'secondary',
            self::Other => 'dark',
        };
    }

    public function isTransport(): bool
    {
        return $this === self::Transport;
    }
}
