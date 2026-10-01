<?php

namespace Modules\Inventory\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class BrandPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['view', 'create', 'update', 'delete'] as $action) {
            Permission::findOrCreate('brands.'.$action, 'web');
        }
        $manager = Role::query()->where('name', 'inventory-manager')->where('guard_name', 'web')->first();
        $manager?->givePermissionTo(['brands.view', 'brands.create', 'brands.update', 'brands.delete']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
