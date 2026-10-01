<?php

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ViewErrorBag;
use Modules\Sales\Http\Controllers\DeliveryController;
use Modules\Sales\Models\Delivery;
use Tests\TestCase;

pest()->extend(TestCase::class);

beforeEach(function () {
    config(['activitylog.enabled' => false]);
    Schema::disableForeignKeyConstraints();
    (require database_path('migrations/2026_09_11_062704_create_permission_tables.php'))->up();
    (require database_path('migrations/2026_01_03_000003_create_vendors_table.php'))->up();
    Schema::table('vendors', function (Blueprint $table) {
        $table->string('vendor_type')->nullable();
    });
    (require base_path('Modules/Sales/database/migrations/2026_09_16_050827_create_sales_orders_table.php'))->up();
    (require base_path('Modules/Sales/database/migrations/2026_09_16_111850_create_delivery_lines_table.php'))->up();
});

it('shows only active transport vendors on the delivery creation form', function () {
    foreach ([
        ['TR-1', 'Zulu Transport', 'transport', 'active', null],
        ['TR-2', 'Alpha Transport', 'transport', 'active', null],
        ['SUP-1', 'Supplier', 'supplier', 'active', null],
        ['TR-3', 'Inactive Transport', 'transport', 'inactive', null],
        ['TR-4', 'Deleted Transport', 'transport', 'active', now()],
    ] as [$code, $name, $type, $status, $deletedAt]) {
        Vendor::forceCreate(['code' => $code, 'name' => $name, 'vendor_type' => $type, 'status' => $status, 'deleted_at' => $deletedAt]);
    }
    $this->actingAs(User::factory()->make());
    Gate::before(fn () => true);

    $view = app(DeliveryController::class)->create(Request::create('/sales/deliveries/create'));
    expect($view->getData()['transportVendors']->pluck('code')->all())->toBe(['TR-2', 'TR-1']);
    $this->view('sales::deliveries._form', $view->getData() + ['errors' => new ViewErrorBag])
        ->assertSee('Alpha Transport')->assertSee('Zulu Transport')->assertDontSee('Inactive Transport');
});

it('supplies transport vendors when editing a delivery and retains the selected vendor', function () {
    $vendor = Vendor::forceCreate(['code' => 'TR-1', 'name' => 'Selected Transport', 'vendor_type' => 'transport', 'status' => 'active']);
    $this->actingAs(User::factory()->make());
    Gate::before(fn () => true);
    $delivery = new Delivery(['vendor_id' => $vendor->id, 'status' => 'draft']);
    $delivery->setRelation('lines', collect());
    $delivery->setRelation('salesOrder', null);
    $view = app(DeliveryController::class)->edit($delivery);
    expect($view->getData()['transportVendors']->pluck('id')->all())->toBe([$vendor->id]);
    $this->view('sales::deliveries._form', $view->getData() + ['salesOrder' => null, 'openOrders' => collect(), 'errors' => new ViewErrorBag])
        ->assertSee('Selected Transport')->assertSee('selected', false);
});
