<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Modules\Procurement\Models\PurchaseOrder;
use Modules\Procurement\Models\PurchaseRequisition;
use Modules\Procurement\Models\RequestForQuotation;
use Modules\Procurement\Models\SupplierInvoice;
use Modules\Procurement\Models\VendorPayment;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OwnerProcurementAccessTest extends TestCase
{
    public function test_owner_defaults_allow_procurement_views_without_granting_creation(): void
    {
        config(['activitylog.enabled' => false]);
        foreach ([
            '0001_01_01_000000_create_users_table.php',
            '2026_09_11_075003_2026_01_01_000002_add_erp_fields_to_users_table.php',
            '2026_09_11_062704_create_permission_tables.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        $owner = Role::findOrCreate('owner', 'web');
        foreach ((new RolesSeeder)->matrix()['owner']['permissions'] as $name) {
            $owner->givePermissionTo(Permission::findOrCreate($name, 'web'));
        }
        $user = User::factory()->create(['status' => UserStatus::Active]);
        $user->assignRole($owner);
        $unassigned = User::factory()->create(['status' => UserStatus::Active]);

        foreach ([PurchaseRequisition::class, RequestForQuotation::class, PurchaseOrder::class, SupplierInvoice::class, VendorPayment::class] as $model) {
            $this->assertTrue($user->can('viewAny', $model), $model);
            $this->assertFalse($unassigned->can('viewAny', $model), $model);
            $this->assertFalse($user->can('create', $model), $model);
        }

        $invoice = new SupplierInvoice(['status' => 'matched', 'purchase_order_id' => 16]);
        $this->assertTrue($user->can('approve', $invoice));
        $this->assertFalse($unassigned->can('approve', $invoice));
        $unassigned->givePermissionTo(Permission::findOrCreate('supplier_invoice.post', 'web'));
        $this->assertFalse($unassigned->can('approve', $invoice));
        $unassigned->assignRole(Role::findOrCreate('super-admin', 'web'));
        $this->assertFalse($unassigned->can('approve', $invoice));
        $this->assertFalse($unassigned->can('reject', $invoice));
        $invoice->status = 'approved';
        $this->assertFalse($user->can('post', $invoice));
        $invoice->status = 'draft';
        $this->assertFalse($user->can('approve', $invoice));
        $invoice->status = 'posted';
        $this->assertFalse($user->can('approve', $invoice));
    }
}
