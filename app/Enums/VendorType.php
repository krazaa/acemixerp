<?php

namespace App\Models;

enum VendorType: string
{
    case Supplier = 'supplier';
    case ServiceProvider = 'service_provider';
    case Transporter = 'transporter';
    case Printing = 'printing';
    case Security = 'security';
}
