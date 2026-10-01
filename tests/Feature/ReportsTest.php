<?php

use App\Enums\UserStatus;
use App\Models\User;
use Spatie\Permission\Models\Permission;

it('redirects guests from reports to login', function () {
    $this->get(route('reports.index'))->assertRedirect(route('login'));
});

it('denies reports to users without financial report permission', function () {
    $user = User::factory()->create(['status' => UserStatus::Active]);

    $this->actingAs($user)->get(route('reports.index'))->assertForbidden();
});

it('shows links to financial reports for authorized users', function () {
    Permission::findOrCreate('reports.financial', 'web');
    $user = User::factory()->create(['status' => UserStatus::Active]);
    $user->givePermissionTo('reports.financial');

    $this->actingAs($user)->get(route('reports.index'))
        ->assertSee('Accounts Payable Report')
        ->assertSee('Accounts Receivable Report')
        ->assertSee('Trial Balance Report')
        ->assertSee('href="'.route('reports.accounts-payable').'"', false)
        ->assertSee('href="'.route('reports.accounts-receivable').'"', false)
        ->assertSee('href="'.route('reports.trial-balance').'"', false);
});
