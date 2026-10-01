<?php

use App\Contracts\SequenceGenerator;
use App\Enums\UserStatus;
use App\Models\Item;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Procurement\Actions\ConvertRequisitionToRfq;
use Modules\Procurement\Exceptions\RfqException;
use Modules\Procurement\Models\PurchaseRequisition;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

pest()->extend(TestCase::class);

beforeEach(function () {
    expect(config('database.default'))->toBe('sqlite');
    expect(config('database.connections.sqlite.database'))->toBe(':memory:');
    Schema::disableForeignKeyConstraints();
    config(['activitylog.enabled' => false]);
    $this->travelTo(now()->setDate(2026, 9, 24)->startOfDay());
    foreach ([
        '0001_01_01_000000_create_users_table.php',
        '2026_09_11_075003_2026_01_01_000002_add_erp_fields_to_users_table.php',
        '2026_09_28_163338_add_soft_deletes_to_users_table.php',
        '2026_09_11_062704_create_permission_tables.php',
        '2026_09_11_074816_create_organizations_table.php',
        '2026_09_11_075115_2026_01_01_000003_create_document_sequences_table.php',
        '2026_01_03_000003_create_vendors_table.php',
        '2026_01_04_000002_create_units_table.php',
        '2026_01_04_000003_create_items_table.php',
        '2026_01_05_000001_create_warehouses_table.php',
    ] as $migration) {
        (require database_path('migrations/'.$migration))->up();
    }
    foreach ([
        '2026_09_14_081105_create_purchase_requisitions_table.php',
        '2026_10_01_104239_add_warehouse_id_to_purchase_requisitions_table.php',
        '2026_09_15_081050_create_purchase_requisition_lines_table.php',
        '2026_01_12_000001_create_request_for_quotations_table.php',
        '2026_01_12_000002_create_rfq_lines_table.php',
        '2026_01_12_000003_create_rfq_vendors_table.php',
    ] as $migration) {
        (require base_path('Modules/Procurement/database/migrations/'.$migration))->up();
    }
    foreach ([
        '2026_01_13_000001_create_purchase_orders_table.php' => 'purchase_orders',
        '2026_01_13_000002_create_purchase_order_lines_table.php' => 'purchase_order_lines',
        '2026_01_13_000003_create_goods_receipts_table.php' => 'goods_receipts',
        '2026_01_13_000004_create_goods_receipt_lines_table.php' => 'goods_receipt_lines',
        '2026_09_15_155656_create_supplier_invoices_table.php' => 'supplier_invoices',
        '2026_09_15_155706_create_supplier_invoice_lines_table.php' => 'supplier_invoice_lines',
    ] as $migration => $table) {
        if (! Schema::hasTable($table)) {
            (require base_path('Modules/Procurement/database/migrations/'.$migration))->up();
        }
    }
    (require base_path('Modules/Inventory/database/migrations/2026_09_25_151229_create_brands_and_add_brands_to_procurement_lines.php'))->up();
    (require base_path('Modules/Procurement/database/migrations/2026_09_26_053858_add_origin_to_purchase_order_lines.php'))->up();
    Schema::table('rfq_lines', function (Blueprint $table): void {
        $table->unsignedBigInteger('origin_id')->nullable();
    });
    Schema::table('purchase_requisition_lines', function (Blueprint $table): void {
        $table->unsignedBigInteger('origin_id')->nullable();
    });

});

it('saves and updates the requisition warehouse and rejects inactive warehouses', function () {
    $user = conversionUser(['purchase.view', 'purchase_request.create']);
    $warehouse = Warehouse::create(['code' => 'WH-1', 'name' => 'Receiving', 'status' => 'active']);
    $item = Item::create(['code' => 'PR-WH', 'name' => 'Warehouse item']);
    app(SequenceGenerator::class)->register('purchase_requisition', 'PR');
    $payload = ['requested_date' => '2026-09-24', 'purpose' => 'Warehouse restock', 'warehouse_id' => $warehouse->id,
        'lines' => [['item_id' => $item->id, 'quantity' => 2, 'estimated_unit_price' => 10]]];
    $this->actingAs($user)->post(route('procurement.purchase-requisitions.store'), $payload)->assertRedirect()->assertSessionHasNoErrors();
    $requisition = PurchaseRequisition::firstOrFail();
    expect($requisition->warehouse_id)->toBe($warehouse->id);
    $payload['warehouse_id'] = null;
    $this->put(route('procurement.purchase-requisitions.update', $requisition), $payload)->assertRedirect()->assertSessionHasNoErrors();
    expect($requisition->fresh()->warehouse_id)->toBeNull();
    $warehouse->update(['status' => 'inactive']);
    $payload['warehouse_id'] = $warehouse->id;
    $this->post(route('procurement.purchase-requisitions.store'), $payload)->assertSessionHasErrors('warehouse_id');
});

