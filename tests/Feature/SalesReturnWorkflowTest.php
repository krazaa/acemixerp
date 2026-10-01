<?php

namespace Tests\Feature;

use App\Contracts\AccountingPeriodManager;
use App\Contracts\JournalPoster;
use App\Contracts\SequenceGenerator;
use App\Contracts\SystemAccountManager;
use App\Enums\SystemAccountRole;
use App\Enums\UserStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Customer;
use App\Models\Item;
use App\Models\JournalEntry;
use App\Models\Organization;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Modules\Sales\Models\SalesCreditNote;
use Modules\Sales\Models\SalesInvoice;
use Modules\Sales\Models\SalesReturn;
use Modules\Sales\Services\SalesReturnWorkflow;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SalesReturnWorkflowTest extends TestCase
{
    private User $actor;

    private SalesInvoice $invoice;

    private Warehouse $warehouse;

    private Account $returnsAccount;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Schema::disableForeignKeyConstraints();
        config(['activitylog.enabled' => false]);
        foreach ([
            '0001_01_01_000000_create_users_table.php',
            '2026_09_11_075003_2026_01_01_000002_add_erp_fields_to_users_table.php',
            '2026_09_28_163338_add_soft_deletes_to_users_table.php',
            '2026_09_11_062704_create_permission_tables.php',
            '2026_09_11_074816_create_organizations_table.php',
            '2026_09_11_075115_2026_01_01_000003_create_document_sequences_table.php',
            '2026_09_11_075250_2026_01_01_000005_create_financial_years_table.php',
            '2026_01_03_000002_create_customers_table.php',
            '2026_01_04_000002_create_units_table.php',
            '2026_01_04_000003_create_items_table.php',
            '2026_01_05_000001_create_warehouses_table.php',
            '2026_01_08_000001_create_accounts_table.php',
            '2026_09_14_173239_create_journal_entries_table.php',
            '2026_09_14_173246_create_journal_lines_table.php',
            '2026_09_27_104031_add_employee_id_to_journal_lines_table.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        foreach ([
            'Sales/2026_03_02_000001_create_sales_invoices_table.php',
            'Sales/2026_03_02_000002_create_sales_invoice_lines_table.php',
            'Inventory/2026_09_16_124133_create_stock_balances_table.php',
            'Inventory/2026_09_16_124134_create_stock_movements_table.php',
            'Sales/2026_10_01_052922_create_sales_returns_tables.php',
        ] as $migration) {
            [$module,$file] = explode('/', $migration);
            (require base_path('Modules/'.$module.'/database/migrations/'.$file))->up();
        }
        Organization::create(['name' => 'ACEMIX', 'currency_code' => 'PKR']);
        $this->actor = User::factory()->create(['status' => UserStatus::Active]);
        foreach (['view', 'create', 'approve', 'receive', 'inspect', 'accept', 'credit', 'post'] as $action) {
            $this->actor->givePermissionTo(Permission::findOrCreate('returns.'.$action, 'web'));
        }
        foreach (['sales_return' => 'SR', 'sales_credit_note' => 'SCN', 'journal' => 'JE'] as $key => $prefix) {
            app(SequenceGenerator::class)->register($key, $prefix);
        }
        $this->warehouse = Warehouse::create(['name' => 'Main', 'code' => 'MAIN', 'status' => 'active']);
        $customer = Customer::create(['name' => 'Customer', 'code' => 'C1', 'status' => 'active']);
        $item = Item::create(['name' => 'Vitamin A', 'code' => 'VITA', 'item_type' => 'stock']);
        $accounts = [];
        foreach (['ar', 'revenue', 'tax', 'wht', 'inventory', 'cogs', 'returns'] as $key) {
            $accounts[$key] = Account::create(['code' => $key, 'name' => $key, 'type' => 'asset', 'normal_balance' => 'debit', 'is_postable' => true, 'status' => 'active']);
        }
        $this->returnsAccount = $accounts['returns'];
        $original = JournalEntry::create(['number' => 'ORIGINAL', 'entry_date' => today(), 'description' => 'Original sale', 'currency_code' => 'PKR', 'status' => 'posted', 'total_debit' => 160, 'total_credit' => 160]);
        foreach ([['ar', 106, 0, 'AR', $customer->id], ['revenue', 0, 100, 'Revenue', null], ['tax', 0, 10, 'Output Tax', null], ['wht', 4, 0, 'WHT withheld - Original', null], ['cogs', 50, 0, 'COGS', null], ['inventory', 0, 50, 'Inventory', null]] as $i => [$key,$debit,$credit,$memo,$party]) {
            $original->lines()->create(['position' => $i, 'account_id' => $accounts[$key]->id, 'debit' => $debit, 'credit' => $credit, 'memo' => $memo, 'customer_id' => $party]);
        }
        $this->invoice = SalesInvoice::create(['number' => 'INV-RETURN', 'customer_id' => $customer->id, 'warehouse_id' => $this->warehouse->id, 'invoice_date' => today(), 'due_date' => today(), 'currency_code' => 'PKR', 'exchange_rate' => 1, 'status' => 'posted', 'subtotal' => 100, 'tax_total' => 10, 'wht_tax_total' => 4, 'total' => 106, 'paid_amount' => 20, 'journal_entry_id' => $original->id]);
        $this->invoice->lines()->create(['item_id' => $item->id, 'quantity' => 10, 'unit_price' => 10, 'line_subtotal' => 100, 'line_tax' => 10, 'line_wht_tax' => 4, 'line_total' => 106, 'unit_cost' => 5, 'cogs_amount' => 50, 'tax_account_id' => $accounts['tax']->id, 'inventory_account_id' => $accounts['inventory']->id, 'cogs_account_id' => $accounts['cogs']->id]);
        $this->mock(SystemAccountManager::class, function ($mock): void {
            $mock->shouldReceive('resolve')->with(SystemAccountRole::SalesReturns)->andReturn($this->returnsAccount);
            $mock->shouldReceive('missingRequiredRoles')->andReturn([]);
        });
        $this->mock(AccountingPeriodManager::class, function ($mock): void {
            $mock->shouldReceive('assertPostable')->andReturn(new AccountingPeriod);
        });
        $this->actingAs($this->actor);
    }

    private function requestReturn(string $quantity = '5'): SalesReturn
    {
        return app(SalesReturnWorkflow::class)->create([
            'sales_invoice_id' => $this->invoice->id, 'warehouse_id' => $this->warehouse->id, 'return_date' => today()->toDateString(),
            'reason' => 'Customer return', 'lines' => [['sales_invoice_line_id' => $this->invoice->lines()->first()->id, 'quantity' => $quantity]],
        ], $this->actor);
    }

    private function step(SalesReturn $return, string $step, array $data = []): TestResponse
    {
        return $this->patch(route('sales.returns.transition', ['salesReturn' => $return, 'step' => $step]), $data);
    }

    private function inspect(SalesReturn $return, string $accepted = '3'): void
    {
        $id = $return->lines()->first()->id;
        $this->step($return, 'approve')->assertSessionHasNoErrors();
        $this->step($return, 'receive', ['lines' => [['id' => $id, 'quantity' => $return->lines()->first()->requested_quantity]]])->assertSessionHasNoErrors();
        $this->step($return, 'inspect', ['lines' => [['id' => $id, 'quantity' => $accepted, 'notes' => 'Remaining quantity damaged']]])->assertSessionHasNoErrors();
    }

    public function test_full_workflow_restocks_only_accepted_goods_and_posts_balanced_credit_without_changing_sale(): void
    {
        $original = $this->invoice->fresh()->getRawOriginal();
        $return = $this->requestReturn();
        $this->get(route('sales.returns.index'))->assertOk()->assertSee($return->number);
        $this->get(route('sales.returns.show', $return))->assertOk()->assertSee('Approve Request');
        $this->get(route('sales.returns.create', ['invoice_id' => $this->invoice->id]))->assertOk()->assertSee('Vitamin A');
        $this->inspect($return);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->step($return, 'accept')->assertSessionHasNoErrors();
        $this->assertDatabaseHas('stock_balances', ['quantity' => 3, 'total_value' => 15]);
        $this->assertDatabaseCount('stock_movements', 1);
        $this->step($return, 'accept')->assertForbidden();
        $this->step($return, 'credit')->assertSessionHasNoErrors();
        $note = SalesCreditNote::firstOrFail();
        $this->assertSame('31.8000', $note->total);
        $this->assertSame('86.0000', $this->invoice->fresh()->outstanding());
        $this->step($return, 'post')->assertSessionHasNoErrors();
        $this->step($return, 'post')->assertForbidden();
        $note->refresh();
        $this->assertSame('posted', $note->status);
        $this->assertSame('54.2000', $this->invoice->fresh()->outstanding());
        $journal = $note->journalEntry;
        $this->assertSame($journal->total_debit, $journal->total_credit);
        $this->assertDatabaseHas('journal_lines', ['journal_entry_id' => $journal->id, 'account_id' => $this->returnsAccount->id, 'debit' => 30]);
        $this->assertDatabaseHas('journal_lines', ['journal_entry_id' => $journal->id, 'customer_id' => $this->invoice->customer_id, 'credit' => 31.8]);
        $this->assertSame($original, $this->invoice->fresh()->getRawOriginal());
        $this->assertDatabaseCount('sales_invoices', 1);
        $this->get(route('sales.returns.show', $return))->assertOk()->assertSee($note->number);
    }

    public function test_validation_permissions_and_invalid_transitions(): void
    {
        $this->post(route('sales.returns.store'), [])->assertSessionHasErrors(['sales_invoice_id', 'lines', 'reason']);
        $return = $this->requestReturn();
        $this->step($return, 'accept')->assertForbidden();
        $this->actingAs(User::factory()->create(['status' => UserStatus::Active]));
        $this->get(route('sales.returns.index'))->assertForbidden();
        $this->get(route('sales.returns.show', $return))->assertForbidden();
        $this->step($return, 'approve')->assertForbidden();
        $this->assertSame('requested', $return->fresh()->status);
    }

    public function test_multiple_requests_cannot_exceed_original_quantity(): void
    {
        $this->requestReturn('7');
        $this->expectException(BusinessRuleException::class);
        $this->requestReturn('4');
    }

    public function test_received_and_inspected_quantities_are_capped_and_rejection_requires_reason(): void
    {
        $return = $this->requestReturn();
        $id = $return->lines()->first()->id;
        $this->step($return, 'approve')->assertSessionHasNoErrors();
        $this->step($return, 'receive', ['lines' => [['id' => $id, 'quantity' => 6]]])->assertSessionHasErrors();
        $this->step($return, 'receive', ['lines' => [['id' => $id, 'quantity' => 5]]])->assertSessionHasNoErrors();
        $this->step($return, 'inspect', ['lines' => [['id' => $id, 'quantity' => 6]]])->assertSessionHasErrors();
        $this->step($return, 'inspect', ['lines' => [['id' => $id, 'quantity' => 3]]])->assertSessionHasErrors();
        $this->assertSame('received', $return->fresh()->status);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_closed_period_rolls_back_stock_and_acceptance(): void
    {
        $return = $this->requestReturn();
        $this->inspect($return);
        $this->mock(AccountingPeriodManager::class, function ($mock): void {
            $mock->shouldReceive('assertPostable')->andThrow(BusinessRuleException::make('Period closed.'));
        });
        $this->app->forgetInstance(JournalPoster::class);
        $this->step($return, 'accept')->assertSessionHasErrors();
        $this->assertSame('inspected', $return->fresh()->status);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseCount('stock_balances', 0);
    }

    public function test_all_failed_inspection_does_not_restock_or_credit(): void
    {
        $return = $this->requestReturn();
        $this->inspect($return, '0');
        $this->step($return, 'accept')->assertSessionHasNoErrors();
        $this->assertSame('rejected', $return->fresh()->status);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->step($return, 'credit')->assertForbidden();
        $this->assertDatabaseCount('sales_credit_notes', 0);
    }

    public function test_nonposted_original_invoice_is_not_returnable(): void
    {
        $this->invoice->update(['status' => 'draft']);
        $this->expectException(BusinessRuleException::class);
        $this->requestReturn();
    }

    public function test_partial_returns_release_failed_quantities_and_cumulative_credits_match_original(): void
    {
        foreach ([['5', '3'], ['7', '7']] as [$requested,$accepted]) {
            $return = $this->requestReturn($requested);
            $this->inspect($return, $accepted);
            foreach (['accept', 'credit', 'post'] as $step) {
                $this->step($return, $step)->assertSessionHasNoErrors();
            }
        }
        $this->assertEquals(106, SalesCreditNote::sum('total'));
        $this->assertDatabaseHas('stock_balances', ['quantity' => 10, 'total_value' => 50]);
        $this->assertSame('0.0000', $this->invoice->fresh()->outstanding());
        $this->expectException(BusinessRuleException::class);
        $this->requestReturn('1');
    }

    public function test_http_creation_accepts_numeric_quantity_and_rejects_foreign_lines(): void
    {
        $payload = ['sales_invoice_id' => $this->invoice->id, 'warehouse_id' => $this->warehouse->id,
            'return_date' => today()->toDateString(), 'reason' => 'Returned by customer',
            'lines' => [['sales_invoice_line_id' => $this->invoice->lines()->first()->id, 'quantity' => 2]]];
        $this->post(route('sales.returns.store'), $payload)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('sales_return_lines', ['requested_quantity' => 2]);
        $payload['lines'][0]['sales_invoice_line_id'] = 999999;
        $this->post(route('sales.returns.store'), $payload)->assertSessionHasErrors();
        $this->assertDatabaseCount('sales_returns', 1);
    }

    public function test_original_journal_cannot_be_reversed_with_an_active_return(): void
    {
        $this->requestReturn();
        $journal = $this->invoice->journalEntry;
        $journal->update(['source_type' => SalesInvoice::class, 'source_id' => $this->invoice->id]);
        $this->expectException(BusinessRuleException::class);
        app(JournalPoster::class)->reverse($journal, $this->actor->id, 'Reverse original');
    }

    public function test_inventory_return_journal_cannot_be_reversed_independently(): void
    {
        $return = $this->requestReturn();
        $this->inspect($return);
        $this->step($return,'accept')->assertSessionHasNoErrors();
        $journal = JournalEntry::findOrFail($return->fresh()->inventory_journal_id);
        $this->expectException(BusinessRuleException::class);
        app(JournalPoster::class)->reverse($journal,$this->actor->id,'Reverse inventory');
    }
}
