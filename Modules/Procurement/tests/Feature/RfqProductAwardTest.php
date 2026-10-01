<?php

use App\Contracts\SequenceGenerator;
use App\Enums\UserStatus;
use App\Models\Organization;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Inventory\Models\Brand;
use Modules\Inventory\Models\Origin;
use Modules\Procurement\Actions\AwardRfqLines;
use Modules\Procurement\Contracts\PurchaseOrderManager;
use Modules\Procurement\Exceptions\PurchaseOrderException;
use Modules\Procurement\Exceptions\RfqException;
use Modules\Procurement\Models\PurchaseOrder;
use Modules\Procurement\Models\PurchaseRequisition;
use Modules\Procurement\Models\RequestForQuotation;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
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
        '2026_09_11_112209_create_departments_table.php',
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
        '2026_01_12_000004_create_vendor_quotations_table.php',
        '2026_01_12_000005_create_vendor_quotation_lines_table.php',
        '2026_09_26_050453_add_withholding_tax_to_vendor_quotations.php',
        '2026_01_13_000001_create_purchase_orders_table.php',
        '2026_01_13_000002_create_purchase_order_lines_table.php',
        '2026_09_24_084055_add_awarded_quotation_line_id_to_rfq_lines_table.php',
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
    Schema::table('rfq_lines', function (Blueprint $table): void {
        $table->unsignedBigInteger('origin_id')->nullable();
    });
    (require base_path('Modules/Procurement/database/migrations/2026_09_26_053858_add_origin_to_purchase_order_lines.php'))->up();
    (require base_path('Modules/Procurement/database/migrations/2026_09_26_055345_add_withholding_tax_to_purchase_order_lines.php'))->up();

});

function productAwardUser(array $permissions = ['purchase.view', 'rfq.issue', 'purchase_order.create']): User
{
    $user = User::factory()->create(['status' => UserStatus::Active]);
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
        $user->givePermissionTo($permission);
    }

    return $user;
}

/** @return array{rfq: RequestForQuotation, vendors: array<int, int>, quotations: array<int, int>, lines: array<int, int>, quotes: array<int, array<int, int>>} */
function productAwardFixture(): array
{
    $id = DB::table('request_for_quotations')->insertGetId([
        'number' => 'RFQ-PRODUCTS', 'issue_date' => '2026-09-24', 'due_date' => '2026-10-01',
        'currency_code' => 'PKR', 'purpose' => 'Three products', 'status' => 'receiving',
    ]);
    $unit = DB::table('units')->insertGetId(['code' => 'EA', 'name' => 'Each']);
    $lines = [];
    $items = [];
    for ($i = 0; $i < 3; $i++) {
        $items[$i] = DB::table('items')->insertGetId(['code' => 'ITEM'.$i, 'name' => 'Product '.$i]);
        $lines[$i] = DB::table('rfq_lines')->insertGetId([
            'request_for_quotation_id' => $id, 'item_id' => $items[$i], 'unit_id' => $unit,
            'quantity' => '2.0000', 'position' => $i, 'specification' => 'Grade A',
            'brand_id' => Brand::factory()->create()->id,
            'origin_id' => Origin::create(['name' => 'Origin '.$i, 'status' => 'active'])->id,
        ]);
    }
    $vendors = $quotations = $quotes = [];
    foreach (['Libby Cruz', 'Raftarr Goods'] as $v => $name) {
        $vendors[$v] = DB::table('vendors')->insertGetId(['code' => 'V'.$v, 'name' => $name, 'status' => 'active']);
        DB::table('rfq_vendors')->insert(['request_for_quotation_id' => $id, 'vendor_id' => $vendors[$v], 'status' => 'submitted']);
        $quotations[$v] = DB::table('vendor_quotations')->insertGetId([
            'request_for_quotation_id' => $id, 'vendor_id' => $vendors[$v],
            'quoted_at' => '2026-09-24', 'valid_until' => '2026-10-01', 'currency_code' => 'PKR', 'status' => 'submitted',
        ]);
        foreach ($lines as $i => $line) {
            $price = $v === 0 ? [10, 20, 40][$i] : [12, 25, 30][$i];
            $quotes[$v][$i] = DB::table('vendor_quotation_lines')->insertGetId([
                'vendor_quotation_id' => $quotations[$v], 'rfq_line_id' => $line, 'item_id' => $items[$i],
                'quantity' => 2, 'unit_price' => $price, 'line_total' => $price * 2,
                'tax_rate' => 10, 'tax_amount' => $price / 5, 'wht_tax_rate' => 5.5, 'position' => $i,
            ]);
        }
    }

    return ['rfq' => RequestForQuotation::findOrFail($id), 'vendors' => $vendors, 'quotations' => $quotations, 'lines' => $lines, 'quotes' => $quotes];
}