function conversionUser(array $permissions = ['purchase.view', 'rfq.create']): User
{
    $user = User::factory()->create(['status' => UserStatus::Active]);
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
        $user->givePermissionTo($permission);
    }

    return $user;
}

function conversionRequisition(string $status = 'approved', bool $withLines = true): PurchaseRequisition
{
    $id = DB::table('purchase_requisitions')->insertGetId([
        'number' => 'PR-TEST', 'requested_date' => '2026-09-24', 'purpose' => 'Restock materials',
        'status' => $status, 'department_id' => 7, 'cost_center_id' => 9,
    ]);
    if ($withLines) {
        $unit = DB::table('units')->insertGetId(['code' => 'EA', 'name' => 'Each']);
        foreach (['2.1250', '3.0000'] as $position => $quantity) {
            $item = DB::table('items')->insertGetId(['code' => 'I'.$position, 'name' => 'Item '.$position]);
            DB::table('purchase_requisition_lines')->insert([
                'purchase_requisition_id' => $id, 'position' => $position,
                'item_id' => $item, 'unit_id' => $unit, 'quantity' => $quantity, 'specification' => 'Required grade '.$position,
            ]);
        }
    }

    return PurchaseRequisition::findOrFail($id);
}

function conversionVendor(string $status = 'active'): int
{
    return DB::table('vendors')->insertGetId(['code' => 'V1', 'name' => 'Test Vendor', 'status' => $status]);
}

it('offers the conversion form with requisition lines and active vendors', function () {
    $pr = conversionRequisition();
    Schema::table('vendors', function (Blueprint $table): void {
        $table->string('vendor_type')->nullable();
    });
    $vendorId = conversionVendor();
    DB::table('vendors')->where('id', $vendorId)->update(['vendor_type' => 'supplier']);

    $this->actingAs(conversionUser())->get(route('procurement.purchase-requisitions.convert-to-rfq', $pr))
        ->assertSee('Source requisition')->assertSee('PR-TEST')->assertSee('Item 0')->assertSee('2.125')->assertSee('Test Vendor');
});

it('converts once and preserves the source lines, dimensions, vendors and audit fields', function () {
    $pr = conversionRequisition();
    $vendor = conversionVendor();
    DB::table('organizations')->insert(['name' => 'Test Organization', 'currency_code' => 'PKR']);
    app(SequenceGenerator::class)->register('rfq', 'RFQ');
    $user = conversionUser();
    $payload = ['vendor_ids' => [$vendor], 'due_date' => '2026-10-01', 'terms' => 'Delivery included'];

    $response = $this->actingAs($user)->post(route('procurement.purchase-requisitions.store-rfq', $pr), $payload);
    $response->assertSessionHasNoErrors();

    $rfq = DB::table('request_for_quotations')->first();
    expect($rfq)->not->toBeNull();
    $response->assertRedirect(route('procurement.rfqs.show', $rfq->id));
    $this->assertDatabaseHas('request_for_quotations', ['id' => $rfq->id, 'terms' => 'Delivery included']);
    $this->assertDatabaseHas('request_for_quotations', ['id' => $rfq->id, 'purpose' => 'Restock materials', 'department_id' => 7, 'cost_center_id' => 9, 'currency_code' => 'PKR', 'status' => 'draft', 'created_by' => $user->id]);
    $this->assertDatabaseHas('purchase_requisitions', ['id' => $pr->id, 'status' => 'converted', 'converted_to_id' => $rfq->id, 'updated_by' => $user->id]);
    expect($pr->fresh()->converted_at)->not->toBeNull();
    $this->assertDatabaseHas('purchase_requisition_rfq', ['purchase_requisition_id' => $pr->id, 'request_for_quotation_id' => $rfq->id]);
    $this->assertDatabaseHas('rfq_vendors', ['request_for_quotation_id' => $rfq->id, 'vendor_id' => $vendor]);
    $this->assertDatabaseCount('rfq_lines', 2);
    foreach ($pr->lines as $line) {
        $this->assertDatabaseHas('rfq_lines', ['purchase_requisition_line_id' => $line->id, 'item_id' => $line->item_id, 'unit_id' => $line->unit_id, 'quantity' => $line->quantity, 'specification' => $line->specification]);
    }
    expect(fn () => app(ConvertRequisitionToRfq::class)->execute($pr, $payload, $user->id))->toThrow(RfqException::class);
    $this->assertDatabaseCount('request_for_quotations', 1);
});

