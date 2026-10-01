<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class VendorStatementViewTest extends TestCase
{
    public function test_statement_renders_the_table_and_metadata_required_for_export_buttons(): void
    {
        (require database_path('migrations/2026_09_11_062704_create_permission_tables.php'))->up();
        $user = User::factory()->make();
        $user->setRelation('roles', new Collection);
        $user->setRelation('permissions', new Collection);
        $this->actingAs($user);
        $vendor = new Vendor(['name' => 'Acme', 'code' => 'V001']);
        $vendor->id = 1;

        $view = $this->view('ledgers.vendor-statement', [
            'vendor' => $vendor,
            'from' => Carbon::parse('2026-09-01'),
            'to' => Carbon::parse('2026-09-28'),
            'lines' => collect([(object) [
                'entry_date' => '2026-09-22',
                'number' => 'GL-2026-000040',
                'reference' => '234234',
                'description' => 'Vendor Invoice VI-2026-000004',
                'line_memo' => null,
                'invoice_amount' => '143840.0000',
                'payment' => '0.0000',
                'wht' => '7440.0000',
                'adjustment' => '0.0000',
                'outstanding' => '136400.0000',
            ]]),
            'openingBalance' => '0.0000',
            'closingBalance' => '0.0000',
            'totalInvoices' => '143840.0000',
            'totalPayments' => '0.0000',
            'totalWht' => '7440.0000',
            'totalAdjustments' => '0.0000',
            'organization' => null,
            'errors' => new ViewErrorBag,
        ]);

        $view->assertSee('id="vendor-statement-exports"', false)
            ->assertSee('id="vendor-statement-table"', false)
            ->assertSeeInOrder(['Date</th>', 'Reference</th>', 'Description</th>', 'Invoice Amount</th>', 'Payment</th>', 'WHT</th>', 'Adjustment</th>', 'Outstanding</th>'], false)
            ->assertSee('143,840.0000')->assertSee('7,440.0000')
            ->assertSee('234234')
            ->assertSee('GL-2026-000040')
            ->assertSee('Vendor Invoice VI-2026-000004')
            ->assertSee('data-export-title="Vendor Statement — Acme"', false)
            ->assertSee('data-export-period="V001 | 01 Sep 2026 – 28 Sep 2026"', false)
            ->assertSee('data-export-filename="vendor-statement-1-2026-09-01-2026-09-28"', false)
            ->assertSeeInOrder(['datatables.bundle.js', 'vendor-statement-exports.js']);
    }
}
