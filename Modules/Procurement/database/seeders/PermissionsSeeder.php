<?php

namespace Modules\Procurement\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Clear Spatie Permission Cache
        |--------------------------------------------------------------------------
        */
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        /*
        |--------------------------------------------------------------------------
        | Procurement Permissions
        |--------------------------------------------------------------------------
        */
        $permissions = [
            /*
            |--------------------------------------------------------------------------
            | Procurement
            |--------------------------------------------------------------------------
            */
            'purchase.view' => [
                // 'module' => 'procurement',
                'description' => 'View procurement module',
            ],

            'purchase.create' => [
                // 'module' => 'procurement',
                'description' => 'Create procurement documents',
            ],

            'purchase.approve' => [
                // 'module' => 'procurement',
                'description' => 'Approve procurement documents',
            ],

            /*
            |--------------------------------------------------------------------------
            | Purchase Requisitions
            |--------------------------------------------------------------------------
            */
            'purchase_request.view' => [
                // 'module' => 'procurement',
                'description' => 'View purchase requisitions',
            ],

            'purchase_request.create' => [
                // 'module' => 'procurement',
                'description' => 'Create purchase requisitions',
            ],

            'purchase_request.update' => [
                // 'module' => 'procurement',
                'description' => 'Update draft purchase requisitions',
            ],

            'purchase_request.delete' => [
                // 'module' => 'procurement',
                'description' => 'Delete draft purchase requisitions',
            ],

            'purchase_request.submit' => [
                // 'module' => 'procurement',
                'description' => 'Submit purchase requisitions for approval',
            ],

            'purchase_request.review' => [
                // 'module' => 'procurement',
                'description' => 'Review purchase requisitions',
            ],

            'purchase_request.approve' => [
                // 'module' => 'procurement',
                'description' => 'Approve purchase requisitions',
            ],

            'purchase_request.reject' => [
                // 'module' => 'procurement',
                'description' => 'Reject purchase requisitions',
            ],

            'purchase_request.cancel' => [
                // 'module' => 'procurement',
                'description' => 'Cancel purchase requisitions',
            ],

            'purchase_request.close' => [
                // 'module' => 'procurement',
                'description' => 'Close purchase requisitions',
            ],

            /*
            |--------------------------------------------------------------------------
            | Request For Quotation
            |--------------------------------------------------------------------------
            */
            'rfq.view' => [
                // 'module' => 'procurement',
                'description' => 'View requests for quotation',
            ],

            'rfq.create' => [
                // 'module' => 'procurement',
                'description' => 'Create requests for quotation',
            ],

            'rfq.update' => [
                // 'module' => 'procurement',
                'description' => 'Update requests for quotation',
            ],

            'rfq.send' => [
                // 'module' => 'procurement',
                'description' => 'Send requests for quotation to vendors',
            ],

            'rfq.cancel' => [
                // 'module' => 'procurement',
                'description' => 'Cancel requests for quotation',
            ],

            /*
            |--------------------------------------------------------------------------
            | Vendor Quotations
            |--------------------------------------------------------------------------
            */
            'quotation.view' => [
                // 'module' => 'procurement',
                'description' => 'View vendor quotations',
            ],

            'quotation.create' => [
                // 'module' => 'procurement',
                'description' => 'Create vendor quotations',
            ],

            'quotation.update' => [
                // 'module' => 'procurement',
                'description' => 'Update vendor quotations',
            ],

            'quotation.compare' => [
                // 'module' => 'procurement',
                'description' => 'Compare vendor quotations',
            ],

            'quotation.approve' => [
                // 'module' => 'procurement',
                'description' => 'Approve vendor quotation selection',
            ],

            /*
            |--------------------------------------------------------------------------
            | Purchase Orders
            |--------------------------------------------------------------------------
            */
            'purchase_order.view' => [
                // 'module' => 'procurement',
                'description' => 'View purchase orders',
            ],

            'purchase_order.create' => [
                // 'module' => 'procurement',
                'description' => 'Create purchase orders',
            ],

            'purchase_order.update' => [
                // 'module' => 'procurement',
                'description' => 'Update purchase orders',
            ],

            'purchase_order.issue' => [
                // 'module' => 'procurement',
                'description' => 'Issue purchase orders to vendors',
            ],

            'purchase_order.cancel' => [
                // 'module' => 'procurement',
                'description' => 'Cancel purchase orders',
            ],

            /*
            |--------------------------------------------------------------------------
            | Goods Receipts
            |--------------------------------------------------------------------------
            */
            'goods_receipt.view' => [
                // 'module' => 'procurement',
                'description' => 'View goods receipts',
            ],

            'goods_receipt.create' => [
                // 'module' => 'procurement',
                'description' => 'Create goods receipts',
            ],

            'goods_receipt.update' => [
                // 'module' => 'procurement',
                'description' => 'Update goods receipts',
            ],

            'goods_receipt.post' => [
                // 'module' => 'procurement',
                'description' => 'Post goods receipts',
            ],

            'goods_receipt.cancel' => [
                // 'module' => 'procurement',
                'description' => 'Cancel goods receipts',
            ],

            /*
            |--------------------------------------------------------------------------
            | Supplier Invoices
            |--------------------------------------------------------------------------
            */
            'supplier_invoice.view' => [
                // 'module' => 'procurement',
                'description' => 'View supplier invoices',
            ],

            'supplier_invoice.create' => [
                // 'module' => 'procurement',
                'description' => 'Create supplier invoices',
            ],

            'supplier_invoice.update' => [
                // 'module' => 'procurement',
                'description' => 'Update supplier invoices',
            ],

            'supplier_invoice.post' => [
                // 'module' => 'procurement',
                'description' => 'Post supplier invoices',
            ],

            'supplier_invoice.cancel' => [
                // 'module' => 'procurement',
                'description' => 'Cancel supplier invoices',
            ],

            /*
            |--------------------------------------------------------------------------
            | Inventory
            |--------------------------------------------------------------------------
            */
            'inventory.view' => [
                // 'module' => 'procurement',
                'description' => 'View inventory',
            ],

            'inventory.receive' => [
                // 'module' => 'procurement',
                'description' => 'Receive inventory',
            ],

            /*
            |--------------------------------------------------------------------------
            | Vendors
            |--------------------------------------------------------------------------
            */
            'vendor.view' => [
                // 'module' => 'procurement',
                'description' => 'View procurement vendors',
            ],

            'vendor.create' => [
                // 'module' => 'procurement',
                'description' => 'Create procurement vendors',
            ],

            'vendor.update' => [
                // 'module' => 'procurement',
                'description' => 'Update procurement vendors',
            ],

            /*
            |--------------------------------------------------------------------------
            | Reports
            |--------------------------------------------------------------------------
            */
            'reports.view' => [
                // 'module' => 'procurement',
                'description' => 'View procurement reports',
            ],

            'reports.export' => [
                // 'module' => 'procurement',
                'description' => 'Export procurement reports',
            ],

            /*
            |--------------------------------------------------------------------------
            | Procurement Settings
            |--------------------------------------------------------------------------
            */
            'procurement.settings.view' => [
                // 'module' => 'procurement',
                'description' => 'View procurement settings',
            ],

            'procurement.settings.update' => [
                // 'module' => 'procurement',
                'description' => 'Update procurement settings',
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
        | Clear Permission Cache
        |--------------------------------------------------------------------------
        */
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
