<?php

namespace Modules\Procurement\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class ProcurementDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            // Procurement
            'purchase.view' => [
                'procurement',
                'View procurement module',
            ],

            'purchase.create' => [
                'procurement',
                'Create procurement documents',
            ],

            'purchase.approve' => [
                'procurement',
                'Approve procurement documents',
            ],

            // Purchase Requisitions
            'purchase_request.view' => [
                'procurement',
                'View purchase requisitions',
            ],

            'purchase_request.create' => [
                'procurement',
                'Create purchase requisitions',
            ],

            'purchase_request.update' => [
                'procurement',
                'Update draft purchase requisitions',
            ],

            'purchase_request.delete' => [
                'procurement',
                'Delete draft purchase requisitions',
            ],

            'purchase_request.submit' => [
                'procurement',
                'Submit purchase requisitions for approval',
            ],

            // Purchase Orders
            'purchase_order.create' => [
                'procurement',
                'Create purchase orders',
            ],

            'purchase_order.issue' => [
                'procurement',
                'Issue purchase orders to vendors',
            ],

            // Goods Receipts
            'goods_receipt.create' => [
                'procurement',
                'Record goods receipts',
            ],

            // Supplier Invoices
            'supplier_invoice.create' => [
                'procurement',
                'Create supplier invoices',
            ],

            'supplier_invoice.post' => [
                'procurement',
                'Post supplier invoices',
            ],
        ];

        foreach ($permissions as $name => [$module, $description]) {
            Permission::updateOrCreate(
                [
                    'name' => $name,
                    'guard_name' => 'web',
                ],
                [
                    'module' => $module,
                    'description' => $description,
                ],
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
