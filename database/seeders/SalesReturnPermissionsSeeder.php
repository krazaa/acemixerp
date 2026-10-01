<?php

namespace Database\Seeders;

use App\Contracts\SequenceGenerator;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SalesReturnPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['view', 'create', 'approve', 'receive', 'inspect', 'accept', 'credit', 'post'] as $action) {
            Permission::findOrCreate('returns.'.$action, 'web');
        }
        foreach ([
            'sales-officer' => ['view', 'create'],
            'sales-manager' => ['view', 'create', 'approve'],
            'owner' => ['view', 'approve'],
            'warehouse' => ['view', 'receive'],
            'inventory-manager' => ['view', 'receive', 'inspect', 'accept'],
            'finance-manager' => ['view', 'credit', 'post'],
        ] as $name => $actions) {
            $role = Role::query()->where('name', $name)->where('guard_name', 'web')->first();
            if ($role) {
                $role->givePermissionTo(array_map(fn ($action) => 'returns.'.$action, $actions));
            }
        }
        app(SequenceGenerator::class)->register('sales_return', 'SR');
        app(SequenceGenerator::class)->register('sales_credit_note', 'SCN');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
