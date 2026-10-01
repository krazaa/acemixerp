<?php

use App\Contracts\SequenceGenerator;
use App\Contracts\StockLedger;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ViewErrorBag;
use Modules\Inventory\Database\Seeders\BrandPermissionsSeeder;
use Modules\Inventory\Models\Brand;
use Modules\Inventory\Models\Origin;
use Modules\Inventory\Models\StockBatch;
use Modules\Procurement\Actions\ConvertRequisitionToRfq;
use Modules\Procurement\Contracts\GoodsReceiptManager;
use Modules\Procurement\Events\GoodsReceived;
use Modules\Procurement\Models\GoodsReceipt;
use Modules\Procurement\Models\GoodsReceiptLine;
use Modules\Procurement\Models\PurchaseOrder;
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
        '2026_09_11_062704_create_permission_tables.php',
        '2026_09_11_074816_create_organizations_table.php',
        '2026_09_11_075115_2026_01_01_000003_create_document_sequences_table.php',
        '2026_01_03_000003_create_vendors_table.php',
        '2026_01_04_000002_create_units_table.php',
        '2026_01_04_000003_create_items_table.php',
    ] as $migration) {
        (require database_path('migrations/'.$migration))->up();
    }
    foreach ([
        '2026_09_14_081105_create_purchase_requisitions_table.php',
        '2026_09_15_081050_create_purchase_requisition_lines_table.php',
        '2026_01_12_000001_create_request_for_quotations_table.php',
        '2026_01_12_000002_create_rfq_lines_table.php',
        '2026_01_12_000003_create_rfq_vendors_table.php',
        '2026_01_12_000004_create_vendor_quotations_table.php',
        '2026_01_12_000005_create_vendor_quotation_lines_table.php',
        '2026_01_13_000001_create_purchase_orders_table.php',
        '2026_01_13_000002_create_purchase_order_lines_table.php',
        '2026_09_24_084055_add_awarded_quotation_line_id_to_rfq_lines_table.php',
        '2026_01_13_000003_create_goods_receipts_table.php',
        '2026_01_13_000004_create_goods_receipt_lines_table.php',
        '2026_09_15_155656_create_supplier_invoices_table.php',
        '2026_09_15_155706_create_supplier_invoice_lines_table.php',
        '2026_09_18_164854_add_batch_number_to_goods_receipt_lines_table.php',
        '2026_09_21_091052_allow_standalone_supplier_invoices.php',
        '2026_09_22_105629_add_withholding_tax_to_supplier_invoices.php',
    ] as $migration) {
        (require base_path('Modules/Procurement/database/migrations/'.$migration))->up();
    }
    Schema::table('users', function (Blueprint $table): void {
        $table->softDeletes();
    });
    Schema::table('goods_receipt_lines', function (Blueprint $table): void {
        $table->date('expiry_date')->nullable();
    });
    (require base_path('Modules/Inventory/database/migrations/2026_09_25_151229_create_brands_and_add_brands_to_procurement_lines.php'))->up();
    (require base_path('Modules/Procurement/database/migrations/2026_09_26_053858_add_origin_to_purchase_order_lines.php'))->up();
    (require base_path('Modules/Procurement/database/migrations/2026_09_26_063855_add_origin_to_goods_receipt_lines.php'))->up();
    (require base_path('Modules/Procurement/database/migrations/2026_09_26_065145_add_manufacturing_date_to_goods_receipt_lines.php'))->up();
    (require base_path('Modules/Procurement/database/migrations/2026_09_26_055345_add_withholding_tax_to_purchase_order_lines.php'))->up();
    foreach (['rfq_lines', 'purchase_requisition_lines'] as $tableName) {
        Schema::table($tableName, function (Blueprint $table): void {
            $table->unsignedBigInteger('origin_id')->nullable();
        });
    }

});

