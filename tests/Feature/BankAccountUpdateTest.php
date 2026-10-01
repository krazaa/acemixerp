<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\Account;
use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BankAccountUpdateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['activitylog.enabled' => false]);
        foreach ([
            '0001_01_01_000000_create_users_table.php',
            '2026_09_11_075003_2026_01_01_000002_add_erp_fields_to_users_table.php',
            '2026_09_11_062704_create_permission_tables.php',
            '2026_01_08_000001_create_accounts_table.php',
            '2026_09_14_052947_create_banks_table.php',
            '2026_09_14_174753_create_bank_accounts_table.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
    }

    public function test_authorized_user_can_update_account_without_changing_unique_fields(): void
    {
        $account = $this->createAccount('MAIN');
        $user = User::factory()->create(['status' => UserStatus::Active]);
        $user->givePermissionTo(Permission::findOrCreate('banking.reconcile', 'web'));

        $this->actingAs($user)->put(route('bank-accounts.update', $account), [
            'code' => $account->code,
            'account_number' => $account->account_number,
            'name' => 'Updated account',
        ])->assertSessionHasNoErrors()->assertRedirect(route('bank-accounts.show', $account));

        $this->assertDatabaseHas('bank_accounts', ['id' => $account->id, 'name' => 'Updated account']);
    }

    public function test_duplicate_code_and_account_number_are_rejected_even_with_another_id_in_input(): void
    {
        $account = $this->createAccount('MAIN');
        $other = $this->createAccount('OTHER', $account->bank_id);
        $user = User::factory()->create(['status' => UserStatus::Active]);
        $user->givePermissionTo(Permission::findOrCreate('banking.reconcile', 'web'));

        $this->actingAs($user)->put(route('bank-accounts.update', $account), [
            'id' => $other->id,
            'code' => $other->code,
            'account_number' => $other->account_number,
        ])->assertSessionHasErrors(['code', 'account_number']);

        $this->assertDatabaseHas('bank_accounts', ['id' => $account->id, 'code' => 'MAIN', 'account_number' => 'MAIN-123']);
    }

    public function test_user_without_permission_cannot_update_account(): void
    {
        $account = $this->createAccount('MAIN');
        $user = User::factory()->create(['status' => UserStatus::Active]);

        $this->actingAs($user)->put(route('bank-accounts.update', $account), ['name' => 'Unauthorized change'])
            ->assertForbidden();

        $this->assertDatabaseHas('bank_accounts', ['id' => $account->id, 'name' => 'MAIN']);
    }

    public function test_missing_bank_account_returns_not_found(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Active]);
        $user->givePermissionTo(Permission::findOrCreate('banking.reconcile', 'web'));

        $this->actingAs($user)->put(route('bank-accounts.update', 999), ['name' => 'Missing account'])
            ->assertNotFound();
    }

    private function createAccount(string $code, ?int $bankId = null): BankAccount
    {
        $bankId ??= Bank::create(['code' => $code, 'name' => $code])->id;
        $ledgerAccount = Account::create(['code' => $code, 'name' => $code, 'type' => 'asset', 'normal_balance' => 'debit']);

        return BankAccount::create([
            'bank_id' => $bankId,
            'gl_account_id' => $ledgerAccount->id,
            'code' => $code,
            'name' => $code,
            'account_number' => $code.'-123',
            'currency_code' => 'PKR',
        ]);
    }
}