it('downloads purchase orders as PDFs and protects both print formats', function () {
    (require database_path('migrations/2026_01_03_000001_create_addresses_table.php'))->up();
    $data = productAwardFixture();
    Organization::create(['name' => 'ACEMIX', 'currency_code' => 'PKR']);
    $order = PurchaseOrder::create([
        'number' => 'PO-PDF', 'vendor_id' => $data['vendors'][0], 'order_date' => '2026-09-24',
        'currency_code' => 'PKR', 'status' => 'draft', 'subtotal' => 100, 'tax_total' => 10, 'total' => 104.5,
    ]);
    $order->lines()->create(['position' => 1, 'item_id' => DB::table('rfq_lines')->where('id', $data['lines'][0])->value('item_id'), 'quantity' => 2, 'unit_price' => 50, 'line_subtotal' => 100, 'line_tax' => 10, 'line_total' => 104.5]);
    $url = route('procurement.purchase-orders.print', ['purchaseOrder' => $order, 'download' => 1]);
    $this->get($url)->assertRedirect(route('login'));
    $this->actingAs(productAwardUser([]))->get($url)->assertForbidden();
    $this->get(route('procurement.purchase-orders.print', $order))->assertForbidden();
    $this->actingAs(productAwardUser(['purchase.view']));
    $this->get(route('procurement.purchase-orders.print', $order))->assertSee('Download PDF')->assertSee('104.50');
    $response = $this->get($url);
    $response->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertDownload('po-pdf.pdf');
    expect($response->getContent())->toStartWith('%PDF-');
});

it('prints vendor requests with terms without disclosing other vendors or quotes', function (string $status) {
    $data = productAwardFixture();
    $data['rfq']->update(['status' => $status, 'terms' => 'Delivery included <script>alert(1)</script>']);
    $this->actingAs(productAwardUser());

    $this->get(route('procurement.rfqs.print-request', $data['rfq']))
        ->assertOk()->assertSee('REQUEST FOR QUOTATION')->assertSee('RFQ-PRODUCTS')
        ->assertSee('Product 0')->assertSee('Grade A')->assertSee('Origin 0')
        ->assertSee('Delivery included <script>alert(1)</script>')
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertDontSee('Libby Cruz')->assertDontSee('Raftarr Goods');
    $this->get(route('procurement.rfqs.show', $data['rfq']))
        ->assertSee('href="'.route('procurement.rfqs.print-request', $data['rfq']).'"', false);
})->with(['issued', 'receiving']);

it('prints the selected invited vendor details and excludes other vendors', function () {
    (require database_path('migrations/2026_01_03_000001_create_addresses_table.php'))->up();
    $data = productAwardFixture();
    $vendor = Vendor::findOrFail($data['vendors'][0]);
    $vendor->update(['email' => 'quotes@example.test', 'phone' => '555-0100', 'tax_number' => 'TAX-123']);
    $vendor->addresses()->create([
        'type' => 'billing', 'address_line1' => '12 Market Road', 'city' => 'Karachi', 'country' => 'PK',
    ]);
    $url = route('procurement.rfqs.print-request', ['rfq' => $data['rfq'], 'vendor' => $vendor]);
    $this->actingAs(productAwardUser());

    $this->get($url)->assertOk()->assertSee('Libby Cruz')->assertSee('V0')
        ->assertSee('quotes@example.test')->assertSee('555-0100')->assertSee('TAX-123')
        ->assertSee('12 Market Road, Karachi, PK')->assertDontSee('Raftarr Goods');
    $this->get(route('procurement.rfqs.show', $data['rfq']))->assertSee('href="'.$url.'"', false);
});

