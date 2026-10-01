<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\Customer;
use App\Models\DocumentSequence;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Hr\Models\Employee;
use Modules\Inventory\Models\Brand;
use Modules\Inventory\Models\Origin;
use Modules\Sales\Models\SalesOrder;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SalesOrderBrandOriginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        config(['activitylog.enabled' => false]);
        Schema::disableForeignKeyConstraints();
        foreach ([
            '0001_01_01_000000_create_users_table.php',
            '2026_09_11_075003_2026_01_01_000002_add_erp_fields_to_users_table.php',
            '2026_09_28_163338_add_soft_deletes_to_users_table.php',
            '2026_09_11_062704_create_permission_tables.php',
            '2026_09_11_075115_2026_01_01_000003_create_document_sequences_table.php',
            '2026_01_03_000002_create_customers_table.php',
            '2026_01_04_000002_create_units_table.php',
            '2026_01_04_000003_create_items_table.php',
            '2026_09_11_112209_create_departments_table.php',
            '2026_01_05_000001_create_warehouses_table.php',
            '2026_09_14_053002_create_payment_terms_table.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        // Inventory's legacy lookup migrations also alter unrelated procurement tables.
        foreach (['brands', 'origins'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('code')->nullable();
                $table->string('description')->nullable();
                $table->string('status')->default('active');
                $table->timestamps();
                $table->softDeletes();
            });
        }
        (require base_path('Modules/Hr/database/migrations/2026_09_18_183133_create_employees_table.php'))->up();
        foreach ([
            '2026_09_16_050827_create_sales_orders_table.php',
            '2026_09_16_050835_create_sales_order_lines_table.php',
            '2026_09_16_111850_create_delivery_lines_table.php',
            '2026_09_30_105156_add_brand_and_origin_to_sales_order_lines_table.php',
        ] as $migration) {
            (require base_path('Modules/Sales/database/migrations/'.$migration))->up();
        }
    }

    public function test_create_and_edit_keep_brand_and_origin_on_each_line(): void
    {
        $user = $this->salesUser();
        $brand = Brand::factory()->create(['name' => 'Selected Brand', 'status' => 'active']);
        $origin = Origin::create(['name' => 'Selected Origin', 'status' => 'active']);
        $data = $this->payload($brand, $origin);
        DocumentSequence::create(['key' => 'sales_order', 'prefix' => 'SO', 'pattern' => '{prefix}-{year}-{number}', 'padding' => 6, 'reset_yearly' => true, 'current_value' => 0]);

        $this->actingAs($user)->post(route('sales.sales-orders.store'), $data)->assertRedirect()->assertSessionHasNoErrors();
        $order = SalesOrder::firstOrFail();
        $line = $order->lines()->firstOrFail();
        $this->assertSame($brand->id, $line->brand_id);
        $this->assertSame($origin->id, $line->origin_id);
        $response = $this->get(route('sales.sales-orders.edit', $order));
        $response->assertOk()->assertSee('Selected Brand')->assertSee('Selected Origin')
            ->assertSee('name="lines[0][brand_id]"', false)->assertSee('name="lines[0][origin_id]"', false)
            ->assertSee('name="lines[__INDEX__][brand_id]"', false);
        $html = new \DOMDocument;
        @$html->loadHTML($response->getContent());
        $xpath = new \DOMXPath($html);
        $this->assertSame((string) $brand->id, $xpath->evaluate('string(//select[@name="lines[0][brand_id]"]/option[@selected]/@value)'));
        $this->assertSame((string) $origin->id, $xpath->evaluate('string(//select[@name="lines[0][origin_id]"]/option[@selected]/@value)'));

        $newBrand = Brand::factory()->create(['status' => 'active']);
        $newOrigin = Origin::create(['name' => 'Updated Origin', 'status' => 'active']);
        $data['lines'][0] += ['sales_order_line_id' => $line->id];
        $data['lines'][0]['brand_id'] = $newBrand->id;
        $data['lines'][0]['origin_id'] = $newOrigin->id;
        $this->put(route('sales.sales-orders.update', $order), $data)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('sales_order_lines', ['id' => $line->id, 'brand_id' => $newBrand->id, 'origin_id' => $newOrigin->id]);
        $this->get(route('sales.sales-orders.show', $order))->assertOk()->assertSee($newBrand->name)->assertSee('Updated Origin');
    }

    public function test_optional_brand_and_origin_can_be_cleared_without_replacing_the_line(): void
    {
        $user = $this->salesUser();
        $brand = Brand::factory()->create();
        $origin = Origin::create(['name' => 'Origin', 'status' => 'active']);
        $data = $this->payload($brand, $origin);
        $order = $this->order($user, $data);
        $line = $order->lines()->firstOrFail();
        $data['lines'][0]['sales_order_line_id'] = $line->id;
        $data['lines'][0]['brand_id'] = '';
        $data['lines'][0]['origin_id'] = '';

        $this->actingAs($user)->put(route('sales.sales-orders.update', $order), $data)->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('sales_order_lines', ['id' => $line->id, 'brand_id' => null, 'origin_id' => null]);
        $this->assertDatabaseCount('sales_order_lines', 1);
    }

    public function test_nonexistent_and_deleted_lookups_are_rejected(): void
    {
        $user = $this->salesUser();
        $brand = Brand::factory()->create();
        $origin = Origin::create(['name' => 'Origin', 'status' => 'active']);
        $data = $this->payload($brand, $origin);
        $order = $this->order($user, $data);
        $data['lines'][0]['sales_order_line_id'] = $order->lines()->first()->id;
        $brand->delete();
        $origin->delete();
        $this->actingAs($user)->post(route('sales.sales-orders.store'), $data)->assertSessionHasErrors(['lines.0.brand_id', 'lines.0.origin_id']);
        $data['lines'][0]['brand_id'] = 99999;
        $data['lines'][0]['origin_id'] = 99999;
        $this->put(route('sales.sales-orders.update', $order), $data)->assertSessionHasErrors(['lines.0.brand_id', 'lines.0.origin_id']);
        $this->assertDatabaseHas('sales_order_lines', ['sales_order_id' => $order->id, 'brand_id' => $brand->id, 'origin_id' => $origin->id]);
    }

    public function test_unpermitted_users_cannot_update_brand_and_origin(): void
    {
        $creator = $this->salesUser();
        $data = $this->payload(Brand::factory()->create(), Origin::create(['name' => 'Origin', 'status' => 'active']));
        $order = $this->order($creator, $data);
        $this->actingAs(User::factory()->create(['status' => UserStatus::Active]))
            ->put(route('sales.sales-orders.update', $order), $data)->assertForbidden();
    }

    public function test_orders_with_an_employee_salesperson_load_in_the_list_and_details(): void
    {
        $user = $this->salesUser();
        $employee = Employee::create([
            'number' => 'EMP-SALES', 'first_name' => 'Sales', 'last_name' => 'Representative',
            'department_id' => 1, 'designation_id' => 1, 'joining_date' => '2026-09-01', 'status' => 'active',
        ]);
        $data = $this->payload(Brand::factory()->create(), Origin::create(['name' => 'Origin', 'status' => 'active']));
        $order = $this->order($user, $data);
        $order->update(['salesperson_id' => $employee->id]);

        $this->actingAs($user)->get(route('sales.sales-orders.index'))->assertOk()
            ->assertSee('SO-TEST')
            ->assertViewHas('orders', fn ($orders) => $orders->first()->salesperson->fullName() === 'Sales Representative');
        $this->get(route('sales.sales-orders.show', $order))->assertOk()->assertSee('Sales Representative');
    }

    private function salesUser(): User
    {
        $user = User::factory()->create(['status' => UserStatus::Active]);
        foreach (['sales.view', 'sales_order.create'] as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        return $user;
    }

    private function payload(Brand $brand, Origin $origin): array
    {
        $customer = Customer::create(['code' => 'CUSTOMER', 'name' => 'Customer', 'status' => 'active']);
        $item = Item::create(['code' => 'ITEM', 'name' => 'Item', 'status' => 'active', 'is_sellable' => true]);

        return [
            'customer_id' => $customer->id, 'order_date' => '2026-09-30', 'currency_code' => 'PKR',
            'lines' => [['item_id' => $item->id, 'brand_id' => $brand->id, 'origin_id' => $origin->id, 'quantity' => '2', 'unit_price' => '50']],
        ];
    }

    private function order(User $user, array $data): SalesOrder
    {
        $order = SalesOrder::create(['number' => 'SO-TEST', 'customer_id' => $data['customer_id'], 'order_date' => $data['order_date'], 'currency_code' => 'PKR', 'status' => 'draft', 'created_by' => $user->id]);
        $order->lines()->create($data['lines'][0]);

        return $order;
    }
}
