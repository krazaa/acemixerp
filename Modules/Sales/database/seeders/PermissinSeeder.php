<?php

namespace Modules\Sales\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissinSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Permissions
        |--------------------------------------------------------------------------
        */

        $permissions = [
            // Invoices
            'invoice.view' => ['sales', 'View sales invoices'],
            'invoice.create' => ['sales', 'Create and edit sales invoices'],
            'invoice.approve' => ['sales', 'Approve sales invoices'],
            'invoice.post' => ['sales', 'Post sales invoices to GL'],
            'invoice.reverse' => ['sales', 'Reverse posted sales invoices'],

            // Receipts
            'receipt.view' => ['sales', 'View customer receipts'],
            'receipt.create' => ['sales', 'Create customer receipts'],
            'receipt.submit' => ['sales', 'Submit receipts for approval'],
            'receipt.approve' => ['sales', 'Approve customer receipts'],
            'receipt.post' => ['sales', 'Post customer receipts to GL'],
            'receipt.reverse' => ['sales', 'Reverse posted customer receipts'],
        ];

        /*
        |--------------------------------------------------------------------------
        | Create / Update Permissions
        |--------------------------------------------------------------------------
        */

        foreach ($permissions as $name => [$module, $description]) {
            Permission::updateOrCreate(
                ['name' => $name],
                [
                    'guard_name' => 'web',
                    // 'module'     => $module,
                    'description' => $description,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Roles
        |--------------------------------------------------------------------------
        */

        $roles = [
            'sales-officer' => [
                'invoice.view',
                'invoice.create',
                'receipt.view',
                'receipt.create',
                'receipt.submit',
            ],

            'sales-manager' => [
                'invoice.view',
                'invoice.approve',
                'invoice.post',
                'invoice.reverse',
                'receipt.view',
                'receipt.approve',
                'receipt.post',
                'receipt.reverse',
            ],

            'finance-manager' => [
                'invoice.view',
                'invoice.post',
                'invoice.reverse',
                'receipt.view',
                'receipt.post',
                'receipt.reverse',
            ],

            'auditor' => [
                'invoice.view',
                'receipt.view',
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Assign Permissions to Roles
        |--------------------------------------------------------------------------
        */

        foreach ($roles as $roleName => $permissionNames) {
            $role = Role::findOrCreate($roleName, 'web');

            $role->syncPermissions($permissionNames);
        }
    }
}
