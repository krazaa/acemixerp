<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionsSeeder::class,
            RolesSeeder::class,
            SalesReturnPermissionsSeeder::class,
            PakistanBanksSeeder::class,
            VendorTypesSeeder::class,
            CategoryDemoSeeder::class,
        ]);
    }
}
