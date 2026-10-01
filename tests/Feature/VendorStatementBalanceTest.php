<?php

namespace Tests\Feature;

use App\Contracts\PartyLedgerService;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Procurement\Models\SupplierInvoice;
use Modules\Procurement\Models\VendorInvoice;
use Modules\Procurement\Models\VendorPayment;
use Tests\TestCase;

class VendorStatementBalanceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::disableForeignKeyConstraints();
        foreach ([
            '2026_01_08_000001_create_accounts_table.php',
            '2026_09_14_173239_create_journal_entries_table.php',
            '2026_09_14_173246_create_journal_lines_table.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        DB::table('accounts')->insert(['id' => 1, 'code' => 'AP', 'name' => 'Accounts payable', 'type' => 'liability', 'normal_balance' => 'credit']);
    }

    public function test_statement_carries_opening_balance_and_excludes_other_vendors_unposted_and_later_entries(): void
    {
        $vendor = new Vendor;
        $vendor->id = 1;
        $this->entry('2026-08-31', '0', '100.1250');
        $this->entry('2026-09-01', '0', '50.2500');
        $this->entry('2026-09-30', '20.1250', '0');
        $this->entry('2026-10-01', '0', '900');
        $this->entry('2026-09-15', '0', '800', 'draft');
        $this->entry('2026-09-15', '0', '700', 'posted', 2);

        $statement = app(PartyLedgerService::class)->vendorStatement($vendor, Carbon::parse('2026-09-01'), Carbon::parse('2026-09-30'));

        $this->assertSame('100.1250', $statement['opening_balance']);
        $this->assertSame('50.2500', $statement['total_credits']);
        $this->assertSame('20.1250', $statement['total_debits']);
        $this->assertSame('130.2500', $statement['closing_balance']);
        $this->assertCount(2, $statement['lines']);
        $this->assertSame(['150.3750', '130.2500'], $statement['lines']->pluck('running_balance')->all());
    }

    public function test_period_without_transactions_carries_forward_a_vendor_advance(): void
    {
        $vendor = new Vendor;
        $vendor->id = 1;
        $this->entry('2026-08-31', '40.5000', '0');

        $statement = app(PartyLedgerService::class)->vendorStatement($vendor, Carbon::parse('2026-09-01'), Carbon::parse('2026-09-30'));

        $this->assertSame('-40.5000', $statement['opening_balance']);
        $this->assertSame('-40.5000', $statement['closing_balance']);
        $this->assertSame('0.0000', $statement['total_debits']);
        $this->assertSame('0.0000', $statement['total_credits']);
        $this->assertCount(0, $statement['lines']);
    }

    public function test_statement_separates_both_invoice_types_wht_payments_and_signed_adjustments(): void
    {
        foreach ([
            '2026_09_21_112300_create_vendor_invoices_table.php',
            '2026_09_15_155656_create_supplier_invoices_table.php',
            '2026_09_15_155706_create_supplier_invoice_lines_table.php',
            '2026_09_22_105629_add_withholding_tax_to_supplier_invoices.php',
        ] as $migration) {
            (require base_path('Modules/Procurement/database/migrations/'.$migration))->up();
            if ($migration === '2026_09_21_112300_create_vendor_invoices_table.php') {
                Schema::table('vendor_invoices', function (Blueprint $table): void {
                    $table->renameIndex('si_vendor_ref_unique', 'vi_vendor_ref_unique');
                });
            }
        }
        $vendor = new Vendor;
        $vendor->id = 1;
        $this->entry('2026-08-31', '0', '100');
        $vendorJournal = $this->entry('2026-09-01', '0', '136000');
        DB::table('journal_lines')->insert(['journal_entry_id' => $vendorJournal, 'account_id' => 1, 'vendor_id' => 1, 'debit' => 0, 'credit' => 400]);
        DB::table('journal_entries')->where('id', $vendorJournal)->update(['source_type' => VendorInvoice::class]);
        DB::table('vendor_invoices')->insert([
            'number' => 'VI-1', 'vendor_id' => 1, 'vendor_invoice_number' => 'EXT-1',
            'invoice_date' => '2026-09-01', 'due_date' => '2026-09-30', 'billing_month' => '2026-09',
            'whttax_total' => '7440', 'total' => '136400', 'journal_entry_id' => $vendorJournal, 'status' => 'paid',
        ]);
        $supplierJournal = $this->entry('2026-09-02', '0', '90');
        DB::table('journal_entries')->where('id', $supplierJournal)->update(['source_type' => SupplierInvoice::class]);
        DB::table('supplier_invoices')->insert([
            'number' => 'SI-1', 'vendor_id' => 1, 'vendor_invoice_number' => 'EXT-2', 'purchase_order_id' => 1,
            'invoice_date' => '2026-09-02', 'due_date' => '2026-09-30', 'currency_code' => 'PKR',
            'wht_tax_total' => '10', 'total' => '90', 'journal_entry_id' => $supplierJournal, 'status' => 'posted',
        ]);
        $paymentJournal = $this->entry('2026-09-03', '1000', '0');
        DB::table('journal_entries')->where('id', $paymentJournal)->update(['source_type' => VendorPayment::class]);
        $this->entry('2026-09-04', '5', '0');
        $this->entry('2026-09-05', '0', '7');

        $statement = app(PartyLedgerService::class)->vendorStatement($vendor, Carbon::parse('2026-09-01'), Carbon::parse('2026-09-30'));

        $this->assertSame('143940.0000', $statement['total_invoices']);
        $this->assertSame('7450.0000', $statement['total_wht']);
        $this->assertSame('1000.0000', $statement['total_payments']);
        $this->assertSame('2.0000', $statement['total_adjustments']);
        $this->assertSame('135592.0000', $statement['closing_balance']);
        $this->assertCount(5, $statement['lines']);
        $invoice = $statement['lines'][0];
        $this->assertSame('VI-1', $invoice->number);
        $this->assertSame('EXT-1', $invoice->reference);
        $this->assertSame('143840.0000', $invoice->invoice_amount);
        $this->assertSame('7440.0000', $invoice->wht);
        $this->assertSame('-5.0000', $statement['lines'][3]->adjustment);
        foreach ($statement['lines'] as $line) {
            $change = bcadd(bcsub(bcsub($line->invoice_amount, $line->payment, 4), $line->wht, 4), $line->adjustment, 4);
            $this->assertSame(bcsub($line->credit, $line->debit, 4), $change);
        }
    }

    private function entry(string $date, string $debit, string $credit, string $status = 'posted', int $vendorId = 1): int
    {
        $id = DB::table('journal_entries')->insertGetId([
            'number' => 'JV-'.(DB::table('journal_entries')->count() + 1),
            'entry_date' => $date,
            'description' => 'Vendor transaction',
            'currency_code' => 'PKR',
            'status' => $status,
        ]);
        DB::table('journal_lines')->insert([
            'journal_entry_id' => $id, 'account_id' => 1, 'vendor_id' => $vendorId,
            'debit' => $debit, 'credit' => $credit,
        ]);

        return $id;
    }
}
