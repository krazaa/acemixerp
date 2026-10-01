<?php

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

pest()->extend(TestCase::class);

beforeEach(function () {
    // Scope this suite to report dependencies while the full migration chain is unordered.
    expect(config('database.default'))->toBe('sqlite');
    expect(config('database.connections.sqlite.database'))->toBe(':memory:');
    Schema::disableForeignKeyConstraints();
    config(['activitylog.enabled' => false]);
    foreach ([
        '0001_01_01_000000_create_users_table.php',
        '2026_09_11_075003_2026_01_01_000002_add_erp_fields_to_users_table.php',
        '2026_09_11_062704_create_permission_tables.php',
        '2026_01_03_000002_create_customers_table.php',
        '2026_01_03_000003_create_vendors_table.php',
        '2026_01_08_000001_create_accounts_table.php',
        '2026_01_08_000002_create_system_accounts_table.php',
        '2026_09_14_173239_create_journal_entries_table.php',
        '2026_09_14_173246_create_journal_lines_table.php',
    ] as $migration) {
        (require database_path('migrations/'.$migration))->up();
    }
});

function reportUser(): User
{
    Permission::findOrCreate('reports.financial', 'web');
    $user = User::factory()->create(['status' => UserStatus::Active]);
    $user->givePermissionTo('reports.financial');

    return $user;
}

function reportAccount(string $code, string $type = 'asset'): int
{
    return DB::table('accounts')->insertGetId([
        'code' => $code, 'name' => 'Account '.$code, 'type' => $type,
        'normal_balance' => $type === 'liability' ? 'credit' : 'debit',
    ]);
}

/** @param array<int, array<string, int|string>> $lines */
function reportJournal(string $date, array $lines, string $status = 'posted', string $currency = 'PKR'): int
{
    $id = DB::table('journal_entries')->insertGetId([
        'number' => 'J-'.(DB::table('journal_entries')->count() + 1),
        'entry_date' => $date, 'description' => 'Report test journal',
        'currency_code' => $currency, 'status' => $status,
    ]);
    foreach ($lines as $line) {
        DB::table('journal_lines')->insert(['journal_entry_id' => $id, 'debit' => 0, 'credit' => 0, ...$line]);
    }

    return $id;
}

it('requires authentication for each report', function (string $route) {
    $this->get(route($route))->assertRedirect(route('login'));
})->with(['reports.index', 'reports.accounts-payable', 'reports.accounts-receivable', 'reports.trial-balance', 'reports.balance-sheet']);

it('requires financial report permission', function (string $route) {
    $user = User::factory()->create(['status' => UserStatus::Active]);

    $this->actingAs($user)->get(route($route))->assertForbidden();
})->with(['reports.index', 'reports.accounts-payable', 'reports.accounts-receivable', 'reports.trial-balance', 'reports.balance-sheet']);

it('links to the dedicated report pages', function () {
    $this->actingAs(reportUser())->get(route('reports.index'))
        ->assertSee('href="'.route('reports.accounts-payable').'"', false)
        ->assertSee('href="'.route('reports.accounts-receivable').'"', false)
        ->assertSee('href="'.route('reports.trial-balance').'"', false)
        ->assertSee('href="'.route('reports.balance-sheet').'"', false);
});

it('nets payments against party balances and excludes unrelated, future and draft entries', function (bool $payables) {
    $account = reportAccount('CONTROL', $payables ? 'liability' : 'asset');
    $other = reportAccount('OTHER');
    $table = $payables ? 'vendors' : 'customers';
    $column = $payables ? 'vendor_id' : 'customer_id';
    $role = $payables ? 'accounts_payable' : 'accounts_receivable';
    $increase = $payables ? 'credit' : 'debit';
    $decrease = $payables ? 'debit' : 'credit';
    $party = DB::table($table)->insertGetId(['code' => 'P1', 'name' => 'Party One']);
    DB::table('system_accounts')->insert(['role' => $role, 'account_id' => $account]);
    reportJournal('2025-12-31', [['account_id' => $account, $column => $party, $increase => '1000.1250']]);
    reportJournal('2026-01-15', [['account_id' => $account, $column => $party, $decrease => '250.0250']]);
    reportJournal('2026-01-15', [['account_id' => $other, $column => $party, $increase => '900']]);
    reportJournal('2026-02-01', [['account_id' => $account, $column => $party, $increase => '800']]);
    reportJournal('2026-01-15', [['account_id' => $account, $column => $party, $increase => '700']], 'draft');
    reportJournal('2026-01-15', [['account_id' => $account, $column => $party, $increase => '50']], 'posted', 'USD');

    $response = $this->actingAs(reportUser())->get(route($payables ? 'reports.accounts-payable' : 'reports.accounts-receivable', ['as_of' => '2026-01-31', 'currency' => 'PKR']));
    $response->assertSee('Party One')->assertSee('750.1000');
    $groups = $response->viewData('groups');
    expect($groups->keys()->all())->toBe(['PKR']);
    expect($groups['PKR']['balance'])->toBe('750.1000');
})->with([true, false]);