it('denies conversion without RFQ creation permission', function () {
    $pr = conversionRequisition();

    $this->actingAs(conversionUser(['purchase.view']))->post(route('procurement.purchase-requisitions.store-rfq', $pr), [])->assertForbidden();
    $this->assertDatabaseCount('request_for_quotations', 0);
});

it('requires authentication', function () {
    $pr = conversionRequisition();

    $this->post(route('procurement.purchase-requisitions.store-rfq', $pr), [])->assertRedirect(route('login'));
});

it('rejects requisitions that are not approved', function (string $status) {
    $pr = conversionRequisition($status);

    $this->actingAs(conversionUser())->post(route('procurement.purchase-requisitions.store-rfq', $pr), [])->assertForbidden();
    $this->assertDatabaseCount('request_for_quotations', 0);
})->with(['draft', 'submitted', 'converted', 'cancelled', 'closed']);

it('validates the deadline and vendors without creating an RFQ', function () {
    $pr = conversionRequisition();
    $vendor = conversionVendor('inactive');

    $this->actingAs(conversionUser())->postJson(route('procurement.purchase-requisitions.store-rfq', $pr), ['vendor_ids' => [$vendor], 'due_date' => '2026-09-23'])
        ->assertUnprocessable()->assertJsonValidationErrors(['vendor_ids.0', 'due_date']);
    $this->assertDatabaseCount('request_for_quotations', 0);
});

it('keeps the requisition approved when creation fails', function () {
    $pr = conversionRequisition(withLines: false);
    $vendor = conversionVendor();
    DB::table('organizations')->insert(['name' => 'Test Organization', 'currency_code' => 'PKR']);

    $this->actingAs(conversionUser())->postJson(route('procurement.purchase-requisitions.store-rfq', $pr), ['vendor_ids' => [$vendor], 'due_date' => '2026-10-01'])->assertUnprocessable();
    $this->assertDatabaseHas('purchase_requisitions', ['id' => $pr->id, 'status' => 'approved', 'converted_to_id' => null]);
    $this->assertDatabaseCount('request_for_quotations', 0);
});

it('shows invited vendors regardless of vendor type on the quotation form', function () {
    Schema::table('vendors', function (Blueprint $table) {
        $table->string('vendor_type')->nullable();
    });
    $vendor = conversionVendor();
    DB::table('vendors')->where('id', $vendor)->update(['vendor_type' => 'transport']);
    DB::table('vendors')->insert(['code' => 'OTHER', 'name' => 'Uninvited Vendor', 'status' => 'active', 'vendor_type' => 'supplier']);
    $rfq = DB::table('request_for_quotations')->insertGetId([
        'number' => 'RFQ-TRANSPORT', 'issue_date' => '2026-09-24', 'due_date' => '2026-10-01',
        'currency_code' => 'PKR', 'purpose' => 'Transport quotation', 'status' => 'issued',
    ]);
    DB::table('rfq_vendors')->insert(['request_for_quotation_id' => $rfq, 'vendor_id' => $vendor, 'status' => 'invited']);

    $this->actingAs(conversionUser())->get(route('procurement.quotations.create', $rfq))
        ->assertSee('Test Vendor')->assertDontSee('Uninvited Vendor');
});

it('renders the requisition edit form with working update and cancel URLs', function () {
    foreach (['2026_09_11_112209_create_departments_table.php', '2026_09_11_112335_create_cost_centers_table.php'] as $migration) {
        (require database_path('migrations/'.$migration))->up();
    }
    $requisition = conversionRequisition('draft');

    $this->actingAs(conversionUser(['purchase.view', 'purchase_request.create']))
        ->get(route('procurement.purchase-requisitions.edit', $requisition))
        ->assertSee('Edit Purchase Requisition')
        ->assertSee('action="'.route('procurement.purchase-requisitions.update', $requisition).'"', false)
        ->assertSee('href="'.route('procurement.purchase-requisitions.index').'"', false)
        ->assertSee('name="_method" value="PUT"', false);
});
