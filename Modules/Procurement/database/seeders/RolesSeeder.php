<?php

namespace Modules\Procurement\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear Spatie's permission cache before making changes.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        /*
        |--------------------------------------------------------------------------
        | Procurement Permissions
        |--------------------------------------------------------------------------
        */

        $permissions = [
            // Procurement
            'purchase.view' => [

                'description' => 'View procurement module',
            ],

            'purchase.create' => [

                'description' => 'Create procurement documents',
            ],

            'purchase.approve' => [

                'description' => 'Approve procurement documents',
            ],

            // Purchase Requisition
            'purchase_request.view' => [

                'description' => 'View purchase requisitions',
            ],

            'purchase_request.create' => [

                'description' => 'Create purchase requisitions',
            ],

            'purchase_request.update' => [

                'description' => 'Update draft purchase requisitions',
            ],

            'purchase_request.delete' => [

                'description' => 'Delete draft purchase requisitions',
            ],

            'purchase_request.submit' => [

                'description' => 'Submit purchase requisitions for approval',
            ],

            // Purchase Order
            'purchase_order.create' => [

                'description' => 'Create purchase orders',
            ],

            'purchase_order.issue' => [

                'description' => 'Issue purchase orders to vendors',
            ],

            // Goods Receipt
            'goods_receipt.create' => [

                'description' => 'Record goods receipts',
            ],

            // Supplier Invoice
            'supplier_invoice.create' => [

                'description' => 'Create supplier invoices',
            ],

            'supplier_invoice.post' => [

                'description' => 'Post supplier invoices',
            ],

            // Inventory
            'inventory.view' => [

                'description' => 'View inventory',
            ],

            // Reports
            'reports.view' => [

                'description' => 'View procurement reports',
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Create / Update Permissions
        |--------------------------------------------------------------------------
        */

        foreach ($permissions as $name => $data) {
            Permission::updateOrCreate(
                [
                    'name' => $name,
                    'guard_name' => 'web',
                ],

            );
        }

        /*
        |--------------------------------------------------------------------------
        | Procurement Roles
        |--------------------------------------------------------------------------
        */

        $roles = [
            'procurement-officer' => [

                'permissions' => [
                    'purchase.view',
                    'purchase.create',

                    'purchase_request.create',
                    'purchase_order.create',

                    'goods_receipt.create',
                    'supplier_invoice.create',

                    'inventory.view',
                ],
            ],

            'procurement-manager' => [
                'level' => 15,

                'permissions' => [
                    'purchase.view',
                    'purchase.approve',

                    'purchase_order.issue',

                    'supplier_invoice.post',

                    'inventory.view',
                    'reports.view',
                ],
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Create / Update Roles
        |--------------------------------------------------------------------------
        */

        foreach ($roles as $roleName => $roleData) {
            $role = Role::updateOrCreate(
                [
                    'name' => $roleName,
                    'guard_name' => 'web',
                ],

            );

            /*
             * syncPermissions() ensures the role has exactly
             * the permissions defined above.
             */
            $role->syncPermissions($roleData['permissions']);
        }

        /*
        |--------------------------------------------------------------------------
        | Clear Permission Cache
        |--------------------------------------------------------------------------
        */

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
