<?php

use App\Contracts\AccountingPeriodManager;
use App\Contracts\JournalPoster;
use App\Contracts\SequenceGenerator;
use App\Contracts\SystemAccountManager;
use App\Data\JournalEntryData;
use App\Data\JournalLineData;
use App\Enums\UserStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ViewErrorBag;
use Modules\Expense\Enums\ExpenseClaimStatus;
use Modules\Expense\Models\ExpenseClaim;
use Modules\Expense\Services\ExpenseEvidencePdfHtml;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

pest()->extend(TestCase::class);

beforeEach(function () {
    expect(config('database.default'))->toBe('sqlite');
    expect(config('database.connections.sqlite.database'))->toBe(':memory:');
    Schema::disableForeignKeyConstraints();
    config(['activitylog.enabled' => false]);
    foreach ([
        '0001_01_01_000000_create_users_table.php',
        '2026_09_11_075003_2026_01_01_000002_add_erp_fields_to_users_table.php',
        '2026_09_11_062704_create_permission_tables.php',
        '2026_09_11_112209_create_departments_table.php',
        '2026_01_08_000001_create_accounts_table.php',
        '2026_01_08_000002_create_system_accounts_table.php',
        '2026_09_14_173239_create_journal_entries_table.php',
        '2026_09_14_173246_create_journal_lines_table.php',
    ] as $migration) {
        (require database_path('migrations/'.$migration))->up();
    }
    foreach ([
        '2026_09_19_083154_create_expense_claims_table.php',
        '2026_09_21_072025_add_manager_deduction_to_expense_claims.php',
        '2026_09_21_075106_create_expense_claim_lines_table.php',
        '2026_09_21_080726_add_manager_review_to_expense_claim_lines.php',
        '2026_09_21_084611_add_manager_deduction_to_expense_claim_lines.php',
        '2026_09_29_102402_add_evidence_text_to_expense_claims_table.php',
    ] as $migration) {
        (require base_path('Modules/Expense/database/migrations/'.$migration))->up();
    }
});

