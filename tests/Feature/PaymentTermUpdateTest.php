<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\PaymentTerm;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PaymentTermUpdateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['activitylog.enabled' => false]);
        foreach ([
            '0001_01_01_000000_create_users_table.php',
            '2026_09_11_075003_2026_01_01_000002_add_erp_fields_to_users_table.php',
            '2026_09_11_062704_create_permission_tables.php',
            '2026_09_14_053002_create_payment_terms_table.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
    }

    public function test_editing_a_payment_term_can_keep_its_existing_code(): void
    {
        $term = PaymentTerm::create(['code' => 'NET30', 'name' => 'Net 30', 'type' => 'net', 'days' => 30]);
        $user = User::factory()->create(['status' => UserStatus::Active]);
        $user->givePermissionTo(Permission::findOrCreate('coa.manage', 'web'));

        $this->actingAs($user)->put(route('payment-terms.update', $term), [
            'code' => ' net30 ', 'name' => 'Updated payment term', 'type' => 'net', 'days' => 45, 'status' => 'active',
        ])->assertSessionHasNoErrors()->assertRedirect(route('payment-terms.index'));

        $this->assertDatabaseHas('payment_terms', ['id' => $term->id, 'code' => 'NET30', 'name' => 'Updated payment term', 'days' => 45]);
    }

    public function test_another_terms_code_is_rejected_even_when_its_id_is_submitted(): void
    {
        $term = PaymentTerm::create(['code' => 'NET30', 'name' => 'Net 30', 'type' => 'net', 'days' => 30]);
        $other = PaymentTerm::create(['code' => 'NET60', 'name' => 'Net 60', 'type' => 'net', 'days' => 60]);
        $user = User::factory()->create(['status' => UserStatus::Active]);
        $user->givePermissionTo(Permission::findOrCreate('coa.manage', 'web'));

        $this->actingAs($user)->put(route('payment-terms.update', $term), [
            'id' => $other->id, 'code' => $other->code, 'name' => 'Changed', 'type' => 'net', 'days' => 30, 'status' => 'active',
        ])->assertSessionHasErrors(['code' => 'The code has already been taken.']);

        $this->assertDatabaseHas('payment_terms', ['id' => $term->id, 'code' => 'NET30', 'name' => 'Net 30']);
    }
}
