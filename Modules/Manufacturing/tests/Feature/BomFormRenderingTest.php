<?php

use App\Models\User;
use Illuminate\Support\ViewErrorBag;
use Modules\Manufacturing\Models\BillOfMaterials;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

pest()->extend(TestCase::class);

it('renders the ingredient controls and their script inside the BOM page', function (string $page) {
    (require database_path('migrations/2026_09_11_062704_create_permission_tables.php'))->up();
    $user = User::factory()->make();
    $user->setRelation('roles', collect([new Role(['name' => 'super-admin', 'guard_name' => 'web'])]));
    $this->actingAs($user);
    $bom = new BillOfMaterials(['name' => 'Test BOM']);
    $bom->id = 1;
    $bom->setRelation('lines', collect());

    $this->view('manufacturing::boms.'.$page, [
        'bom' => $bom,
        'products' => collect(),
        'components' => collect(),
        'units' => collect(),
        'errors' => new ViewErrorBag,
    ])->assertSee('Add Ingredient')
        ->assertSee('window.bomAddLine = function', false)
        ->assertSee('id="bom-line-template"', false)
        ->assertSeeInOrder(['window.bomAddLine = function', '</body>'], false);
})->with(['create', 'edit']);
