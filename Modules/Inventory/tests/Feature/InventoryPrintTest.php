<?php

use App\Enums\UserStatus;
use App\Models\Item;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Modules\Inventory\Models\StockAdjustment;
use Modules\Inventory\Models\StockBalance;
use Modules\Inventory\Models\StockCount;
use Modules\Inventory\Models\StockMovement;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

pest()->extend(TestCase::class);

beforeEach(function () {
    config(['activitylog.enabled' => false]);
    Schema::disableForeignKeyConstraints();

    foreach ([
        'database/migrations/0001_01_01_000000_create_users_table.php',
        'database/migrations/2026_09_11_075003_2026_01_01_000002_add_erp_fields_to_users_table.php',
        'database/migrations/2026_09_11_062704_create_permission_tables.php',
        'database/migrations/2026_01_04_000001_create_categories_table.php',
        'database/migrations/2026_01_04_000002_create_units_table.php',
        'database/migrations/2026_01_04_000003_create_items_table.php',
        'database/migrations/2026_01_05_000001_create_warehouses_table.php',
        'Modules/Inventory/database/migrations/2026_09_16_124133_create_stock_balances_table.php',
        'Modules/Inventory/database/migrations/2026_09_16_124134_create_stock_movements_table.php',
        'Modules/Inventory/database/migrations/2026_04_02_000003_create_stock_adjustments_table.php',
        'Modules/Inventory/database/migrations/2026_04_02_000004_create_stock_adjustment_lines_table.php',
        'Modules/Inventory/database/migrations/2026_04_02_000005_create_stock_counts_table.php',
    ] as $migration) {
        (require base_path($migration))->up();
    }
});

function inventoryPrintUser(): User
{
    $user = User::factory()->create(['status' => UserStatus::Active]);
    $user->givePermissionTo(Permission::findOrCreate('inventory.view', 'web'));

    return $user;
}

it('prints all stock rows beyond pagination and keeps the search filter', function () {
    $warehouse = Warehouse::create(['code' => 'MAIN', 'name' => 'Main']);
    for ($index = 1; $index <= 51; $index++) {
        $item = Item::create(['code' => 'I'.$index, 'name' => 'Included item '.$index, 'reorder_level' => 10]);
        StockBalance::create(['item_id' => $item->id, 'warehouse_id' => $warehouse->id, 'quantity' => 5, 'total_value' => 10]);
    }
    $excluded = Item::create(['code' => 'EX', 'name' => 'Excluded item']);
    StockBalance::create(['item_id' => $excluded->id, 'warehouse_id' => $warehouse->id, 'quantity' => 20]);
    $this->actingAs(inventoryPrintUser());

    $response = $this->get(route('inventory.stock.index', ['print' => 1, 'page' => 2, 'search' => 'Included', 'warehouse_id' => $warehouse->id]));

    $response->assertSee('Included item 1')->assertSee('Included item 51')->assertDontSee('Excluded item')
        ->assertSee('510.00')->assertSee('Print / Save as PDF')->assertDontSee('id="kt_app_sidebar"', false);
    expect($response->viewData('balances'))->toHaveCount(51);
});

it('prints every low stock row and excludes stock above its reorder level', function () {
    $warehouse = Warehouse::create(['code' => 'MAIN', 'name' => 'Main']);
    for ($index = 1; $index <= 51; $index++) {
        $item = Item::create(['code' => 'I'.$index, 'name' => 'Low item '.$index, 'reorder_level' => 10]);
        StockBalance::create(['item_id' => $item->id, 'warehouse_id' => $warehouse->id, 'quantity' => 5]);
    }
    $item = Item::create(['code' => 'HIGH', 'name' => 'Sufficient stock', 'reorder_level' => 10]);
    StockBalance::create(['item_id' => $item->id, 'warehouse_id' => $warehouse->id, 'quantity' => 20]);

    $response = $this->actingAs(inventoryPrintUser())->get(route('inventory.low-stock', ['print' => 1, 'page' => 2]));

    $response->assertSee('Low item 1')->assertSee('Low item 51')->assertDontSee('Sufficient stock');
    expect($response->viewData('rows'))->toHaveCount(51);
});