function brandUser(array $permissions = ['brands.view', 'brands.create', 'brands.update', 'brands.delete']): User
{
    $user = User::factory()->create(['status' => UserStatus::Active]);
    foreach ($permissions as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    return $user;
}

function brandOrder(Brand $brand): PurchaseOrder
{
    $vendor = DB::table('vendors')->insertGetId(['code' => 'VB', 'name' => 'Vendor', 'status' => 'active']);
    $item = DB::table('items')->insertGetId(['code' => 'IB', 'name' => 'Inventory Item']);
    $po = PurchaseOrder::create(['number' => 'PO-BRAND', 'vendor_id' => $vendor, 'order_date' => '2026-09-24', 'currency_code' => 'PKR', 'status' => 'issued', 'warehouse_id' => 1]);
    $po->lines()->create(['item_id' => $item, 'brand_id' => $brand->id, 'quantity' => 10, 'received_quantity' => 5, 'unit_price' => 10, 'line_subtotal' => 100, 'line_total' => 100]);

    return $po;
}

it('creates edits lists and deletes unused brands', function () {
    $this->actingAs(brandUser())->get(route('inventory.brands.create'))->assertOk()->assertSee('Brand name');
    $this->post(route('inventory.brands.store'), ['name' => 'Atlas', 'status' => 'active', 'description' => 'Building materials'])
        ->assertSessionHasNoErrors()->assertRedirect(route('inventory.brands.index'));
    $brand = Brand::firstOrFail();
    $this->get(route('inventory.brands.edit', $brand))->assertOk()->assertSee('Atlas');
    $this->put(route('inventory.brands.update', $brand), ['name' => 'Atlas Cement', 'status' => 'inactive', 'description' => null])
        ->assertSessionHasNoErrors()->assertRedirect(route('inventory.brands.index'));
    $this->assertDatabaseHas('brands', ['id' => $brand->id, 'name' => 'Atlas Cement', 'status' => 'inactive', 'description' => null]);
    $this->get(route('inventory.brands.index', ['search' => 'Atlas']))->assertOk()->assertSee('Atlas Cement');
    $this->delete(route('inventory.brands.destroy', $brand))->assertRedirect(route('inventory.brands.index'));
    $this->assertSoftDeleted($brand);
});

it('validates duplicate brand names and allows an unchanged name on edit', function () {
    $brand = Brand::factory()->create(['name' => 'Atlas']);
    $this->actingAs(brandUser())->post(route('inventory.brands.store'), ['name' => 'Atlas', 'status' => 'active'])->assertSessionHasErrors('name');
    $this->put(route('inventory.brands.update', $brand), ['name' => 'Atlas', 'status' => 'active'])->assertSessionHasNoErrors();
    $this->assertDatabaseCount('brands', 1);
});

it('protects brand management permissions', function () {
    $this->get(route('inventory.brands.index'))->assertRedirect(route('login'));
    $this->actingAs(brandUser([]))->post(route('inventory.brands.store'), ['name' => 'Unauthorized', 'status' => 'active'])->assertForbidden();
    $this->assertDatabaseCount('brands', 0);
});

it('seeds brand permissions idempotently', function () {
    $this->seed(BrandPermissionsSeeder::class);
    $this->seed(BrandPermissionsSeeder::class);

    expect(Permission::where('name', 'like', 'brands.%')->count())->toBe(4);
});

it('prevents deleting a brand used on a purchase document', function () {
    $brand = Brand::factory()->create();
    brandOrder($brand);

    $this->actingAs(brandUser())->delete(route('inventory.brands.destroy', $brand))->assertSessionHasErrors('brand');

    $this->assertNotSoftDeleted($brand);
});

it('stores a separate brand for each requisition line and preserves it when converting to RFQ', function () {
    $first = Brand::factory()->create();
    $second = Brand::factory()->create();
    $item = DB::table('items')->insertGetId(['code' => 'CEMENT', 'name' => 'Cement']);
    DB::table('organizations')->insert(['name' => 'Test Organization', 'currency_code' => 'PKR']);
    app(SequenceGenerator::class)->register('purchase_requisition', 'PR');
    app(SequenceGenerator::class)->register('rfq', 'RFQ');
    $user = brandUser(['purchase_request.create', 'rfq.create']);
    $payload = ['requested_date' => '2026-09-24', 'purpose' => 'Materials', 'lines' => [
        ['item_id' => $item, 'brand_id' => $first->id, 'quantity' => 2],
        ['item_id' => $item, 'brand_id' => $second->id, 'quantity' => 3],
    ]];

    $this->actingAs($user)->post(route('procurement.purchase-requisitions.store'), $payload)->assertSessionHasNoErrors()->assertRedirect();
    $pr = PurchaseRequisition::firstOrFail();
    expect($pr->lines->pluck('brand_id')->all())->toBe([$first->id, $second->id]);
    $pr->update(['status' => 'approved']);
    $vendorId = DB::table('vendors')->insertGetId(['code' => 'V-PR', 'name' => 'Vendor', 'status' => 'active']);
    $rfq = app(ConvertRequisitionToRfq::class)->execute($pr, ['due_date' => '2026-10-01', 'vendor_ids' => [$vendorId]], $user->id);

    expect($rfq->lines->pluck('brand_id')->all())->toBe([$first->id, $second->id]);
});

it('rejects a deleted brand before creating a requisition', function () {
    $brand = Brand::factory()->create();
    $brand->delete();
    $item = DB::table('items')->insertGetId(['code' => 'I', 'name' => 'Item']);

    $this->actingAs(brandUser(['purchase_request.create']))->post(route('procurement.purchase-requisitions.store'), [
        'requested_date' => '2026-09-24', 'purpose' => 'Materials',
        'lines' => [['item_id' => $item, 'brand_id' => $brand->id, 'quantity' => 2]],
    ])->assertSessionHasErrors('lines.0.brand_id');

    $this->assertDatabaseCount('purchase_requisitions', 0);
});

it('inherits the order brand when saving receipts and invoices', function () {
    $brand = Brand::factory()->create();
    $po = brandOrder($brand);
    $line = $po->lines->first();
    $origin = Origin::create(['name' => 'Pakistan', 'status' => 'active']);
    $line->update(['origin_id' => $origin->id]);
    app(SequenceGenerator::class)->register('goods_receipt', 'GRN');
    app(SequenceGenerator::class)->register('supplier_invoice', 'SI');
    $this->actingAs(brandUser(['goods_receipt.create', 'supplier_invoice.create']));

    $this->post(route('procurement.goods-receipts.store'), [
        'purchase_order_id' => $po->id, 'received_date' => '2026-09-24',
        'lines' => [['purchase_order_line_id' => $line->id, 'item_id' => $line->item_id, 'received_quantity' => 2, 'accepted_quantity' => 2, 'batch_number' => 'B-1']],
    ])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('goods_receipt_lines', ['purchase_order_line_id' => $line->id, 'brand_id' => $brand->id, 'origin_id' => $origin->id]);

    $this->post(route('procurement.supplier-invoices.store'), [
        'purchase_order_id' => $po->id, 'vendor_id' => $po->vendor_id, 'vendor_invoice_number' => 'INV-1',
        'invoice_date' => '2026-09-24', 'due_date' => '2026-10-01', 'currency_code' => 'PKR',
        'lines' => [['purchase_order_line_id' => $line->id, 'item_id' => $line->item_id, 'quantity' => 2, 'unit_price' => 10]],
    ])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('supplier_invoice_lines', ['purchase_order_line_id' => $line->id, 'brand_id' => $brand->id]);
});

it('rejects conflicting brands on linked receipts without saving the header', function () {
    $po = brandOrder(Brand::factory()->create());
    $line = $po->lines->first();
    $other = Brand::factory()->create();
    app(SequenceGenerator::class)->register('goods_receipt', 'GRN');

    $this->actingAs(brandUser(['goods_receipt.create']))->post(route('procurement.goods-receipts.store'), [
        'purchase_order_id' => $po->id, 'received_date' => '2026-09-24',
        'lines' => [['purchase_order_line_id' => $line->id, 'item_id' => $line->item_id, 'brand_id' => $other->id, 'received_quantity' => 2, 'accepted_quantity' => 2, 'batch_number' => 'B-1']],
    ])->assertSessionHasErrors('lines.0.brand_id');

    $this->assertDatabaseCount('goods_receipts', 0);
});

it('renders the brand selector in each purchasing line editor', function (string $view) {
    $brand = Brand::factory()->create(['name' => '<Brand>']);

    $this->view('procurement::'.$view.'._line-row', ['index' => 0, 'line' => ['brand_id' => $brand->id], 'brands' => collect([$brand]), 'origins' => collect(), 'items' => collect(), 'units' => collect(), 'errors' => new ViewErrorBag])
        ->assertSee('lines[0][brand_id]', false)->assertSee('&lt;Brand&gt;', false);
})->with(['purchase-requisitions', 'rfqs', 'purchase-orders', 'goods-receipts', 'supplier-invoices']);

it('updates and clears brands on draft purchasing lines', function (string $document, string $permission, string $table) {
    $first = Brand::factory()->create();
    $second = Brand::factory()->create();
    $item = DB::table('items')->insertGetId(['code' => 'EDIT', 'name' => 'Editable item']);
    $vendor = DB::table('vendors')->insertGetId(['code' => 'EDIT', 'name' => 'Vendor', 'status' => 'active']);
    foreach (['purchase_requisition', 'rfq', 'purchase_order'] as $sequence) {
        app(SequenceGenerator::class)->register($sequence, 'DOC');
    }
    $payload = [
        'requested_date' => '2026-09-24', 'issue_date' => '2026-09-24', 'order_date' => '2026-09-24',
        'due_date' => '2026-10-01', 'purpose' => 'Brand selection', 'currency_code' => 'PKR',
        'vendor_ids' => [$vendor], 'vendor_id' => $vendor,
        'lines' => [['item_id' => $item, 'brand_id' => $first->id, 'quantity' => 2, 'unit_price' => 10]],
    ];
    $this->actingAs(brandUser([$permission]))->post(route('procurement.'.$document.'.store'), $payload)
        ->assertSessionHasNoErrors()->assertRedirect();
    $id = DB::table($table)->value('id');
    $payload['lines'][0]['brand_id'] = $second->id;

    $this->put(route('procurement.'.$document.'.update', $id), $payload)->assertSessionHasNoErrors()->assertRedirect();
    $this->assertDatabaseHas(match ($table) {
        'purchase_requisitions' => 'purchase_requisition_lines',
        'request_for_quotations' => 'rfq_lines',
        'purchase_orders' => 'purchase_order_lines',
    }, ['brand_id' => $second->id]);
    $payload['lines'][0]['brand_id'] = null;
    $this->put(route('procurement.'.$document.'.update', $id), $payload)->assertSessionHasNoErrors()->assertRedirect();
    $this->assertDatabaseHas(match ($table) {
        'purchase_requisitions' => 'purchase_requisition_lines',
        'request_for_quotations' => 'rfq_lines',
        'purchase_orders' => 'purchase_order_lines',
    }, ['brand_id' => null]);
})->with([
    ['purchase-requisitions', 'purchase_request.create', 'purchase_requisitions'],
    ['rfqs', 'rfq.create', 'request_for_quotations'],
    ['purchase-orders', 'purchase_order.create', 'purchase_orders'],
]);

it('rejects conflicting invoice brands and foreign purchase order lines', function (bool $wrongOrder) {
    $brand = Brand::factory()->create();
    $po = brandOrder($brand);
    $line = $po->lines->first();
    $other = Brand::factory()->create();
    app(SequenceGenerator::class)->register('supplier_invoice', 'SI');
    $orderId = $po->id;
    if ($wrongOrder) {
        $orderId = PurchaseOrder::create(['number' => 'PO-OTHER', 'vendor_id' => $po->vendor_id, 'order_date' => '2026-09-24', 'currency_code' => 'PKR'])->id;
    }

    $this->actingAs(brandUser(['supplier_invoice.create']))->post(route('procurement.supplier-invoices.store'), [
        'purchase_order_id' => $orderId, 'vendor_id' => $po->vendor_id, 'vendor_invoice_number' => 'INV-INVALID',
        'invoice_date' => '2026-09-24', 'due_date' => '2026-10-01', 'currency_code' => 'PKR',
        'lines' => [['purchase_order_line_id' => $line->id, 'item_id' => $line->item_id, 'brand_id' => $other->id, 'quantity' => 2, 'unit_price' => 10]],
    ])->assertSessionHasErrors($wrongOrder ? 'lines.0.purchase_order_line_id' : 'lines.0.brand_id');

    $this->assertDatabaseCount('supplier_invoices', 0);
})->with([false, true]);

it('saves updates and clears purchase order origins while retaining the selected brand', function () {
    $brand = Brand::factory()->create();
    $origin = Origin::create(['name' => 'Pakistan', 'status' => 'active']);
    $item = DB::table('items')->insertGetId(['code' => 'ORIGIN', 'name' => 'Origin item']);
    $vendor = DB::table('vendors')->insertGetId(['code' => 'ORIGIN', 'name' => 'Vendor', 'status' => 'active']);
    app(SequenceGenerator::class)->register('purchase_order', 'PO');
    $payload = [
        'vendor_id' => $vendor, 'order_date' => '2026-09-24', 'currency_code' => 'PKR',
        'lines' => [['item_id' => $item, 'brand_id' => $brand->id, 'origin_id' => $origin->id, 'quantity' => 2, 'unit_price' => 10]],
    ];

    $this->actingAs(brandUser(['purchase_order.create']))->post(route('procurement.purchase-orders.store'), $payload)
        ->assertSessionHasNoErrors()->assertRedirect();
    $order = PurchaseOrder::firstOrFail();
    $this->assertDatabaseHas('purchase_order_lines', ['purchase_order_id' => $order->id, 'brand_id' => $brand->id, 'origin_id' => $origin->id]);

    $other = Origin::create(['name' => 'China', 'status' => 'active']);
    $payload['lines'][0]['origin_id'] = $other->id;
    $this->put(route('procurement.purchase-orders.update', $order), $payload)->assertSessionHasNoErrors();
    $this->assertDatabaseHas('purchase_order_lines', ['purchase_order_id' => $order->id, 'brand_id' => $brand->id, 'origin_id' => $other->id]);

    $origin->delete();
    $payload['lines'][0]['origin_id'] = $origin->id;
    $this->put(route('procurement.purchase-orders.update', $order), $payload)->assertSessionHasErrors('lines.0.origin_id');
    $this->assertDatabaseHas('purchase_order_lines', ['purchase_order_id' => $order->id, 'origin_id' => $other->id]);

    $payload['lines'][0]['origin_id'] = null;
    $this->put(route('procurement.purchase-orders.update', $order), $payload)->assertSessionHasNoErrors();
    $this->assertDatabaseHas('purchase_order_lines', ['purchase_order_id' => $order->id, 'brand_id' => $brand->id, 'origin_id' => null]);
});

it('calculates and saves purchase order withholding on creation and editing', function () {
    $item = DB::table('items')->insertGetId(['code' => 'WHT', 'name' => 'WHT item']);
    $vendor = DB::table('vendors')->insertGetId(['code' => 'WHT', 'name' => 'Vendor', 'status' => 'active']);
    app(SequenceGenerator::class)->register('purchase_order', 'PO');
    $payload = [
        'vendor_id' => $vendor, 'order_date' => '2026-09-24', 'currency_code' => 'PKR',
        'lines' => [['item_id' => $item, 'quantity' => 2, 'unit_price' => '125.50', 'tax_rate' => 18, 'wht_tax_rate' => '5.5', 'wht_line_tax' => 999]],
    ];

    $this->actingAs(brandUser(['purchase_order.create', 'purchase.view']))->post(route('procurement.purchase-orders.store'), $payload)
        ->assertSessionHasNoErrors()->assertRedirect();
    $order = PurchaseOrder::firstOrFail();
    $this->assertDatabaseHas('purchase_order_lines', [
        'purchase_order_id' => $order->id, 'wht_tax_rate' => 5.5, 'wht_line_tax' => 13.805, 'line_tax' => 45.18,
    ]);

    $this->get(route('procurement.purchase-orders.show', $order))->assertOk()->assertSee('WHT %')->assertSee('5.5%')->assertSee('13.8050');

    $payload['lines'][0]['wht_tax_rate'] = 10;
    $this->put(route('procurement.purchase-orders.update', $order), $payload)->assertSessionHasNoErrors();
    $this->assertDatabaseHas('purchase_order_lines', ['purchase_order_id' => $order->id, 'wht_tax_rate' => 10, 'wht_line_tax' => 25.1]);

    $payload['lines'][0]['wht_tax_rate'] = 101;
    $this->put(route('procurement.purchase-orders.update', $order), $payload)->assertSessionHasErrors('lines.0.wht_tax_rate');
    $this->assertDatabaseHas('purchase_order_lines', ['purchase_order_id' => $order->id, 'wht_tax_rate' => 10, 'wht_line_tax' => 25.1]);

    $payload['lines'][0]['wht_tax_rate'] = null;
    $this->put(route('procurement.purchase-orders.update', $order), $payload)->assertSessionHasNoErrors();
    $this->assertDatabaseHas('purchase_order_lines', ['purchase_order_id' => $order->id, 'wht_tax_rate' => 0, 'wht_line_tax' => 0]);
});

it('rejects conflicting origins when receiving a purchase order', function () {
    $po = brandOrder(Brand::factory()->create());
    $origin = Origin::create(['name' => 'Pakistan', 'status' => 'active']);
    $other = Origin::create(['name' => 'China', 'status' => 'active']);
    $line = $po->lines->first();
    $line->update(['origin_id' => $origin->id]);
    app(SequenceGenerator::class)->register('goods_receipt', 'GRN');

    $this->actingAs(brandUser(['goods_receipt.create']))->post(route('procurement.goods-receipts.store'), [
        'purchase_order_id' => $po->id, 'received_date' => '2026-09-24',
        'lines' => [['purchase_order_line_id' => $line->id, 'item_id' => $line->item_id, 'origin_id' => $other->id, 'received_quantity' => 2, 'accepted_quantity' => 2, 'batch_number' => 'B-1']],
    ])->assertSessionHasErrors('lines.0.origin_id');

    $this->assertDatabaseCount('goods_receipts', 0);
});

it('saves optional manufacturing dates and rejects dates after receipt', function () {
    $po = brandOrder(Brand::factory()->create());
    $line = $po->lines->first();
    app(SequenceGenerator::class)->register('goods_receipt', 'GRN');
    $payload = [
        'purchase_order_id' => $po->id, 'received_date' => '2026-09-24',
        'lines' => [['purchase_order_line_id' => $line->id, 'item_id' => $line->item_id, 'received_quantity' => 2, 'accepted_quantity' => 2, 'batch_number' => 'B-1', 'manufacturing_date' => '2026-09-25']],
    ];

    $this->actingAs(brandUser(['goods_receipt.create']))->post(route('procurement.goods-receipts.store'), $payload)
        ->assertSessionHasErrors('lines.0.manufacturing_date');
    $this->assertDatabaseCount('goods_receipts', 0);

    $payload['lines'][0]['manufacturing_date'] = '2026-09-01';
    $this->post(route('procurement.goods-receipts.store'), $payload)->assertSessionHasNoErrors();
    expect(GoodsReceiptLine::where('purchase_order_line_id', $line->id)->firstOrFail()->manufacturing_date->toDateString())->toBe('2026-09-01');
});

it('copies manufacturing dates to posted stock batches and preserves them when omitted', function () {
    foreach ([
        '2026_09_18_164854_create_stock_batches_table.php',
        '2026_09_23_105310_add_expiry_date_to_stock_batches_table.php',
        '2026_09_26_065453_add_manufacturing_date_to_stock_batches.php',
    ] as $migration) {
        (require base_path('Modules/Inventory/database/migrations/'.$migration))->up();
    }
    $this->mock(StockLedger::class)->shouldReceive('recordMany')->twice();
    Event::fake([GoodsReceived::class]);
    $po = brandOrder(Brand::factory()->create());
    $line = $po->lines->first();
    $user = brandUser(['goods_receipt.create']);
    app(SequenceGenerator::class)->register('goods_receipt', 'GRN');

    foreach (['2026-09-01', null] as $manufactured) {
        $this->actingAs($user)->post(route('procurement.goods-receipts.store'), [
            'purchase_order_id' => $po->id, 'received_date' => '2026-09-24',
            'lines' => [['purchase_order_line_id' => $line->id, 'item_id' => $line->item_id, 'received_quantity' => 2, 'accepted_quantity' => 2, 'batch_number' => 'B-MFG', 'manufacturing_date' => $manufactured]],
        ])->assertSessionHasNoErrors();
        $receipt = GoodsReceipt::latest('id')->firstOrFail();
        app(GoodsReceiptManager::class)->post($receipt, $user->id);

        expect(StockBatch::where('number', 'B-MFG')->firstOrFail()->manufacturing_date->toDateString())->toBe('2026-09-01');
    }

    $this->assertDatabaseCount('stock_batches', 1);
});
