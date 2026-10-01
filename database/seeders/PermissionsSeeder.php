<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionsSeeder extends Seeder
{
    /** @return array<string, array{0: string, 1: string}> */
    public function permissions(): array
    {
        return [
            'brands.view' => ['inventory', 'View brands'],
            'brands.create' => ['inventory', 'Create brands'],
            'brands.update' => ['inventory', 'Update brands'],
            'brands.delete' => ['inventory', 'Delete brands'],
            // ── Users ─────────────────────────────────────────────
            'users.view' => ['users', 'View users'],
            'users.create' => ['users', 'Create users'],
            'users.update' => ['users', 'Update users'],
            'users.delete' => ['users', 'Delete users'],
            'users.manage-status' => ['users', 'Change user status (suspend/lock/disable)'],
            'users.reset-password' => ['users', 'Reset user passwords'],

            // ── Roles ─────────────────────────────────────────────
            'roles.view' => ['roles', 'View roles'],
            'roles.create' => ['roles', 'Create roles'],
            'roles.update' => ['roles', 'Update roles & permissions'],
            'roles.delete' => ['roles', 'Delete roles'],

            // ── Settings ──────────────────────────────────────────
            'settings.view' => ['settings', 'View system settings'],
            'settings.update' => ['settings', 'Update system settings'],

            // ── Audit ─────────────────────────────────────────────
            'audit.view' => ['audit', 'View activity/audit log'],

            // ── Accounting ────────────────────────────────────────
            'accounting.view' => ['accounting', 'View accounting module'],
            'coa.view' => ['accounting', 'View chart of accounts'],
            'coa.manage' => ['accounting', 'Create / update chart of accounts'],
            'journal.create' => ['accounting', 'Create journal entries'],
            'journal.approve' => ['accounting', 'Approve journal entries'],
            'journal.post' => ['accounting', 'Post journal entries to GL'],
            'journal.reverse' => ['accounting', 'Reverse posted journals'],
            'payment.view' => ['accounting', 'View vendor payments'],
            'payment.create' => ['accounting', 'Create vendor payments'],
            'payment.submit' => ['accounting', 'Submit vendor payments for approval'],
            'payment.approve' => ['accounting', 'Approve vendor payments'],
            'payment.post' => ['accounting', 'Post vendor payments to GL'],
            'payment.allocate' => ['accounting', 'Allocate payments to invoices'],
            'payment.reverse' => ['accounting', 'Reverse posted vendor payments'],
            'receipt.create' => ['accounting', 'Create customer receipts'],
            'receipt.post' => ['accounting', 'Post customer receipts'],
            'period.close' => ['accounting', 'Close an accounting period'],
            'period.reopen' => ['accounting', 'Reopen a closed accounting period'],
            'year.create' => ['accounting', 'Create a new financial year'],
            'year.close' => ['accounting', 'Close a financial year'],
            'system_accounts.manage' => ['accounting', 'Map system account roles to accounts'],
            'assets.view' => ['accounting', 'View fixed asset register'],
            'assets.manage' => ['accounting', 'Register and process fixed asset lifecycle transactions'],

            // ── Reports ───────────────────────────────────────────
            'reports.view' => ['reports', 'View operational reports'],
            'reports.financial' => ['reports', 'View financial statements'],
            'reports.export' => ['reports', 'Export reports (PDF/Excel/CSV)'],

            // ── Banking ───────────────────────────────────────────
            'banking.view' => ['banking', 'View bank accounts & transactions'],
            'banking.reconcile' => ['banking', 'Perform bank reconciliation'],

            // ── Sales ─────────────────────────────────────────────
            'sales.view' => ['sales', 'View sales module'],
            'sales.create' => ['sales', 'Create sales documents'],
            'sales.approve' => ['sales', 'Approve sales documents'],
            'quotation.create' => ['sales', 'Create quotations'],
            'sales_order.create' => ['sales', 'Create sales orders'],
            'delivery.create' => ['sales', 'Create delivery notes'],
            'delivery.dispatch' => ['sales', 'Dispatch deliveries'],
            'invoice.create' => ['sales', 'Create sales invoices'],
            'invoice.post' => ['sales', 'Post sales invoices'],
            'returns.create' => ['sales', 'Create sales returns'],

            // ── Procurement ───────────────────────────────────────
            'purchase.view' => ['procurement', 'View procurement module'],
            'purchase.create' => ['procurement', 'Create procurement documents'],
            'purchase.approve' => ['procurement', 'Approve procurement documents'],
            'purchase_request.view' => ['procurement', 'View purchase requisitions'],
            'purchase_request.create' => ['procurement', 'Create purchase requisitions'],
            'purchase_request.update' => ['procurement', 'Update draft purchase requisitions'],
            'purchase_request.delete' => ['procurement', 'Delete draft purchase requisitions'],
            'purchase_request.submit' => ['procurement', 'Submit purchase requisitions for approval'],
            'rfq.view' => ['procurement', 'View RFQs'],
            'rfq.create' => ['procurement', 'Create RFQs'],
            'rfq.issue' => ['procurement', 'Issue RFQs to vendors'],
            'rfq.award' => ['procurement', 'Award an RFQ to a vendor quote'],
            'quotation.submit' => ['procurement', 'Submit a vendor quotation'],
            'purchase_order.create' => ['procurement', 'Create purchase orders'],
            'purchase_order.issue' => ['procurement', 'Issue purchase orders to vendors'],
            'goods_receipt.create' => ['procurement', 'Record goods receipts'],
            'supplier_invoice.view' => ['procurement', 'View supplier invoices'],
            'supplier_invoice.create' => ['procurement', 'Create and edit supplier invoices'],
            'supplier_invoice.match' => ['procurement', 'Run three-way match'],
            'supplier_invoice.approve' => ['procurement', 'CEO approval and rejection of supplier invoices'],
            'supplier_invoice.post' => ['procurement', 'Post approved supplier invoices'],

            // ── Inventory ─────────────────────────────────────────
            'inventory.view' => ['inventory', 'View inventory module'],
            'inventory.adjust' => ['inventory', 'Create stock adjustments'],
            'inventory.transfer' => ['inventory', 'Create stock transfers'],
            'inventory.count' => ['inventory', 'Perform stock counts'],

            // ── HR ────────────────────────────────────────────────
            'hr.view' => ['hr', 'View HR module'],
            'hr.manage' => ['hr', 'Manage employees & HR records'],
            'attendance.manage' => ['hr', 'Manage attendance records'],
            'leave.approve' => ['hr', 'Approve leave requests'],
            'payroll.approve' => ['hr', 'Approve payroll runs'],
            'payroll.finalize' => ['hr', 'Finalize/lock payroll'],

            // ── Expenses ─────────────────────────────────────────
            'expense.view' => ['expense', 'View expense claims'],
            'expense.create' => ['expense', 'Create expense claims'],
            'expense.manager_approve' => ['expense', 'Manager approval for expense claims'],
            'expense.ceo_approve' => ['expense', 'CEO approval for expense claims'],
            'expense.reimburse' => ['expense', 'Reimburse approved expense claims'],

            // ── Workflow ──────────────────────────────────────────
            'workflow.view' => ['workflow', 'View approval queues'],
            'workflow.approve' => ['workflow', 'Approve/reject documents'],
            'workflow.configure' => ['workflow', 'Configure approval workflows'],
        ];
    }

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permissions() as $name => [$group, $description]) {
            Permission::findOrCreate($name, 'web');

            Permission::query()
                ->where('name', $name)
                ->where('guard_name', 'web')
                ->update(['group' => $group, 'description' => $description]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
