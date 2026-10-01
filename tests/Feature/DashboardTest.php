<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\Organization;
use App\Models\User;
use App\Services\Settings\DashboardMetricsService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Schema::disableForeignKeyConstraints();
        config(['activitylog.enabled' => false]);
        $this->travelTo(CarbonImmutable::parse('2026-09-29 12:00:00'));
        foreach ([
            '0001_01_01_000000_create_users_table.php',
            '2026_09_11_075003_2026_01_01_000002_add_erp_fields_to_users_table.php',
            '2026_09_28_163338_add_soft_deletes_to_users_table.php',
            '2026_09_11_062704_create_permission_tables.php',
            '2026_09_11_074816_create_organizations_table.php',
            '2026_09_11_075115_2026_01_01_000003_create_document_sequences_table.php',
            '2026_09_11_075250_2026_01_01_000005_create_financial_years_table.php',
            '2026_01_08_000001_create_accounts_table.php',
            '2026_09_14_173239_create_journal_entries_table.php',
            '2026_09_14_173246_create_journal_lines_table.php',
            '2026_09_11_085100_create_activity_log_table.php',
            '2026_09_11_085101_add_event_column_to_activity_log_table.php',
            '2026_09_11_085102_add_batch_uuid_column_to_activity_log_table.php',
            '2026_01_04_000003_create_items_table.php',
        ] as $migration) {
            $named = [
                '2026_09_11_085100_create_activity_log_table.php' => \CreateActivityLogTable::class,
                '2026_09_11_085101_add_event_column_to_activity_log_table.php' => \AddEventColumnToActivityLogTable::class,
                '2026_09_11_085102_add_batch_uuid_column_to_activity_log_table.php' => \AddBatchUuidColumnToActivityLogTable::class,
            ];
            if (isset($named[$migration])) {
                require_once database_path('migrations/'.$migration);
                (new $named[$migration])->up();
            } else {
                (require database_path('migrations/'.$migration))->up();
            }
        }
        foreach ([
            'Sales/2026_03_02_000001_create_sales_invoices_table.php',
            'Sales/2026_09_16_050827_create_sales_orders_table.php',
            'Procurement/2026_09_15_155656_create_supplier_invoices_table.php',
            'Procurement/2026_09_21_112300_create_vendor_invoices_table.php',
            'Procurement/2026_01_13_000001_create_purchase_orders_table.php',
            'Procurement/2026_01_12_000001_create_request_for_quotations_table.php',
            'Expense/2026_09_19_083154_create_expense_claims_table.php',
            'Expense/2026_09_21_072025_add_manager_deduction_to_expense_claims.php',
            'Inventory/2026_09_16_124133_create_stock_balances_table.php',
            'Manufacturing/2026_06_01_000002_create_production_orders_table.php',
            'Hr/2026_09_18_183133_create_employees_table.php',
            'Hr/2026_09_18_185842_create_leave_requests_table.php',
            'Hr/2026_09_18_190213_create_payroll_runs_table.php',
            'FixedAssets/2026_09_19_110621_create_fixed_assets_table.php',
        ] as $migration) {
            [$module, $file] = explode('/', $migration);
            if ($file === '2026_09_21_112300_create_vendor_invoices_table.php') {
                // SQLite index names are global; these two legacy migrations reuse a name.
                Schema::table('supplier_invoices', fn ($table) => $table->dropUnique('si_vendor_ref_unique'));
            }
            (require base_path('Modules/'.$module.'/database/migrations/'.$file))->up();
        }
        (require base_path('Modules/Sales/database/migrations/2026_10_01_052922_create_sales_returns_tables.php'))->up();
        Organization::create(['name' => 'ACEMIX', 'currency_code' => 'PKR']);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_unprivileged_users_do_not_receive_module_data(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Active]);
        $this->actingAs($user)->get(route('dashboard'))->assertOk()
            ->assertDontSee('Sales invoiced')->assertDontSee('Cash &amp; bank', false)
            ->assertDontSee('Active users')->assertDontSee('id="financial-chart"', false);
        $data = app(DashboardMetricsService::class)->forUser($user, CarbonImmutable::parse('2026-09-01'), 'PKR');
        $this->assertSame([], $data['cards']);
        $this->assertSame([], $data['charts']['series']);
    }

    public function test_all_authorized_modules_render_with_valid_links(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Active]);
        $user->assignRole(Role::findOrCreate('super-admin', 'web'));
        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk()->assertSee('Financial Activity')->assertSee('Manufacturing')->assertSee('Human Resources')->assertSee('Fixed Assets')->assertSee('Reports');
        foreach ($response->viewData('dashboard')['modules'] as $module) {
            $response->assertSee(route($module['route']));
        }
    }

    public function test_sales_totals_exclude_drafts_deleted_records_and_other_currencies(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Active]);
        $user->givePermissionTo(Permission::findOrCreate('sales.view', 'web'));
        foreach ([
            ['posted', 'PKR', '2026-09-03', 500, 100, null],
            ['paid', 'PKR', '2026-08-03', 200, 200, null],
            ['draft', 'PKR', '2026-09-03', 900, 0, null],
            ['posted', 'USD', '2026-09-03', 800, 0, null],
            ['posted', 'PKR', '2026-09-03', 700, 0, now()],
        ] as $index => [$status, $currency, $date, $total, $paid, $deleted]) {
            DB::table('sales_invoices')->insert(['number' => 'INV-'.$index, 'customer_id' => 1, 'invoice_date' => $date, 'due_date' => $date,
                'status' => $status, 'currency_code' => $currency, 'total' => $total, 'paid_amount' => $paid, 'deleted_at' => $deleted]);
        }
        $data = app(DashboardMetricsService::class)->forUser($user, CarbonImmutable::parse('2026-09-01'), 'PKR');
        $this->assertSame([0.0, 0.0, 0.0, 0.0, 200.0, 500.0], $data['charts']['series'][0]['data']);
        $this->assertSame(400.0, collect($data['cards'])->firstWhere('label', 'Receivables')['value']);
        $this->assertSame(['Sales'], array_column($data['modules'], 'name'));
    }

    public function test_month_filter_and_invalid_input(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Active]);
        $this->actingAs($user)->get(route('dashboard', ['month' => '2026-08']))->assertOk()->assertSee('August 2026');
        $this->getJson(route('dashboard', ['month' => 'invalid']))->assertUnprocessable()->assertJsonValidationErrors('month');
        $this->getJson(route('dashboard', ['month' => '2027-01']))->assertUnprocessable()->assertJsonValidationErrors('month');
    }

    public function test_cash_balance_includes_reversal_pairs_and_excludes_drafts_and_foreign_currency(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Active]);
        $user->givePermissionTo(Permission::findOrCreate('accounting.view', 'web'));
        $account = DB::table('accounts')->insertGetId(['code' => 'CASH', 'name' => 'Cash', 'type' => 'asset', 'normal_balance' => 'debit', 'is_cash' => true]);
        foreach ([['posted', 'PKR', 500, 0], ['reversed', 'PKR', 200, 0], ['posted', 'PKR', 0, 200], ['draft', 'PKR', 900, 0], ['posted', 'USD', 800, 0]] as $index => [$status, $currency, $debit, $credit]) {
            $entry = DB::table('journal_entries')->insertGetId(['number' => 'JV-'.$index, 'entry_date' => '2026-09-01', 'description' => 'Cash movement', 'currency_code' => $currency, 'status' => $status]);
            DB::table('journal_lines')->insert(['journal_entry_id' => $entry, 'position' => 1, 'account_id' => $account, 'debit' => $debit, 'credit' => $credit]);
        }

        $data = app(DashboardMetricsService::class)->forUser($user, CarbonImmutable::parse('2026-09-01'), 'PKR');

        $this->assertSame(500.0, collect($data['cards'])->firstWhere('label', 'Cash & bank')['value']);
    }

    public function test_expense_trend_uses_approved_reimbursed_amount_and_preserves_status_counts(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Active]);
        $user->givePermissionTo(Permission::findOrCreate('expense.view', 'web'));
        foreach (['reimbursed', 'submitted', 'draft'] as $index => $status) {
            DB::table('expense_claims')->insert(['number' => 'EXP-'.$index, 'employee_id' => 1, 'expense_account_id' => 1, 'expense_date' => '2026-09-03', 'amount' => 100, 'approved_amount' => 80, 'currency_code' => 'PKR', 'description' => 'Expense', 'status' => $status, 'created_by' => $user->id]);
        }

        $data = app(DashboardMetricsService::class)->forUser($user, CarbonImmutable::parse('2026-09-01'), 'PKR');

        $this->assertSame(80.0, $data['charts']['series'][0]['data'][5]);
        $this->assertSame(1, $data['charts']['expenses']['submitted']);
        $this->assertSame(1, collect($data['queues'])->firstWhere('label', 'Expense claims awaiting review')['count']);
    }
}
