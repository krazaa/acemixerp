<?php

namespace Modules\Manufacturing\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $role = Role::firstOrCreate(
            [
                'name' => 'production-manager',
                'guard_name' => 'web',
            ],
            [
                'level' => 20,
                'description' => 'Manages BOMs, production orders, and shop-floor execution.',
            ]
        );

        $role->givePermissionTo([
            'production.view',
            'production.create',
            'production.release',
            'production.execute',
            'bom.view',
            'bom.manage',
            'inventory.view',
            'reports.view',
        ]);

    }
}