it('combines batches per warehouse before checking the reorder level', function (bool $print) {
    $warehouse = Warehouse::create(['code' => 'MAIN', 'name' => 'Main']);
    $otherWarehouse = Warehouse::create(['code' => 'OTHER', 'name' => 'Other']);
    $item = Item::create(['code' => 'VIT', 'name' => 'Vitamin A', 'reorder_level' => 10]);
    $sufficient = Item::create(['code' => 'ENOUGH', 'name' => 'Sufficient combined stock', 'reorder_level' => 10]);
    foreach ([4, 6] as $index => $quantity) {
        StockBalance::create(['item_id' => $item->id, 'warehouse_id' => $warehouse->id, 'batch_id' => $index + 1, 'quantity' => $quantity]);
        StockBalance::create(['item_id' => $sufficient->id, 'warehouse_id' => $warehouse->id, 'batch_id' => $index + 1, 'quantity' => 6]);
    }
    StockBalance::create(['item_id' => $item->id, 'warehouse_id' => $otherWarehouse->id, 'quantity' => 2]);

    $response = $this->actingAs(inventoryPrintUser())->get(route('inventory.low-stock', ['print' => $print]));

    $response->assertSee('Vitamin A')->assertDontSee('Sufficient combined stock');
    $rows = $response->viewData('rows');
    expect($rows)->toHaveCount(2);
    expect($rows->first()->quantity)->toBe('10.0000');
    expect($rows->first()->warehouse_id)->toBe($warehouse->id);
    expect($rows->last()->quantity)->toBe('2.0000');
    expect($rows->last()->warehouse_id)->toBe($otherWarehouse->id);
    if (! $print) {
        expect($rows->total())->toBe(2);
    }
})->with([false, true]);

it('prints all movements matching the ledger filters', function () {
    $warehouse = Warehouse::create(['code' => 'MAIN', 'name' => 'Main']);
    $item = Item::create(['code' => 'I1', 'name' => 'Stock item']);
    for ($index = 1; $index <= 51; $index++) {
        StockMovement::create(['item_id' => $item->id, 'warehouse_id' => $warehouse->id, 'type' => 'purchase', 'quantity' => 1, 'reference' => 'MATCH-'.$index, 'occurred_at' => '2026-09-20 12:00:00']);
    }
    StockMovement::create(['item_id' => $item->id, 'warehouse_id' => $warehouse->id, 'type' => 'sale', 'quantity' => -1, 'reference' => 'EXCLUDED', 'occurred_at' => '2026-09-20 12:00:00']);

    $response = $this->actingAs(inventoryPrintUser())->get(route('inventory.movements.index', [
        'print' => 1, 'page' => 2, 'warehouse_id' => $warehouse->id, 'item_id' => $item->id,
        'type' => 'purchase', 'reference' => 'MATCH', 'from' => '2026-09-19', 'to' => '2026-09-21',
    ]));

    $response->assertSee('MATCH-1')->assertSee('MATCH-51')->assertDontSee('EXCLUDED');
    expect($response->viewData('movements'))->toHaveCount(51);
});

it('prints every adjustment matching the list filters', function () {
    $warehouse = Warehouse::create(['code' => 'MAIN', 'name' => 'Main']);
    for ($index = 1; $index <= 26; $index++) {
        StockAdjustment::create(['number' => 'MATCH-'.$index, 'warehouse_id' => $warehouse->id, 'adjustment_date' => '2026-09-20', 'reason' => 'Count correction', 'status' => 'draft']);
    }
    StockAdjustment::create(['number' => 'EXCLUDED', 'warehouse_id' => $warehouse->id, 'adjustment_date' => '2026-09-20', 'reason' => 'Excluded correction', 'status' => 'posted']);

    $response = $this->actingAs(inventoryPrintUser())->get(route('inventory.adjustments.index', [
        'print' => 1, 'page' => 2, 'search' => 'MATCH', 'status' => 'draft', 'warehouse_id' => $warehouse->id,
    ]));

    $response->assertSee('MATCH-1')->assertSee('MATCH-26')->assertDontSee('EXCLUDED')->assertDontSee('>Actions</th>', false);
    expect($response->viewData('adjustments'))->toHaveCount(26);
});