it('rejects printing a vendor who was not invited to the RFQ', function () {
    $data = productAwardFixture();
    $vendor = Vendor::create(['code' => 'OTHER', 'name' => 'Not invited']);

    $this->actingAs(productAwardUser())->get(route('procurement.rfqs.print-request', [
        'rfq' => $data['rfq'], 'vendor' => $vendor,
    ]))->assertNotFound();
});

it('rejects vendor request printing outside the quotation response stage', function (string $status) {
    $data = productAwardFixture();
    $data['rfq']->update(['status' => $status]);

    $this->actingAs(productAwardUser())->get(route('procurement.rfqs.print-request', $data['rfq']))->assertNotFound();
})->with(['draft', 'cancelled', 'awarded', 'closed']);

it('protects vendor request printing with authentication and RFQ view permission', function () {
    $data = productAwardFixture();

    $this->get(route('procurement.rfqs.print-request', $data['rfq']))->assertRedirect(route('login'));
    $this->actingAs(productAwardUser([]))->get(route('procurement.rfqs.print-request', $data['rfq']))->assertForbidden();
});

function productSelections(array $data): array
{
    return [$data['lines'][0] => $data['quotes'][0][0], $data['lines'][1] => $data['quotes'][0][1], $data['lines'][2] => $data['quotes'][1][2]];
}

