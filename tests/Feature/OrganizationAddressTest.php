<?php

use App\Enums\UserStatus;
use App\Models\Address;
use App\Models\Organization;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

function organizationSettingsPayload(): array
{
    return [
        'name' => 'Test Organization',
        'country' => 'PK',
        'currency_code' => 'PKR',
        'currency_symbol' => 'Rs',
        'currency_decimals' => 2,
        'timezone' => 'Asia/Karachi',
        'date_format' => 'Y-m-d',
        'fiscal_year_start_month' => '07',
        'inventory_valuation_method' => 'FIFO',
        'addresses_present' => '1',
    ];
}

function organizationSettingsUser(): User
{
    $user = User::factory()->create(['status' => UserStatus::Active]);
    foreach (['settings.view', 'settings.update'] as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    return $user;
}

test('organization saves multiple addresses and displays them after reloading', function () {
    $organization = Organization::create(['name' => 'Original']);
    $user = organizationSettingsUser();
    $addresses = [
        ['type' => 'billing', 'address_line1' => '12 Main Road', 'city' => 'Karachi'],
        ['type' => 'office', 'address_line1' => '34 Office Road', 'city' => 'Lahore', 'is_primary' => '1', 'contact_email' => 'office@example.com'],
    ];

    $this->actingAs($user)->put(route('organization.update'), organizationSettingsPayload() + ['addresses' => $addresses])
        ->assertSessionHasNoErrors()->assertRedirect(route('organization.edit'));

    expect($organization->addresses()->count())->toBe(2);
    $this->assertDatabaseHas('addresses', ['addressable_type' => Organization::class, 'addressable_id' => $organization->id, 'address_line1' => '34 Office Road', 'country' => 'PK', 'is_primary' => true]);
    $this->assertDatabaseHas('organizations', ['id' => $organization->id, 'name' => 'Test Organization']);
    $this->get(route('organization.edit'))->assertOk()->assertSee('12 Main Road')->assertSee('34 Office Road');
});

test('organization updates retained addresses and soft deletes removed addresses', function () {
    $organization = Organization::create(['name' => 'Original']);
    $retained = $organization->addresses()->create(['type' => 'office', 'address_line1' => 'Old Road', 'city' => 'Karachi']);
    $removed = $organization->addresses()->create(['type' => 'billing', 'address_line1' => 'Removed Road', 'city' => 'Karachi']);

    $this->actingAs(organizationSettingsUser())->put(route('organization.update'), organizationSettingsPayload() + [
        'addresses' => [3 => ['id' => $retained->id, 'type' => 'office', 'address_line1' => 'New Road', 'city' => 'Lahore']],
    ])->assertSessionHasNoErrors()->assertRedirect(route('organization.edit'));

    $this->assertDatabaseHas('addresses', ['id' => $retained->id, 'address_line1' => 'New Road', 'deleted_at' => null]);
    $this->assertSoftDeleted($removed);
    expect($organization->addresses()->count())->toBe(1);
});

test('removing every organization address clears the saved list', function () {
    $organization = Organization::create(['name' => 'Original']);
    $address = $organization->addresses()->create(['type' => 'office', 'address_line1' => 'Old Road', 'city' => 'Karachi']);

    $this->actingAs(organizationSettingsUser())->put(route('organization.update'), organizationSettingsPayload())
        ->assertSessionHasNoErrors()->assertRedirect(route('organization.edit'));

    $this->assertSoftDeleted($address);
    expect($organization->addresses()->count())->toBe(0);
    $this->get(route('organization.edit'))->assertSee('No addresses yet.');
});

test('organization can save addresses without legacy address columns', function () {
    $columns = ['address_line1', 'address_line2', 'city', 'state', 'postal_code'];
    foreach ($columns as $column) {
        if (Schema::hasColumn('organizations', $column)) {
            Schema::table('organizations', function (Blueprint $table) use ($column): void {
                $table->dropColumn($column);
            });
        }
    }
    $organization = Organization::create(['name' => 'Original']);

    $this->actingAs(organizationSettingsUser())->put(route('organization.update'), organizationSettingsPayload() + [
        'addresses' => [['type' => 'office', 'address_line1' => 'Office Road', 'city' => 'Islamabad']],
    ])->assertSessionHasNoErrors()->assertRedirect(route('organization.edit'));

    $this->assertDatabaseHas('addresses', ['addressable_type' => Organization::class, 'addressable_id' => $organization->id, 'address_line1' => 'Office Road']);
    $this->get(route('organization.edit'))->assertOk()->assertSee('Office Road');
});

test('invalid addresses leave organization details and saved addresses unchanged', function () {
    $organization = Organization::create(['name' => 'Original']);
    $address = $organization->addresses()->create(['type' => 'office', 'address_line1' => 'Old Road', 'city' => 'Karachi']);

    $this->actingAs(organizationSettingsUser())->put(route('organization.update'), organizationSettingsPayload() + [
        'addresses' => [['type' => 'office', 'address_line1' => '', 'city' => '']],
    ])->assertSessionHasErrors(['addresses.0.address_line1', 'addresses.0.city']);

    expect($organization->refresh()->name)->toBe('Original');
    $this->assertNotSoftDeleted($address);
});

test('organization cannot claim an address belonging to a vendor', function () {
    $organization = Organization::create(['name' => 'Original']);
    $address = new Address(['type' => 'office', 'address_line1' => 'Vendor Road', 'city' => 'Karachi']);
    $address->addressable_type = Vendor::class;
    $address->addressable_id = 99;
    $address->save();

    $this->actingAs(organizationSettingsUser())->put(route('organization.update'), organizationSettingsPayload() + [
        'addresses' => [['id' => $address->id, 'type' => 'office', 'address_line1' => 'Stolen Road', 'city' => 'Karachi']],
    ])->assertSessionHasErrors('addresses.0.id');

    expect($address->refresh()->address_line1)->toBe('Vendor Road');
    expect($organization->refresh()->name)->toBe('Original');
});

test('organization address changes require settings permission', function () {
    $organization = Organization::create(['name' => 'Original']);
    $user = User::factory()->create(['status' => UserStatus::Active]);

    $this->actingAs($user)->put(route('organization.update'), organizationSettingsPayload())->assertForbidden();

    expect($organization->refresh()->name)->toBe('Original');
});

test('guests cannot save organization addresses', function () {
    $this->put(route('organization.update'), organizationSettingsPayload())->assertRedirect(route('login'));
});

test('saving an organization address preserves its regional details', function () {
    $organization = Organization::create(['name' => 'Original']);
    $address = ['address_line1' => 'Office Road', 'city' => 'Karachi', 'state' => 'Sindh', 'postal_code' => '74000', 'country' => 'PK', 'type' => 'office', 'is_primary' => '1'];

    $this->actingAs(organizationSettingsUser())->put(route('organization.update'), organizationSettingsPayload() + ['addresses' => [$address]])
        ->assertSessionHasNoErrors()->assertRedirect(route('organization.edit'));

    $this->assertDatabaseHas('addresses', ['addressable_type' => Organization::class, 'addressable_id' => $organization->id, 'state' => 'Sindh', 'postal_code' => '74000', 'country' => 'PK']);
});

test('failed validation does not restore addresses the user removed', function () {
    $organization = Organization::create(['name' => 'Original']);
    $address = $organization->addresses()->create(['type' => 'office', 'address_line1' => 'Old Road', 'city' => 'Karachi']);

    $this->actingAs(organizationSettingsUser())->from(route('organization.edit'))
        ->put(route('organization.update'), array_replace(organizationSettingsPayload(), ['name' => '']))
        ->assertSessionHasErrors('name');

    $this->get(route('organization.edit'))->assertSee('No addresses yet.')->assertDontSee('Old Road');
    $this->assertNotSoftDeleted($address);
});

test('address editor allocates new indices beyond retained sparse rows', function () {
    Organization::create(['name' => 'Original']);
    $rows = [3 => ['type' => 'office', 'address_line1' => 'Retained Road', 'city' => 'Karachi']];

    $this->actingAs(organizationSettingsUser())->withSession(['_old_input' => ['addresses_present' => '1', 'addresses' => $rows]])
        ->get(route('organization.edit'))->assertSee('addresses[3][address_line1]', false)
        ->assertSee('let vendorAddressIndex = 4;', false);
});

test('shared vendor address controls still render without organization options', function () {
    $vendor = new Vendor;
    $vendor->setRelation('addresses', collect());

    $this->blade("@include('vendors._form-scripts', ['vendor' => \$vendor])", ['vendor' => $vendor])
        ->assertSee('let vendorAddressIndex = 0;', false)
        ->assertSee('addresses[__INDEX__][address_line1]', false)
        ->assertDontSee('addresses[__INDEX__][state]', false);
});