/** @param array<int, string> $permissions */
function expenseApprovalUser(bool $superAdmin = false, array $permissions = []): User
{
    $user = User::factory()->create(['status' => UserStatus::Active]);
    if ($superAdmin) {
        $user->assignRole(Role::findOrCreate('super-admin', 'web'));
    }
    foreach ($permissions as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    return $user;
}

/** @param array<string, mixed> $attributes */
function expenseApprovalClaim(User $manager, array $attributes = []): ExpenseClaim
{
    $department = DB::table('departments')->insertGetId([
        'code' => 'D-'.DB::table('departments')->count(),
        'name' => 'Department '.DB::table('departments')->count(),
        'manager_id' => $manager->id,
    ]);
    $account = DB::table('accounts')->insertGetId([
        'code' => 'EXP-'.DB::table('accounts')->count(), 'name' => 'Expense', 'type' => 'expense', 'normal_balance' => 'debit',
    ]);
    $claim = ExpenseClaim::query()->create([
        'number' => 'EXP-'.ExpenseClaim::query()->count(), 'employee_id' => 1,
        'department_id' => $department, 'expense_account_id' => $account,
        'expense_date' => '2026-09-01', 'amount' => '100.0000', 'currency_code' => 'PKR',
        'description' => 'Travel expense', 'status' => ExpenseClaimStatus::Submitted,
        'created_by' => $manager->id, ...$attributes,
    ]);
    $claim->lines()->create([
        'line_number' => 1, 'expense_date' => '2026-09-01',
        'expense_account_id' => $account, 'amount' => '100.0000',
    ]);

    return $claim;
}

it('downloads an authorized expense as a PDF with safe table evidence', function () {
    (require database_path('migrations/2026_09_28_163338_add_soft_deletes_to_users_table.php'))->up();
    Schema::create('employees', function (Blueprint $table) {
        $table->id();
        $table->string('first_name');
        $table->string('last_name');
    });
    $user = expenseApprovalUser();
    $claim = expenseApprovalClaim($user, ['evidence_text' => '<table><tr><td>Receipt</td><td>100</td></tr></table>']);

    $response = $this->actingAs($user)->get(route('expense.pdf', $claim));

    $response->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertDownload('exp-0.pdf');
    expect($response->getContent())->toStartWith('%PDF-');
});

it('protects expense PDF downloads from guests and unrelated users', function () {
    $claim = expenseApprovalClaim(expenseApprovalUser());
    $this->get(route('expense.pdf', $claim))->assertRedirect(route('login'));
    $this->actingAs(expenseApprovalUser())->get(route('expense.pdf', $claim))->assertForbidden();
});

it('preserves evidence tables but excludes executable content and resource URLs from PDFs', function () {
    $html = app(ExpenseEvidencePdfHtml::class)->render('<style>body{background:url(file:///etc/passwd)}</style><script>alert(1)</script><table onclick="alert(2)"><tr><td colspan="2" style="background:url(https://example.com)">Receipt &amp; total</td></tr></table><img src="file:///etc/passwd">');
    expect($html)->toBe('<table><tr><td colspan="2">Receipt &amp; total</td></tr></table>');
});

it('saves and clears evidence text when editing a draft expense', function (?string $text) {
    $user = expenseApprovalUser(true);
    $claim = expenseApprovalClaim($user, ['status' => 'draft', 'evidence_text' => 'Old receipt']);

    $this->actingAs($user)->patch(route('expense.update', $claim), [
        'notes' => 'Travel expense', 'evidence_text' => $text,
        'lines' => [['expense_date' => '2026-09-01', 'expense_account_id' => $claim->expense_account_id, 'amount' => 100]],
    ])->assertSessionHasNoErrors()->assertRedirect(route('expense.show', $claim));

    $this->assertDatabaseHas('expense_claims', ['id' => $claim->id, 'evidence_text' => $text]);
})->with(["Receipt 123\nPaid in cash", '<table><tbody><tr><td>Receipt 123</td><td>100.00</td></tr></tbody></table>', null]);

it('rejects oversized evidence text without changing the expense', function () {
    $user = expenseApprovalUser(true);
    $claim = expenseApprovalClaim($user, ['status' => 'draft', 'evidence_text' => 'Original receipt']);

    $this->actingAs($user)->patch(route('expense.update', $claim), [
        'notes' => 'Travel expense', 'evidence_text' => str_repeat('a', 10001),
        'lines' => [['expense_date' => '2026-09-01', 'expense_account_id' => $claim->expense_account_id, 'amount' => 100]],
    ])->assertSessionHasErrors('evidence_text');

    $this->assertDatabaseHas('expense_claims', ['id' => $claim->id, 'evidence_text' => 'Original receipt']);
});

it('requires authentication for expense approvals', function (string $route) {
    $this->patch(route($route, 1))->assertRedirect(route('login'));
})->with(['expense.manager-approve', 'expense.ceo-approve', 'expense.ceo-reject', 'expense.reimburse']);

it('requires manager approved status for non-admin CEO approval', function (string $status) {
    $user = expenseApprovalUser(false, ['expense.ceo_approve']);
    $claim = expenseApprovalClaim($user, ['status' => $status]);

    $this->actingAs($user)->patch(route('expense.ceo-approve', $claim))->assertForbidden();

    expect($claim->fresh()->status->value)->toBe($status);
    expect($claim->fresh()->ceo_approved_at)->toBeNull();
    $this->assertDatabaseCount('journal_entries', 0);
})->with(['draft', 'submitted', 'ceo_approved', 'reimbursed', 'rejected']);

it('requires non-admin reviewers to be the assigned department manager', function () {
    $manager = expenseApprovalUser(false, ['expense.manager_approve']);
    $otherManager = expenseApprovalUser(false, ['expense.manager_approve']);
    $claim = expenseApprovalClaim($manager);

    $this->actingAs($otherManager)->patch(route('expense.manager-approve', $claim), [
        'line_reviews' => [['id' => $claim->lines->first()->id, 'decision' => 'approved']],
    ])->assertForbidden();

    expect($claim->fresh()->manager_approved_at)->toBeNull();
    expect($claim->lines->first()->fresh()->manager_decision)->toBeNull();
});

it('requires approval permissions for the assigned manager', function () {
    $manager = expenseApprovalUser();
    $claim = expenseApprovalClaim($manager);

    $this->actingAs($manager)->patch(route('expense.manager-approve', $claim))->assertForbidden();

    expect($claim->fresh()->status)->toBe(ExpenseClaimStatus::Submitted);
});

it('records manager review before enabling CEO approval and prevents repeated non-admin reviews', function () {
    $manager = expenseApprovalUser(false, ['expense.manager_approve']);
    $ceo = expenseApprovalUser(false, ['expense.ceo_approve']);
    $claim = expenseApprovalClaim($manager);
    $reviews = ['line_reviews' => [[
        'id' => $claim->lines->first()->id, 'decision' => 'approved',
        'deduction_amount' => '10', 'deduction_reason' => 'Personal travel',
    ]]];

    $this->actingAs($manager)->patch(route('expense.manager-approve', $claim), $reviews)->assertRedirect();

    $claim->refresh();
    expect($claim->status)->toBe(ExpenseClaimStatus::ManagerApproved);
    expect($claim->approved_amount)->toBe('90.0000');
    expect($claim->manager_approved_by)->toBe($manager->id);
    expect($claim->hasManagerApproval())->toBeTrue();
    expect($claim->lines->first()->manager_decision)->toBe('approved');
    expect(Gate::forUser($ceo)->allows('ceoApprove', $claim))->toBeTrue();
    $this->patch(route('expense.manager-approve', $claim), $reviews)->assertForbidden();
    $this->assertDatabaseCount('journal_entries', 0);
});

it('validates every expense line before recording manager approval', function () {
    $manager = expenseApprovalUser(false, ['expense.manager_approve']);
    $claim = expenseApprovalClaim($manager);

    $this->actingAs($manager)->patchJson(route('expense.manager-approve', $claim), [
        'line_reviews' => [['id' => $claim->lines->first()->id, 'decision' => 'rejected']],
    ])->assertUnprocessable()->assertJsonValidationErrors('line_reviews.0.rejection_reason');

    expect($claim->fresh()->hasManagerApproval())->toBeFalse();
});

it('allows super admins to request CEO approval without manager review', function (string $status) {
    $admin = expenseApprovalUser(true);
    $claim = expenseApprovalClaim($admin, ['status' => $status]);

    expect(Gate::forUser($admin)->allows('ceoApprove', $claim))->toBeTrue();
    $this->actingAs($admin)->patchJson(route('expense.ceo-approve', $claim))
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Employee Expense Payable system account must be mapped before CEO approval.');

    expect($claim->fresh()->manager_approved_at)->toBeNull();
    $this->assertDatabaseCount('journal_entries', 0);
})->with(['draft', 'submitted']);

it('persists the CEO rejection reason after manager approval', function () {
    $ceo = expenseApprovalUser(true);
    $claim = expenseApprovalClaim($ceo, [
        'status' => 'manager_approved', 'manager_approved_by' => $ceo->id,
        'manager_approved_at' => now(), 'approved_amount' => '100.0000',
    ]);

    $this->actingAs($ceo)->patch(route('expense.ceo-reject', $claim), ['rejection_reason' => 'Not a business expense'])->assertRedirect();

    expect($claim->fresh()->status)->toBe(ExpenseClaimStatus::Rejected);
    expect($claim->fresh()->rejection_reason)->toBe('Not a business expense');
    $this->assertDatabaseCount('journal_entries', 0);
});

it('shows super admin approval actions without requiring manager review', function () {
    $admin = expenseApprovalUser(true);
    $claim = expenseApprovalClaim($admin, ['status' => 'ceo_approved', 'ceo_approved_at' => now(), 'journal_entry_id' => 78,
        'evidence_text' => '<table><tr><td>Receipt</td></tr></table><script>alert(1)</script>',
    ]);
    $claim->setRelation('employee', null)->setRelation('expenseAccount', null)->setRelation('evidences', collect());
    $claim->department->setRelation('manager', $admin);
    $claim->lines->each(fn ($line) => $line->setRelation('expenseAccount', null));
    $this->actingAs($admin);

    $this->view('expense::show', ['claim' => $claim, 'banks' => collect(), 'expenseAccounts' => collect(), 'errors' => new ViewErrorBag])
        ->assertDontSee('Manager approval is missing.')
        ->assertSee('title="Receipt evidence" sandbox', false)
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('<table><tr><td>Receipt</td></tr></table><script>alert(1)</script>')
        ->assertSee('Not reviewed')
        ->assertSee('CEO Approve</button>', false)
        ->assertSee('Complete Manager Review')
        ->assertSee('Post Reimbursement');
});

it('shows manager review for a submitted claim to an owner with super admin access', function () {
    $admin = expenseApprovalUser(true);
    $admin->assignRole(Role::findOrCreate('owner', 'web'));
    $claim = expenseApprovalClaim($admin);
    $claim->setRelation('employee', null)->setRelation('expenseAccount', null)->setRelation('evidences', collect());
    $claim->department->setRelation('manager', $admin);
    $claim->lines->each(fn ($line) => $line->setRelation('expenseAccount', null));
    $this->actingAs($admin);

    expect($claim->manager_approved_by)->toBeNull();
    $this->view('expense::show', ['claim' => $claim, 'banks' => collect(), 'expenseAccounts' => collect(), 'errors' => new ViewErrorBag])
        ->assertSee('Complete Manager Review')
        ->assertSee(route('expense.manager-approve', $claim));
});

it('hides finance reimbursement after payment even for super admin', function () {
    $admin = expenseApprovalUser(true);
    $claim = expenseApprovalClaim($admin, ['status' => ExpenseClaimStatus::Reimbursed, 'reimbursed_at' => now()]);
    $claim->setRelation('employee', null)->setRelation('expenseAccount', null)->setRelation('evidences', collect());
    $claim->department->setRelation('manager', $admin);
    $claim->lines->each(fn ($line) => $line->setRelation('expenseAccount', null));
    $this->actingAs($admin);

    $this->view('expense::show', ['claim' => $claim, 'banks' => collect(), 'expenseAccounts' => collect(), 'errors' => new ViewErrorBag])
        ->assertDontSee('card-header bg-white fw-semibold">Finance Reimbursement', false)
        ->assertDontSee('Post Reimbursement');
});

it('rechecks a locked claim when another request has already advanced its status', function (string $route, string $initial, string $advanced) {
    $admin = expenseApprovalUser(false, ['expense.create', 'expense.manager_approve', 'expense.ceo_approve']);
    $claim = expenseApprovalClaim($admin, [
        'status' => $initial, 'manager_approved_by' => $admin->id,
        'manager_approved_at' => now(), 'approved_amount' => '100.0000',
    ]);
    Route::bind('claim', function (string $id) use ($advanced): ExpenseClaim {
        $bound = ExpenseClaim::query()->findOrFail($id);
        DB::table('expense_claims')->where('id', $id)->update(['status' => $advanced]);

        return $bound;
    });

    $this->actingAs($admin)->patch(route($route, $claim), [
        'line_reviews' => [['id' => $claim->lines->first()->id, 'decision' => 'approved']],
        'rejection_reason' => 'Rejected after review',
    ])->assertForbidden();

    expect($claim->fresh()->status->value)->toBe($advanced);
    $this->assertDatabaseCount('journal_entries', 0);
})->with([
    ['expense.submit', 'draft', 'submitted'],
    ['expense.manager-approve', 'submitted', 'manager_approved'],
    ['expense.ceo-approve', 'manager_approved', 'ceo_approved'],
    ['expense.ceo-reject', 'manager_approved', 'ceo_approved'],
]);

it('allows finance reimbursement of CEO approved claims', function () {
    $finance = expenseApprovalUser(false, ['expense.reimburse']);
    $claim = expenseApprovalClaim($finance, [
        'status' => 'ceo_approved', 'manager_approved_by' => $finance->id,
        'manager_approved_at' => now(), 'approved_amount' => '100.0000',
        'ceo_approved_by' => $finance->id, 'ceo_approved_at' => now(), 'journal_entry_id' => 78,
    ]);

    expect(Gate::forUser($finance)->allows('reimburse', $claim))->toBeTrue();
    $claim->status = ExpenseClaimStatus::Submitted;
    expect(Gate::forUser($finance)->allows('reimburse', $claim))->toBeFalse();
});

it('allows a super admin to review any department claim and then perform CEO approval', function (bool $assignedManager) {
    $manager = expenseApprovalUser(false, ['expense.manager_approve']);
    $admin = expenseApprovalUser(true);
    $claim = expenseApprovalClaim($manager);
    if (! $assignedManager) {
        DB::table('departments')->where('id', $claim->department_id)->update(['manager_id' => null]);
    }

    $this->actingAs($admin)->patch(route('expense.manager-approve', $claim), [
        'line_reviews' => [['id' => $claim->lines->first()->id, 'decision' => 'approved']],
    ])->assertRedirect();

    $claim->refresh();
    expect($claim->status)->toBe(ExpenseClaimStatus::ManagerApproved);
    expect($claim->manager_approved_by)->toBe($admin->id);
    expect($claim->lines->first()->manager_reviewed_by)->toBe($admin->id);
    expect(Gate::forUser($admin)->allows('ceoApprove', $claim))->toBeTrue();
    expect(Gate::forUser($admin)->allows('managerApprove', $claim))->toBeTrue();
    $this->assertDatabaseCount('journal_entries', 0);
})->with([true, false]);

it('repairs the renamed reimbursement bank column without losing saved bank selections', function () {
    $manager = expenseApprovalUser(true);
    $claim = expenseApprovalClaim($manager, ['reimbursement_bank_account_id' => 2]);
    Schema::table('expense_claims', function (Blueprint $table): void {
        $table->renameColumn('reimbursement_bank_account_id', 'bank_account_id');
    });
    $migration = require base_path('Modules/Expense/database/migrations/2026_09_26_174255_restore_reimbursement_bank_account_column_on_expense_claims.php');

    $migration->up();

    expect($claim->fresh()->reimbursement_bank_account_id)->toBe(2);
    $claim->update(['reimbursement_bank_account_id' => 3, 'status' => ExpenseClaimStatus::Reimbursed]);
    $this->assertDatabaseHas('expense_claims', ['id' => $claim->id, 'reimbursement_bank_account_id' => 3, 'status' => 'reimbursed']);
});

it('leaves a correctly named reimbursement bank column intact on migration and rollback', function () {
    $manager = expenseApprovalUser(true);
    $claim = expenseApprovalClaim($manager, ['reimbursement_bank_account_id' => 2]);
    $migration = require base_path('Modules/Expense/database/migrations/2026_09_26_174255_restore_reimbursement_bank_account_column_on_expense_claims.php');

    $migration->up();
    $migration->down();

    expect($claim->fresh()->reimbursement_bank_account_id)->toBe(2);
    $claim->update(['reimbursement_bank_account_id' => 3]);
    $this->assertDatabaseHas('expense_claims', ['id' => $claim->id, 'reimbursement_bank_account_id' => 3]);
});

it('posts an employee reimbursement to the selected party-required bank and preserves the employee on reversal', function () {
    foreach ([
        '2026_09_27_104031_add_employee_id_to_journal_lines_table.php',
        '2026_09_14_174753_create_bank_accounts_table.php',
        '2026_09_11_074816_create_organizations_table.php',
        '2026_09_11_075250_2026_01_01_000005_create_financial_years_table.php',
    ] as $migration) {
        (require database_path('migrations/'.$migration))->up();
    }
    Schema::table('users', fn ($table) => $table->softDeletes());
    DB::table('organizations')->insert(['name' => 'Test organization', 'currency_code' => 'PKR']);
    $admin = expenseApprovalUser(true);
    $claim = expenseApprovalClaim($admin, ['status' => ExpenseClaimStatus::CeoApproved]);
    $bankAccount = Account::query()->create([
        'code' => '1122', 'name' => 'Meezan', 'type' => 'asset', 'normal_balance' => 'debit',
        'is_postable' => true, 'requires_party' => true,
    ]);
    $payable = Account::query()->create([
        'code' => '2100', 'name' => 'Employee payable', 'type' => 'liability', 'normal_balance' => 'credit',
        'is_postable' => true, 'requires_party' => true,
    ]);
    $bankId = DB::table('bank_accounts')->insertGetId([
        'bank_id' => 1, 'gl_account_id' => $bankAccount->id, 'code' => 'MEEZAN',
        'name' => 'Meezan', 'account_number' => '123', 'currency_code' => 'PKR',
    ]);
    $this->mock(SystemAccountManager::class, function ($mock) use ($payable) {
        $mock->shouldReceive('resolve')->andReturn($payable);
        $mock->shouldReceive('missingRequiredRoles')->andReturn([]);
    });
    $this->mock(AccountingPeriodManager::class, function ($mock) {
        $mock->shouldReceive('assertPostable')->andReturn(new AccountingPeriod);
    });
    $this->mock(SequenceGenerator::class, function ($mock) {
        $mock->shouldReceive('next')->andReturn('JE-EMPLOYEE', 'JE-EMPLOYEE-REVERSAL', 'JE-MISSING-PARTY');
    });
    $this->actingAs($admin)->patch(route('expense.reimburse', $claim), [
        'bank_account_id' => $bankId,
        'expense_account_ids' => [$claim->lines->first()->id => $claim->expense_account_id],
    ])->assertRedirect()->assertSessionHasNoErrors();
    $this->assertDatabaseHas('journal_lines', [
        'account_id' => $bankAccount->id, 'employee_id' => $claim->employee_id, 'credit' => 100,
    ]);
    expect($claim->fresh()->status)->toBe(ExpenseClaimStatus::Reimbursed);
    $journal = JournalEntry::query()->firstOrFail();
    $reversal = app(JournalPoster::class)->reverse($journal, $admin->id, 'Test reversal');
    $this->assertDatabaseHas('journal_lines', [
        'journal_entry_id' => $reversal->id, 'account_id' => $bankAccount->id,
        'employee_id' => $claim->employee_id, 'debit' => 100,
    ]);
    expect(fn () => app(JournalPoster::class)->post(new JournalEntryData(
        now(), 'Missing party', [
            new JournalLineData($bankAccount->id, '100', '0'),
            new JournalLineData($payable->id, '0', '100'),
        ],
    )))->toThrow(BusinessRuleException::class, 'requires a customer, vendor, or employee');
});