it('includes custom control accounts, advances and unassigned balances and filters parties', function () {
    $account = reportAccount('AP', 'liability');
    $custom = reportAccount('CUSTOM-AP', 'liability');
    DB::table('system_accounts')->insert(['role' => 'accounts_payable', 'account_id' => $account]);
    $party = DB::table('vendors')->insertGetId(['code' => 'V1', 'name' => 'Advance Vendor', 'ap_account_id' => $custom]);
    reportJournal('2026-01-01', [['account_id' => $custom, 'vendor_id' => $party, 'debit' => '25']]);
    reportJournal('2026-01-01', [['account_id' => $account, 'credit' => '100']]);

    $response = $this->actingAs(reportUser())->get(route('reports.accounts-payable', ['as_of' => '2026-01-31', 'party_id' => $party]));
    $response->assertSee('-25.0000')->assertDontSee('Unassigned party');
    expect($response->viewData('groups')['PKR']['rows'])->toHaveCount(1);
});

it('keeps reversed originals through their date and offsets them only on the reversal date', function () {
    $account = reportAccount('AR');
    $other = reportAccount('REVENUE', 'revenue');
    reportJournal('2026-01-01', [['account_id' => $account, 'debit' => '100'], ['account_id' => $other, 'credit' => '100']], 'reversed');
    reportJournal('2026-02-01', [['account_id' => $account, 'credit' => '100'], ['account_id' => $other, 'debit' => '100']]);
    $this->actingAs(reportUser());

    $before = $this->get(route('reports.trial-balance', ['as_of' => '2026-01-31']));
    expect($before->viewData('groups')['PKR']['debit'])->toBe('100.0000');
    $after = $this->get(route('reports.trial-balance', ['as_of' => '2026-02-01']));
    expect($after->viewData('groups')['PKR']['debit'])->toBe('0.0000');
    expect($after->viewData('groups')['PKR']['credit'])->toBe('0.0000');
});

it('shows net trial balances separately by currency including archived accounts', function () {
    $debit = reportAccount('ASSET');
    $credit = reportAccount('AP', 'liability');
    DB::table('accounts')->where('id', $debit)->update(['deleted_at' => now(), 'status' => 'archived']);
    reportJournal('2025-12-31', [['account_id' => $debit, 'debit' => '1000'], ['account_id' => $credit, 'credit' => '1000']]);
    reportJournal('2026-01-01', [['account_id' => $debit, 'credit' => '200'], ['account_id' => $credit, 'debit' => '200']]);
    reportJournal('2026-01-01', [['account_id' => $debit, 'debit' => '10'], ['account_id' => $credit, 'credit' => '10']], 'posted', 'USD');

    $response = $this->actingAs(reportUser())->get(route('reports.trial-balance', ['as_of' => '2026-01-31']));
    $response->assertSee('Debits and credits balance.')->assertSee('Account ASSET');
    $groups = $response->viewData('groups');
    expect($groups['PKR']['debit'])->toBe('800.0000');
    expect($groups['PKR']['credit'])->toBe('800.0000');
    expect($groups['USD']['debit'])->toBe('10.0000');
});

it('rejects invalid report filters', function (array $filters, string $field) {
    $this->actingAs(reportUser())->getJson(route('reports.accounts-payable', $filters))
        ->assertUnprocessable()->assertJsonValidationErrors($field);
})->with([
    [['as_of' => '2026-02-30'], 'as_of'],
    [['as_of' => 'yesterday'], 'as_of'],
    [['currency' => 'invalid'], 'currency'],
    [['party_id' => 999], 'party_id'],
]);

it('renders an empty report', function (string $route) {
    $this->actingAs(reportUser())->get(route($route, ['as_of' => '2026-01-31']))->assertSee('match the selected filters.');
})->with(['reports.accounts-payable', 'reports.accounts-receivable', 'reports.trial-balance', 'reports.balance-sheet']);

