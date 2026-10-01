<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\AccountManager;
use App\Contracts\LedgerService;
use App\Data\LedgerQuery;
use App\Models\Account;
use App\Models\CostCenter;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GeneralLedgerController extends Controller
{
    public function __construct(
        private readonly LedgerService $ledger,
        private readonly AccountManager $accounts,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('reports.financial');

        $query = new LedgerQuery(
            accountId: $request->integer('account_id') ?: null,
            customerId: $request->integer('customer_id') ?: null,
            vendorId: $request->integer('vendor_id') ?: null,
            costCenterId: $request->integer('cost_center_id') ?: null,
            departmentId: $request->integer('department_id') ?: null,
            from: $request->date('from'),
            to: $request->date('to'),
            search: $request->string('search')->toString() ?: null,
        );

        $lines = $query->accountId
            ? $this->ledger->lines($query)
            : collect();

        return view('gl.index', [
            'query' => $query,
            'lines' => $lines,
            'balance' => $query->accountId ? $this->ledger->balance($query) : null,
            'accounts' => $this->accounts->allPostable(),
            'costCenters' => CostCenter::query()->active()->orderBy('code')->get(),
            'departments' => Department::query()->active()->orderBy('name')->get(),
            'customers' => Customer::query()->active()->orderBy('name')->get(['id', 'code', 'name']),
            'vendors' => Vendor::query()->active()->orderBy('name')->get(['id', 'code', 'name']),
        ]);
    }

    public function csv(Request $request): StreamedResponse
    {
        $this->authorize('reports.financial');
        $query = $this->ledgerQuery($request);
        abort_unless($query->accountId, 422, 'Select an account before exporting.');
        $lines = $this->ledger->lines($query);

        return response()->streamDownload(function () use ($lines): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Date', 'Entry', 'Description', 'Memo', 'Debit', 'Credit', 'Running Balance']);
            foreach ($lines as $line) {
                fputcsv($output, [$line->entry_date, $line->number, $line->description, $line->line_memo, $line->debit, $line->credit, $line->running_balance]);
            }
            fclose($output);
        }, 'general-ledger.csv', ['Content-Type' => 'text/csv']);
    }

    public function print(Request $request): View
    {
        $this->authorize('reports.financial');
        $query = $this->ledgerQuery($request);
        abort_unless($query->accountId, 422, 'Select an account before printing.');

        return view('gl.print', ['query' => $query, 'account' => Account::query()->findOrFail($query->accountId), 'lines' => $this->ledger->lines($query), 'balance' => $this->ledger->balance($query)]);
    }

    private function ledgerQuery(Request $request): LedgerQuery
    {
        return new LedgerQuery(accountId: $request->integer('account_id') ?: null, customerId: $request->integer('customer_id') ?: null, vendorId: $request->integer('vendor_id') ?: null, costCenterId: $request->integer('cost_center_id') ?: null, departmentId: $request->integer('department_id') ?: null, from: $request->date('from'), to: $request->date('to'), search: $request->string('search')->toString() ?: null);
    }
}
