<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesSeeder extends Seeder
{
    /** @return array<string, array{level:int, description:string, permissions:string[], system?:bool}> */
    public function matrix(): array
    {
        return [
            'super-admin' => [
                'level' => 0,
                'description' => 'Full system access. Bypasses all permission checks.',
                'permissions' => ['*'],
                'system' => true,
            ],

            'finance-manager' => [
                'level' => 10,
                'description' => 'Financial controller — journals, payments, reconciliation.',
                'permissions' => [
                    'accounting.view', 'coa.view', 'coa.manage',
                    'journal.create', 'journal.approve', 'journal.post', 'journal.reverse',
                    'payment.view', 'payment.create', 'payment.submit',
                    'payment.approve', 'payment.post', 'payment.allocate', 'payment.reverse',
                    'receipt.create', 'receipt.post',
                    'period.close', 'period.reopen',
                    'year.create', 'year.close',
                    'system_accounts.manage',
                    'supplier_invoice.view', 'supplier_invoice.post',
                    'reports.view', 'reports.financial', 'reports.export',
                    'banking.view', 'banking.reconcile',
                    'audit.view', 'settings.view',
                    'expense.view', 'expense.reimburse',
                ],
            ],

            'procurement-officer' => [
                'level' => 20,
                'description' => 'Raises requisitions, RFQs, POs, GRNs, supplier invoices.',
                'permissions' => [
                    'purchase.view', 'purchase.create',
                    'purchase_request.view', 'purchase_request.create',
                    'purchase_request.update', 'purchase_request.delete',
                    'purchase_request.submit',
                    'rfq.view', 'rfq.create', 'rfq.issue',
                    'quotation.submit',
                    'purchase_order.create',
                    'goods_receipt.create',
                    'supplier_invoice.view', 'supplier_invoice.create', 'supplier_invoice.match',
                    'inventory.view',
                    'payment.view', 'payment.create', 'payment.submit',
                ],
            ],

            'procurement-manager' => [
                'level' => 15,
                'description' => 'Approves requisitions, issues POs, posts supplier invoices.',
                'permissions' => [
                    'purchase.view', 'purchase.approve',
                    'purchase_request.view',
                    'rfq.view', 'rfq.award',
                    'purchase_order.create', 'purchase_order.issue',
                    'supplier_invoice.view', 'supplier_invoice.post',
                    'inventory.view',
                    'reports.view',
                ],
            ],

            'sales-officer' => [
                'level' => 20,
                'description' => 'Creates quotations, orders, deliveries, invoices, returns.',
                'permissions' => [
                    'sales.view', 'sales.create',
                    'quotation.create', 'sales_order.create',
                    'delivery.create', 'invoice.create', 'returns.create',
                    'inventory.view',
                ],
            ],

            'sales-manager' => [
                'level' => 15,
                'description' => 'Approves sales documents and posts invoices.',
                'permissions' => [
                    'sales.view', 'sales.approve',
                    'delivery.dispatch', 'invoice.post',
                    'reports.view',
                ],
            ],

            'inventory-manager' => [
                'level' => 20,
                'description' => 'Full stock operations.',
                'permissions' => [
                    'inventory.view', 'inventory.adjust',
                    'brands.view', 'brands.create', 'brands.update', 'brands.delete',
                    'inventory.transfer', 'inventory.count',
                    'reports.view',
                ],
            ],

            'hr-manager' => [
                'level' => 15,
                'description' => 'HR, attendance, leave, payroll approvals.',
                'permissions' => [
                    'hr.view', 'hr.manage', 'attendance.manage',
                    'leave.approve', 'payroll.approve', 'payroll.finalize',
                    'reports.view',
                    'expense.view', 'expense.create', 'expense.manager_approve',
                ],
            ],

            'operations-manager' => [
                'level' => 15,
                'description' => 'Reviews expense claims and vendor invoices.',
                'permissions' => ['expense.view', 'expense.manager_approve'],
            ],

            'owner' => [
                'level' => 1,
                'description' => 'Chief executive approval authority.',
                'permissions' => [
                    'expense.view', 'expense.ceo_approve',
                    'purchase.view', 'rfq.view', 'rfq.award', 'payment.view',
                    'supplier_invoice.approve',
                ],
                'system' => true,
            ],

            'auditor' => [
                'level' => 30,
                'description' => 'Read-only access to financials, audit log, reports.',
                'permissions' => [
                    'audit.view', 'accounting.view', 'coa.view',
                    'reports.view', 'reports.financial', 'reports.export',
                    'sales.view', 'purchase.view', 'inventory.view',
                    'supplier_invoice.view',
                ],
            ],

            'employee' => [
                'level' => 100,
                'description' => 'Base role — self-service only.',
                'permissions' => [],
            ],
        ];
    }

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->matrix() as $roleName => $config) {
            /** @var Role $role */
            $role = Role::findOrCreate($roleName, 'web');
            $role->forceFill([
                'description' => $config['description'],
                'level' => $config['level'],
                'is_system' => $config['system'] ?? false,
            ])->save();

            $permissions = $config['permissions'] === ['*']
                ? Permission::query()->pluck('name')->all()
                : $config['permissions'];

            $role->syncPermissions($permissions);
        }

        $this->call(SalesReturnPermissionsSeeder::class);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