it('omits settled balances but includes control balances without a party', function () {
    $account = reportAccount('AP', 'liability');
    DB::table('system_accounts')->insert(['role' => 'accounts_payable', 'account_id' => $account]);
    $party = DB::table('vendors')->insertGetId(['code' => 'SETTLED', 'name' => 'Settled Vendor']);
    reportJournal('2026-01-01', [['account_id' => $account, 'vendor_id' => $party, 'credit' => '100']]);
    reportJournal('2026-01-02', [['account_id' => $account, 'vendor_id' => $party, 'debit' => '100']]);
    reportJournal('2026-01-02', [['account_id' => $account, 'credit' => '40']]);

    $response = $this->actingAs(reportUser())->get(route('reports.accounts-payable', ['as_of' => '2026-01-31']));
    $response->assertSee('Unassigned party');
    $group = $response->viewData('groups')['PKR'];
    expect($group['rows'])->toHaveCount(1);
    expect($group['balance'])->toBe('40.0000');
});

it('reports an unbalanced ledger without hiding the difference', function () {
    $account = reportAccount('ASSET');
    reportJournal('2026-01-01', [['account_id' => $account, 'debit' => '0.0123']]);

    $this->actingAs(reportUser())->get(route('reports.trial-balance', ['as_of' => '2026-01-31']))
        ->assertSee('Out of balance by 0.0123 PKR.');
});

it('rejects inactive users even when they have report permission', function () {
    $user = reportUser();
    $user->update(['status' => UserStatus::Suspended]);

    $this->actingAs($user)->get(route('reports.accounts-payable'))->assertRedirect(route('login'));
});

it('shows net outstanding balances and currency totals on aging reports', function (bool $payables) {
    $account = reportAccount('AGING', $payables ? 'liability' : 'asset');
    $column = $payables ? 'vendor_id' : 'customer_id';
    $table = $payables ? 'vendors' : 'customers';
    $increase = $payables ? 'credit' : 'debit';
    $decrease = $payables ? 'debit' : 'credit';
    $party = DB::table($table)->insertGetId(['code' => 'AGE1', 'name' => 'Aging Party']);
    DB::table('system_accounts')->insert(['role' => $payables ? 'accounts_payable' : 'accounts_receivable', 'account_id' => $account]);
    reportJournal('2026-01-01', [['account_id' => $account, $column => $party, $increase => '1000']]);
    reportJournal('2026-02-15', [['account_id' => $account, $column => $party, $decrease => '250']]);
    reportJournal('2026-03-01', [['account_id' => $account, $column => $party, $decrease => '750']]);
    reportJournal('2026-01-01', [['account_id' => $account, $column => $party, $increase => '10']], 'posted', 'USD');

    $response = $this->actingAs(reportUser())->get(route($payables ? 'gl.aging.payables' : 'gl.aging.receivables', ['as_of' => '2026-02-28']));
    $response->assertSee('Outstanding Balance')->assertSee('Total Outstanding Balance')->assertSee('750.00');
    $rows = $response->viewData('rows')->keyBy('currency_code');
    expect($rows['PKR']->total)->toBe('750.0000');
    expect($rows['PKR']->days_31_60)->toBe('1000.0000');
    expect($rows['PKR']->days_1_30)->toBe('-250.0000');
    expect($rows['USD']->total)->toBe('10.0000');
})->with([true, false]);

it('omits fully settled parties from aging', function () {
    $account = reportAccount('AGING-AP', 'liability');
    DB::table('system_accounts')->insert(['role' => 'accounts_payable', 'account_id' => $account]);
    $party = DB::table('vendors')->insertGetId(['code' => 'SETTLED', 'name' => 'Settled Vendor']);
    reportJournal('2026-01-01', [['account_id' => $account, 'vendor_id' => $party, 'credit' => '100']], 'reversed');
    reportJournal('2026-01-02', [['account_id' => $account, 'vendor_id' => $party, 'debit' => '100']]);

    $response = $this->actingAs(reportUser())->get(route('gl.aging.payables', ['as_of' => '2026-01-31']));
    $response->assertSee('No outstanding balances.');
    expect($response->viewData('rows'))->toBeEmpty();
});

