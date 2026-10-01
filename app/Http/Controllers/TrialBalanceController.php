<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\LedgerService;
use App\Data\LedgerQuery;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrialBalanceController extends Controller
{
    public function __construct(private readonly LedgerService $ledger) {}

    public function __invoke(Request $request): View
    {
        $this->authorize('reports.financial');

        $query = new LedgerQuery(
            from: $request->date('from') ?? now()->startOfYear(),
            to: $request->date('to') ?? now(),
        );

        $rows = $this->ledger->accountBalances($query);

        $totalDebit = '0.0000';
        $totalCredit = '0.0000';
        foreach ($rows as $r) {
            $totalDebit = bcadd($totalDebit, (string) $r->debit_total, 4);
            $totalCredit = bcadd($totalCredit, (string) $r->credit_total, 4);
        }

        $balanced = bccomp($totalDebit, $totalCredit, 4) === 0;

        return view('gl.trial-balance', [
            'rows' => $rows,
            'query' => $query,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'balanced' => $balanced,
        ]);
    }
}
