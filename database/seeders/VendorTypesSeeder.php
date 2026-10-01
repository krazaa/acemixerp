<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\VendorType;
use Illuminate\Database\Seeder;
use Modules\Procurement\Enums\VendorType as ProcurementVendorType;

class VendorTypesSeeder extends Seeder
{
    public function run(): void
    {
        foreach (ProcurementVendorType::cases() as $vendorType) {
            VendorType::query()->firstOrCreate(['name' => $vendorType->label()]);
        }
    }
}