it('builds a balance sheet with contra balances and unclosed earnings by currency', function () {
    $cash = reportAccount('CASH');
    $contra = reportAccount('CONTRA');
    $debt = reportAccount('DEBT', 'liability');
    $capital = reportAccount('CAPITAL', 'equity');
    $revenue = reportAccount('SALES', 'revenue');
    $expense = reportAccount('EXPENSE', 'expense');
    $cogs = reportAccount('COGS', 'cogs');
    DB::table('accounts')->where('id', $contra)->update(['deleted_at' => now(), 'status' => 'archived']);
    reportJournal('2025-12-31', [
        ['account_id' => $cash, 'debit' => '1000.1250'],
        ['account_id' => $contra, 'credit' => '50'],
        ['account_id' => $debt, 'credit' => '200'],
        ['account_id' => $capital, 'credit' => '500'],
        ['account_id' => $revenue, 'credit' => '400.1250'],
        ['account_id' => $expense, 'debit' => '100'],
        ['account_id' => $cogs, 'debit' => '50'],
    ]);
    reportJournal('2026-02-01', [['account_id' => $cash, 'debit' => '99']]);
    reportJournal('2026-01-01', [['account_id' => $cash, 'debit' => '88']], 'draft');
    reportJournal('2026-01-01', [['account_id' => $cash, 'debit' => '77']], 'cancelled');
    reportJournal('2026-01-01', [['account_id' => $cash, 'debit' => '10'], ['account_id' => $capital, 'credit' => '10']], 'posted', 'USD');

    $response = $this->actingAs(reportUser())->get(route('reports.balance-sheet', ['as_of' => '2026-01-31']));
    $response->assertSee('Unclosed earnings / (loss)')->assertSee('Account CONTRA')->assertSee('-50.0000');
    $groups = $response->viewData('groups');
    expect($groups['PKR']['asset'])->toBe('950.1250');
    expect($groups['PKR']['liability'])->toBe('200.0000');
    expect($groups['PKR']['earnings'])->toBe('250.1250');
    expect($groups['PKR']['equity'])->toBe('750.1250');
    expect($groups['PKR']['difference'])->toBe('0.0000');
    expect($groups['USD']['asset'])->toBe('10.0000');
});

it('includes reversals on their dates and filters balance sheet currencies', function () {
    $cash = reportAccount('CASH');
    $revenue = reportAccount('SALES', 'revenue');
    reportJournal('2026-01-01', [['account_id' => $cash, 'debit' => '100'], ['account_id' => $revenue, 'credit' => '100']], 'reversed');
    reportJournal('2026-02-01', [['account_id' => $cash, 'credit' => '100'], ['account_id' => $revenue, 'debit' => '100']]);
    reportJournal('2026-01-01', [['account_id' => $cash, 'debit' => '20']], 'posted', 'USD');
    $this->actingAs(reportUser());

    $before = $this->get(route('reports.balance-sheet', ['as_of' => '2026-01-31', 'currency' => 'PKR']));
    expect($before->viewData('groups')->keys()->all())->toBe(['PKR']);
    expect($before->viewData('groups')['PKR']['earnings'])->toBe('100.0000');
    $after = $this->get(route('reports.balance-sheet', ['as_of' => '2026-02-01', 'currency' => 'PKR']));
    expect($after->viewData('groups')['PKR']['asset'])->toBe('0.0000');
    expect($after->viewData('groups')['PKR']['earnings'])->toBe('0.0000');
});

it('moves closed earnings into equity without double counting losses', function () {
    $cash = reportAccount('CASH');
    $expense = reportAccount('EXPENSE', 'expense');
    $retained = reportAccount('RETAINED', 'equity');
    reportJournal('2026-01-01', [['account_id' => $cash, 'credit' => '25'], ['account_id' => $expense, 'debit' => '25']]);
    reportJournal('2026-01-31', [['account_id' => $expense, 'credit' => '25'], ['account_id' => $retained, 'debit' => '25']]);
    $this->actingAs(reportUser());

    $before = $this->get(route('reports.balance-sheet', ['as_of' => '2026-01-30']));
    expect($before->viewData('groups')['PKR']['earnings'])->toBe('-25.0000');
    $after = $this->get(route('reports.balance-sheet', ['as_of' => '2026-01-31']));
    expect($after->viewData('groups')['PKR']['earnings'])->toBe('0.0000');
    expect($after->viewData('groups')['PKR']['equity'])->toBe('-25.0000');
    expect($after->viewData('groups')['PKR']['difference'])->toBe('0.0000');
});

it('flags an unbalanced ledger and escapes account names on the balance sheet', function () {
    $cash = reportAccount('CASH');
    DB::table('accounts')->where('id', $cash)->update(['name' => '<script>alert(1)</script>']);
    reportJournal('2026-01-01', [['account_id' => $cash, 'debit' => '0.0001']]);

    $this->actingAs(reportUser())->get(route('reports.balance-sheet', ['as_of' => '2026-01-31']))
        ->assertSee('Out of balance: 0.0001')->assertSee('<script>alert(1)</script>')->assertDontSee('<script>alert(1)</script>', false);
});

it('validates balance sheet filters', function () {
    $this->actingAs(reportUser())->getJson(route('reports.balance-sheet', ['as_of' => '2026-02-30', 'currency' => 'invalid']))
        ->assertUnprocessable()->assertJsonValidationErrors(['as_of', 'currency']);
});
