<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\Item;
use App\Models\JournalEntry;
use App\Models\User;
use App\Models\Warehouse;
use App\Notifications\ApplicationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Modules\Inventory\Models\StockBalance;
use Modules\Procurement\Models\PurchaseRequisition;
use Modules\Procurement\Services\PurchaseRequisitionService;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DatabaseNotificationsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['activitylog.enabled' => false]);
        Schema::disableForeignKeyConstraints();
        foreach ([
            '0001_01_01_000000_create_users_table.php',
            '2026_09_11_075003_2026_01_01_000002_add_erp_fields_to_users_table.php',
            '2026_09_11_062704_create_permission_tables.php',
            '2026_09_28_163338_add_soft_deletes_to_users_table.php',
            '2026_09_14_173239_create_journal_entries_table.php',
            '2026_01_04_000003_create_items_table.php',
            '2026_01_05_000001_create_warehouses_table.php',
            '2026_09_28_162318_create_notifications_table.php',
            '2026_09_28_162351_create_inventory_alert_states_table.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        (require base_path('Modules/Inventory/database/migrations/2026_09_16_124133_create_stock_balances_table.php'))->up();
        (require base_path('Modules/Procurement/database/migrations/2026_09_14_081105_create_purchase_requisitions_table.php'))->up();
    }

    public function test_users_can_manage_only_their_own_notifications(): void
    {
        $user = $this->user();
        $other = $this->user();
        $user->notify(new ApplicationNotification('Own alert', 'Review stock', '/inventory/stock', 'inventory'));
        $other->notify(new ApplicationNotification('Private alert', 'Private contents', '/inventory/stock', 'inventory'));
        $own = $user->notifications()->first();
        $private = $other->notifications()->first();
        $this->actingAs($user)->get(route('notifications.index'))->assertOk()->assertSee('Own alert')->assertDontSee('Private alert');
        $this->patch(route('notifications.update', $private->id), ['status' => 'read'])->assertNotFound();
        $this->post(route('notifications.open', $private->id))->assertNotFound();
        $this->patch(route('notifications.update', $own->id), ['status' => 'read'])->assertRedirect();
        $this->assertNotNull($own->fresh()->read_at);
        $this->get(route('notifications.index', ['filter' => 'unread']))->assertViewHas('notifications', fn ($rows) => $rows->isEmpty());
        $this->patch(route('notifications.update', $own->id), ['status' => 'unread'])->assertRedirect();
        $this->assertNull($own->fresh()->read_at);
        $this->post(route('notifications.read-all'))->assertRedirect();
        $this->assertNotNull($own->fresh()->read_at);
        $this->assertNull($private->fresh()->read_at);
        $this->post(route('notifications.open', $own->id))->assertRedirect('/inventory/stock');
    }

    public function test_guests_are_denied_and_external_targets_are_rejected(): void
    {
        $this->get(route('notifications.index'))->assertRedirect(route('login'));
        $user = $this->user();
        $user->notify(new ApplicationNotification('Unsafe URL', 'Contents', '//example.com', 'workflow'));
        $this->actingAs($user)->post(route('notifications.open', $user->notifications()->first()->id))->assertRedirect(route('notifications.index'));
    }

    public function test_approval_transitions_notify_eligible_approvers_and_then_the_creator(): void
    {
        $owner = $this->user(['accounting.view', 'journal.approve']);
        $approver = $this->user(['accounting.view', 'journal.approve']);
        $unauthorized = $this->user(['accounting.view']);
        $inactive = $this->user(['accounting.view', 'journal.approve']);
        $inactive->update(['status' => UserStatus::Disabled]);
        $this->actingAs($owner);
        $entry = JournalEntry::create(['number' => 'JV-NOTIFY', 'entry_date' => today(), 'description' => 'Approval test', 'currency_code' => 'PKR', 'status' => 'draft', 'created_by' => $owner->id]);
        $entry->update(['status' => 'submitted']);
        $entry->update(['description' => 'Edited description']);
        $this->assertSame(1, $approver->notifications()->count());
        $this->assertSame('approval', $approver->notifications()->first()->data['category']);
        $this->assertSame(1, $owner->notifications()->count());
        $this->assertSame(0, $unauthorized->notifications()->count());
        $this->assertSame(0, $inactive->notifications()->count());
        $this->actingAs($approver);
        $approval = $approver->notifications()->first();
        $this->get(route('notifications.index'))->assertSee('View approval details');
        $this->post(route('notifications.open', $approval->id))
            ->assertRedirect(route('journals.show', $entry));
        $this->assertNotNull($approval->fresh()->read_at);
        $entry->update(['status' => 'approved']);
        $this->assertSame(1, $owner->notifications()->where('data->category', 'workflow')->count());
    }

    public function test_starting_review_notifies_the_reviewer_when_they_can_approve(): void
    {
        $requester = $this->user(['purchase.view', 'purchase.approve']);
        $reviewer = $this->user(['purchase.view', 'purchase.approve']);
        $this->actingAs($requester);
        $requisition = PurchaseRequisition::create([
            'number' => 'PR-REVIEW', 'requested_date' => today(), 'purpose' => 'Review notification',
            'status' => 'draft', 'requested_by' => $requester->id, 'created_by' => $requester->id,
        ]);
        $requisition->update(['status' => 'submitted']);
        $this->assertSame(0, $requester->notifications()->count());
        $this->actingAs($reviewer);

        app(PurchaseRequisitionService::class)->startReview($requisition, $reviewer->id);

        $this->assertSame(2, $reviewer->notifications()->where('data->category', 'approval')->count());
        $this->assertSame(1, $requester->notifications()->where('data->category', 'workflow')->count());
        $alert = $reviewer->notifications()->first();
        $this->post(route('notifications.open', $alert->id))
            ->assertRedirect(route('procurement.purchase-requisitions.show', $requisition));
    }

    public function test_stock_alerts_aggregate_batches_and_rearm_only_after_recovery(): void
    {
        $recipient = $this->user(['inventory.view']);
        $other = $this->user();
        $item = Item::create(['code' => 'ALERT', 'name' => 'Alert item', 'status' => 'active', 'reorder_level' => 10]);
        $warehouse = Warehouse::create(['code' => 'ALERT', 'name' => 'Alert warehouse', 'status' => 'active']);
        $first = StockBalance::create(['item_id' => $item->id, 'warehouse_id' => $warehouse->id, 'quantity' => 8, 'batch_id' => 1]);
        $second = StockBalance::create(['item_id' => $item->id, 'warehouse_id' => $warehouse->id, 'quantity' => 8, 'batch_id' => 2]);
        $first->update(['quantity' => 5]);
        $this->assertSame(0, $recipient->notifications()->count());
        DB::beginTransaction();
        $second->update(['quantity' => 4]);
        $this->assertSame(1, $recipient->notifications()->count());
        DB::rollBack();
        $this->assertSame(0, $recipient->notifications()->count());
        $second->refresh()->update(['quantity' => 4]);
        $recipient->unreadNotifications()->update(['read_at' => now()]);
        $second->update(['quantity' => 3]);
        $this->artisan('inventory:notify-low-stock')->assertSuccessful();
        $this->assertSame(1, $recipient->notifications()->count());
        $first->update(['quantity' => 20]);
        $first->update(['quantity' => 2]);
        $this->assertSame(2, $recipient->notifications()->count());
        $this->assertSame(0, $other->notifications()->count());
    }

    public function test_workflow_configuration_has_real_routes_models_and_policy_methods(): void
    {
        foreach (config('notifications.workflows') as $model => $workflow) {
            $this->assertTrue(class_exists($model), $model);
            $this->assertTrue(Route::has($workflow['route']), $workflow['route']);
            $policy = Gate::getPolicyFor($model);
            $this->assertNotNull($policy, $model);
            foreach ($workflow['approvals'] as $ability) {
                $this->assertTrue(method_exists($policy, $ability), $model.' '.$ability);
            }
        }
    }

    private function user(array $permissions = []): User
    {
        $user = User::factory()->create(['status' => UserStatus::Active]);
        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        return $user;
    }
}