it('allows an owner with award permission but no issue permission to confirm product awards', function () {
    $data = productAwardFixture();
    $role = Role::findOrCreate('owner', 'web');
    foreach (['rfq.view', 'rfq.award'] as $permission) {
        $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
    $user = productAwardUser([]);
    $user->assignRole($role);
    expect($user->can('rfq.issue'))->toBeFalse();

    $this->actingAs($user)->get(route('procurement.rfqs.compare', $data['rfq']))
        ->assertOk()->assertSee('Confirm Product Awards');
    $this->patch(route('procurement.rfqs.award-lines', $data['rfq']), ['selections' => productSelections($data)])
        ->assertRedirect(route('procurement.rfqs.compare', $data['rfq']));
    $this->assertDatabaseHas('request_for_quotations', ['id' => $data['rfq']->id, 'status' => 'awarded', 'awarded_by' => $user->id]);
    $this->get(route('procurement.rfqs.compare', $data['rfq']))->assertDontSee('Confirm Product Awards');
});

it('awards two products to one vendor and one to another and creates accurate separate orders', function () {
    $data = productAwardFixture();
    $user = productAwardUser();
    $this->actingAs($user);
    app(SequenceGenerator::class)->register('purchase_order', 'PO');

    $this->get(route('procurement.rfqs.show', $data['rfq']))->assertOk()->assertSee('Quotations recorded')->assertSee('Requested items');

    $comparison = $this->get(route('procurement.rfqs.compare', $data['rfq']));
    $comparison->assertSee('Lowest rate')->assertSee('Confirm Product Awards');
    expect(array_column($comparison->viewData('comparison')['lines'], 'best_unit_price'))->toBe(['10.0000', '20.0000', '30.0000']);
    $this->patch(route('procurement.rfqs.award-lines', $data['rfq']), ['selections' => productSelections($data)])
        ->assertRedirect(route('procurement.rfqs.compare', $data['rfq']));
    foreach (productSelections($data) as $line => $quote) {
        $this->assertDatabaseHas('rfq_lines', ['id' => $line, 'awarded_quotation_line_id' => $quote]);
    }
    $this->assertDatabaseHas('request_for_quotations', ['id' => $data['rfq']->id, 'status' => 'awarded', 'awarded_quotation_id' => null, 'awarded_by' => $user->id]);
    foreach ($data['quotations'] as $quotation) {
        $this->assertDatabaseHas('vendor_quotations', ['id' => $quotation, 'status' => 'awarded']);
    }
    $this->get(route('procurement.rfqs.compare', $data['rfq']))->assertSee('Libby Cruz')->assertSee('Raftarr Goods')->assertDontSee('Confirm Product Awards');
    $this->get(route('procurement.rfqs.print', $data['rfq']))->assertSee('Libby Cruz')->assertSee('Raftarr Goods');
    $this->post(route('procurement.rfqs.create-po', $data['rfq']))->assertRedirect(route('procurement.rfqs.compare', $data['rfq']));
    $orders = DB::table('purchase_orders')->get()->keyBy('vendor_id');
    expect($orders)->toHaveCount(2);
    expect((string) $orders[$data['vendors'][0]]->total)->toBe('66');
    expect((string) $orders[$data['vendors'][1]]->total)->toBe('66');
    $this->assertDatabaseHas('purchase_order_lines', ['purchase_order_id' => $orders[$data['vendors'][1]]->id, 'rfq_line_id' => $data['lines'][2], 'quantity' => 2, 'unit_price' => 30, 'specification' => 'Grade A', 'unit_id' => 1]);
    foreach ($data['rfq']->fresh()->lines as $rfqLine) {
        $this->assertDatabaseHas('purchase_order_lines', ['rfq_line_id' => $rfqLine->id, 'brand_id' => $rfqLine->brand_id, 'origin_id' => $rfqLine->origin_id, 'wht_tax_rate' => 5.5]);
    }
    $this->assertDatabaseCount('purchase_order_lines', 3);
    $this->postJson(route('procurement.rfqs.create-po', $data['rfq']))->assertForbidden();
    $this->assertDatabaseCount('purchase_orders', 2);
});

it('rejects incomplete or mismatched product selections without awarding any products', function (string $case) {
    $data = productAwardFixture();
    $selections = productSelections($data);
    if ($case === 'missing') {
        unset($selections[$data['lines'][2]]);
    } else {
        $selections[$data['lines'][0]] = $data['quotes'][1][1];
    }

    $this->actingAs(productAwardUser())->patchJson(route('procurement.rfqs.award-lines', $data['rfq']), ['selections' => $selections])->assertUnprocessable();
    $this->assertDatabaseHas('request_for_quotations', ['id' => $data['rfq']->id, 'status' => 'receiving']);
    expect(DB::table('rfq_lines')->whereNotNull('awarded_quotation_line_id')->count())->toBe(0);
})->with(['missing', 'wrong product']);

it('rejects ineligible quotations and excludes their rates from the lowest rate', function (string $case) {
    $data = productAwardFixture();
    match ($case) {
        'currency' => DB::table('vendor_quotations')->where('id', $data['quotations'][0])->update(['currency_code' => 'USD']),
        'expired' => DB::table('vendor_quotations')->where('id', $data['quotations'][0])->update(['valid_until' => '2026-09-23']),
        'quantity' => DB::table('vendor_quotation_lines')->where('id', $data['quotes'][0][0])->update(['quantity' => 1]),
        'draft' => DB::table('vendor_quotations')->where('id', $data['quotations'][0])->update(['status' => 'draft']),
    };
    $this->actingAs(productAwardUser());

    $comparison = $this->get(route('procurement.rfqs.compare', $data['rfq']));
    expect($comparison->viewData('comparison')['lines'][0]['best_unit_price'])->toBe('12.0000');
    $this->patchJson(route('procurement.rfqs.award-lines', $data['rfq']), ['selections' => productSelections($data)])->assertUnprocessable();
    expect(DB::table('rfq_lines')->whereNotNull('awarded_quotation_line_id')->count())->toBe(0);
})->with(['currency', 'expired', 'quantity', 'draft']);

it('checks award permissions and purchase order creation permissions independently', function () {
    $data = productAwardFixture();

    $this->actingAs(productAwardUser(['purchase.view']))->patchJson(route('procurement.rfqs.award-lines', $data['rfq']), ['selections' => productSelections($data)])->assertForbidden();
    $user = productAwardUser(['purchase.view', 'rfq.issue']);
    $this->actingAs($user)->patch(route('procurement.rfqs.award-lines', $data['rfq']), ['selections' => productSelections($data)])->assertRedirect();
    $this->postJson(route('procurement.rfqs.create-po', $data['rfq']))->assertForbidden();
    $this->assertDatabaseCount('purchase_orders', 0);
});

it('prevents a stale request from changing confirmed awards', function () {
    $data = productAwardFixture();
    $user = productAwardUser();
    app(AwardRfqLines::class)->execute($data['rfq'], productSelections($data), $user->id);

    expect(fn () => app(AwardRfqLines::class)->execute($data['rfq'], productSelections($data), $user->id))->toThrow(RfqException::class);
});

it('keeps the existing whole quotation award endpoint working', function () {
    $data = productAwardFixture();

    $this->actingAs(productAwardUser())->patch(route('procurement.quotations.award', [$data['rfq'], $data['quotations'][0]]))->assertRedirect();
    $this->assertDatabaseHas('request_for_quotations', ['id' => $data['rfq']->id, 'awarded_quotation_id' => $data['quotations'][0]]);
    $this->assertDatabaseHas('vendor_quotations', ['id' => $data['quotations'][1], 'status' => 'rejected']);
    expect(DB::table('rfq_lines')->whereNotNull('awarded_quotation_line_id')->count())->toBe(3);
});

it('compares rates at four decimal places and allows ties', function () {
    $data = productAwardFixture();
    DB::table('vendor_quotation_lines')->where('id', $data['quotes'][0][0])->update(['unit_price' => '10.0001']);
    DB::table('vendor_quotation_lines')->where('id', $data['quotes'][1][0])->update(['unit_price' => '10.0002']);
    DB::table('vendor_quotation_lines')->where('id', $data['quotes'][1][1])->update(['unit_price' => '20.0000']);

    $response = $this->actingAs(productAwardUser())->get(route('procurement.rfqs.compare', $data['rfq']));
    expect($response->viewData('comparison')['lines'][0]['best_unit_price'])->toBe('10.0001');
    expect(substr_count($response->getContent(), '>Lowest rate</span>'))->toBe(4);
});

it('records only priced products and retains a genuinely zero rate', function () {
    $data = productAwardFixture();
    $lines = [];
    foreach ($data['rfq']->lines as $index => $line) {
        $lines[] = ['rfq_line_id' => $line->id, 'item_id' => $line->item_id, 'quantity' => 2, 'unit_price' => $index === 0 ? '0' : ''];
    }

    $this->actingAs(productAwardUser())->post(route('procurement.quotations.store', $data['rfq']), [
        'vendor_id' => $data['vendors'][0], 'quoted_at' => '2026-09-24', 'currency_code' => 'PKR', 'lines' => $lines,
    ])->assertRedirect(route('procurement.rfqs.show', $data['rfq']));
    $rows = DB::table('vendor_quotation_lines')->where('vendor_quotation_id', $data['quotations'][0])->get();
    expect($rows)->toHaveCount(1);
    expect((string) $rows->first()->unit_price)->toBe('0');
});

it('rejects a quotation with no priced products', function () {
    $data = productAwardFixture();
    $line = $data['rfq']->lines->first();

    $this->actingAs(productAwardUser())->postJson(route('procurement.quotations.store', $data['rfq']), [
        'vendor_id' => $data['vendors'][0], 'quoted_at' => '2026-09-24', 'currency_code' => 'PKR',
        'lines' => [['rfq_line_id' => $line->id, 'item_id' => $line->item_id, 'quantity' => 2, 'unit_price' => '']],
    ])->assertUnprocessable()->assertJsonValidationErrors('lines');
    $this->assertDatabaseCount('vendor_quotation_lines', 6);
});

it('supports purchase orders for legacy whole RFQ awards', function () {
    $data = productAwardFixture();
    $warehouse = Warehouse::create(['code' => 'WH-PO', 'name' => 'PO Receiving', 'status' => 'active']);
    $requisition = PurchaseRequisition::create(['number' => 'PR-WH', 'requested_date' => '2026-09-24', 'purpose' => 'Restock', 'status' => 'converted', 'warehouse_id' => $warehouse->id]);
    $data['rfq']->purchaseRequisitions()->attach($requisition);
    DB::table('request_for_quotations')->where('id', $data['rfq']->id)->update(['status' => 'awarded', 'awarded_quotation_id' => $data['quotations'][0]]);
    DB::table('vendor_quotations')->where('id', $data['quotations'][0])->update(['status' => 'awarded']);
    app(SequenceGenerator::class)->register('purchase_order', 'PO');

    $this->actingAs(productAwardUser())->post(route('procurement.rfqs.create-po', $data['rfq']))->assertRedirect();
    $this->assertDatabaseCount('purchase_orders', 1);
    $this->assertDatabaseCount('purchase_order_lines', 3);
    $this->assertDatabaseHas('purchase_orders', ['warehouse_id' => $warehouse->id]);
    $otherWarehouse = Warehouse::create(['code' => 'OTHER', 'name' => 'Other warehouse', 'status' => 'active']);
    $otherOrder = PurchaseOrder::firstOrFail()->replicate();
    $otherOrder->fill(['number' => 'PO-OTHER', 'warehouse_id' => $otherWarehouse->id])->save();
    $results = app(PurchaseOrderManager::class)->paginate(['warehouse_id' => $warehouse->id]);
    expect($results->total())->toBe(1)
        ->and($results->first()->warehouse->name)->toBe('PO Receiving');
});

it('rejects a quotation from another RFQ and rolls back earlier selections', function () {
    $data = productAwardFixture();
    $other = DB::table('request_for_quotations')->insertGetId(['number' => 'OTHER', 'issue_date' => '2026-09-24', 'due_date' => '2026-10-01', 'currency_code' => 'PKR', 'purpose' => 'Other RFQ', 'status' => 'receiving']);
    DB::table('vendor_quotations')->where('id', $data['quotations'][1])->update(['request_for_quotation_id' => $other]);

    $this->actingAs(productAwardUser())->patchJson(route('procurement.rfqs.award-lines', $data['rfq']), ['selections' => productSelections($data)])->assertUnprocessable();
    expect(DB::table('rfq_lines')->whereNotNull('awarded_quotation_line_id')->count())->toBe(0);
});

it('requires a vendor invitation when awarding products', function () {
    $data = productAwardFixture();
    DB::table('rfq_vendors')->where('vendor_id', $data['vendors'][0])->delete();
    $this->actingAs(productAwardUser());

    $comparison = $this->get(route('procurement.rfqs.compare', $data['rfq']));
    expect($comparison->viewData('comparison')['lines'][0]['best_unit_price'])->toBe('12.0000');
    $this->patchJson(route('procurement.rfqs.award-lines', $data['rfq']), ['selections' => productSelections($data)])->assertUnprocessable();
});

it('does not allow quotations to be replaced after product awards', function () {
    $data = productAwardFixture();
    $user = productAwardUser();
    app(AwardRfqLines::class)->execute($data['rfq'], productSelections($data), $user->id);
    $line = $data['rfq']->lines->first();

    $this->actingAs($user)->postJson(route('procurement.quotations.store', $data['rfq']), [
        'vendor_id' => $data['vendors'][0], 'quoted_at' => '2026-09-24', 'currency_code' => 'PKR',
        'lines' => [['rfq_line_id' => $line->id, 'item_id' => $line->item_id, 'quantity' => 2, 'unit_price' => 1]],
    ])->assertUnprocessable();
    $this->assertDatabaseHas('vendor_quotation_lines', ['id' => $data['quotes'][0][0], 'unit_price' => 10]);
});

it('requires an authenticated active user to confirm product awards', function () {
    $data = productAwardFixture();
    $url = route('procurement.rfqs.award-lines', $data['rfq']);
    $payload = ['selections' => productSelections($data)];

    $this->patch($url, $payload)->assertRedirect(route('login'));
    $user = productAwardUser();
    $user->update(['status' => UserStatus::Suspended]);
    $this->actingAs($user)->patch($url, $payload)->assertRedirect(route('login'));
    expect(DB::table('rfq_lines')->whereNotNull('awarded_quotation_line_id')->count())->toBe(0);
});

it('prevents duplicate purchase orders even when passed a stale RFQ model', function () {
    $data = productAwardFixture();
    $user = productAwardUser();
    $awarded = app(AwardRfqLines::class)->execute($data['rfq'], productSelections($data), $user->id);
    app(SequenceGenerator::class)->register('purchase_order', 'PO');
    $orders = app(PurchaseOrderManager::class);
    $orders->createOrdersFromRfq($awarded, $user->id);

    expect(fn () => $orders->createOrdersFromRfq($awarded, $user->id))->toThrow(PurchaseOrderException::class);
    $this->assertDatabaseCount('purchase_orders', 2);
});

it('saves separate GST and WHT amounts and displays them in comparisons', function () {
    $data = productAwardFixture();
    $lines = $data['rfq']->lines->take(2)->map(fn ($line) => [
        'rfq_line_id' => $line->id, 'item_id' => $line->item_id,
        'quantity' => 2, 'unit_price' => '125.50', 'tax_rate' => '18', 'wht_tax_rate' => '5.5',
    ])->all();

    $this->actingAs(productAwardUser())->post(route('procurement.quotations.store', $data['rfq']), [
        'vendor_id' => $data['vendors'][0], 'quoted_at' => '2026-09-24', 'currency_code' => 'PKR', 'lines' => $lines,
    ])->assertRedirect(route('procurement.rfqs.show', $data['rfq']))->assertSessionHasNoErrors();

    $this->assertDatabaseHas('vendor_quotation_lines', [
        'vendor_quotation_id' => $data['quotations'][0], 'rfq_line_id' => $lines[0]['rfq_line_id'],
        'tax_rate' => 18, 'wht_tax_rate' => 5.5, 'tax_amount' => 45.18, 'wht_tax_amount' => 13.805,
    ]);
    $this->assertDatabaseHas('vendor_quotations', [
        'id' => $data['quotations'][0], 'subtotal' => 502, 'tax_total' => 90.36, 'wht_tax_total' => 27.61, 'total' => 564.75,
    ]);
    DB::table('vendor_quotations')->where('id', $data['quotations'][0])->update(['status' => 'submitted']);
    $this->get(route('procurement.rfqs.compare', $data['rfq']))
        ->assertSee('GST amount: 45.18')->assertSee('WHT (5.50%): 13.81')->assertSee('564.75');
});

it('rejects invalid withholding rates without replacing quotation lines', function (mixed $rate) {
    $data = productAwardFixture();
    $line = $data['rfq']->lines->first();

    $this->actingAs(productAwardUser())->postJson(route('procurement.quotations.store', $data['rfq']), [
        'vendor_id' => $data['vendors'][0], 'quoted_at' => '2026-09-24', 'currency_code' => 'PKR',
        'lines' => [['rfq_line_id' => $line->id, 'item_id' => $line->item_id, 'quantity' => 2, 'unit_price' => 100, 'wht_tax_rate' => $rate]],
    ])->assertUnprocessable()->assertJsonValidationErrors('lines.0.wht_tax_rate');

    $this->assertDatabaseCount('vendor_quotation_lines', 6);
})->with([-1, 101, 'invalid']);