it('prints saved stock count quantities without editable controls', function (string $status) {
    $warehouse = Warehouse::create(['code' => 'MAIN', 'name' => 'Main']);
    $item = Item::create(['code' => 'I1', 'name' => 'Counted item']);
    $count = StockCount::create(['number' => 'CNT-PRINT', 'warehouse_id' => $warehouse->id, 'count_date' => '2026-09-20', 'status' => $status]);
    $count->lines()->create(['item_id' => $item->id, 'system_quantity' => 10.25, 'counted_quantity' => 8.5, 'variance' => -1.75, 'unit_cost' => 12]);

    $response = $this->actingAs(inventoryPrintUser())->get(route('inventory.counts.show', ['count' => $count, 'print' => 1]));

    $response->assertSee('CNT-PRINT')->assertSee('Counted item')->assertSee('10.25')->assertSee('8.50')->assertSee('-1.75')
        ->assertDontSee('Save Counted Quantities')->assertDontSee('name="counted[', false);
})->with(['draft', 'counting', 'review', 'approved', 'posted', 'cancelled']);

it('keeps print reports behind existing permissions', function (string $route) {
    $user = User::factory()->create(['status' => UserStatus::Active]);

    $this->actingAs($user)->get(route($route, ['print' => 1]))->assertForbidden();
})->with(['inventory.stock.index', 'inventory.low-stock', 'inventory.movements.index', 'inventory.adjustments.index']);

it('retains pagination and adds a filter preserving print link on normal lists', function (string $route, string $dataKey) {
    $response = $this->actingAs(inventoryPrintUser())->get(route($route, ['search' => 'needle', 'page' => 2]));

    $response->assertSee('Print / PDF')->assertSee(route($route, ['search' => 'needle', 'print' => 1]));
    expect($response->viewData($dataKey))->toBeInstanceOf(LengthAwarePaginator::class);
})->with([
    ['inventory.stock.index', 'balances'], ['inventory.low-stock', 'rows'],
    ['inventory.movements.index', 'movements'], ['inventory.adjustments.index', 'adjustments'],
]);

it('denies stock count printing without inventory permission', function () {
    $warehouse = Warehouse::create(['code' => 'MAIN', 'name' => 'Main']);
    $count = StockCount::create(['number' => 'CNT-PRIVATE', 'warehouse_id' => $warehouse->id, 'count_date' => '2026-09-20', 'status' => 'draft']);
    $user = User::factory()->create(['status' => UserStatus::Active]);

    $this->actingAs($user)->get(route('inventory.counts.show', ['count' => $count, 'print' => 1]))->assertForbidden();
});

it('redirects guests from print reports to login', function (string $path) {
    $this->get($path.'?print=1')->assertRedirect(route('login'));
})->with(['/inventory/stock', '/inventory/low-stock', '/inventory/movements', '/inventory/adjustments', '/inventory/counts/2']);

it('escapes filter text in the print header', function () {
    $search = '<script>alert(1)</script>';

    $this->actingAs(inventoryPrintUser())->get(route('inventory.stock.index', ['print' => 1, 'search' => $search]))
        ->assertSee($search)->assertDontSee($search, false);
});

it('offers print on the normal stock count page', function () {
    $warehouse = Warehouse::create(['code' => 'MAIN', 'name' => 'Main']);
    $count = StockCount::create(['number' => 'CNT-LINK', 'warehouse_id' => $warehouse->id, 'count_date' => '2026-09-20', 'status' => 'draft']);

    $this->actingAs(inventoryPrintUser())->get(route('inventory.counts.show', $count))
        ->assertSee('Print / PDF')->assertSee(route('inventory.counts.show', ['count' => $count, 'print' => 1]));
});
