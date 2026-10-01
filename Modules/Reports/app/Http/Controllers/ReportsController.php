<?php

declare(strict_types=1);

namespace Modules\Reports\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Reports\Http\Requests\FinancialReportRequest;
use Modules\Reports\Services\FinancialReport;

class ReportsController extends Controller
{
    public function __construct(private readonly FinancialReport $reports) {}

    public function index(): View
    {
        $this->authorize('reports.financial');

        return view('reports::index');
    }

    public function payables(FinancialReportRequest $request): View
    {
        return $this->partyReport($request, true);
    }

    public function receivables(FinancialReportRequest $request): View
    {
        return $this->partyReport($request, false);
    }

    public function trialBalance(FinancialReportRequest $request): View
    {
        $filters = $request->validated();
        $rows = $this->reports->trialBalance($filters['as_of'], $filters['currency'] ?? null);

        return view('reports::trial-balance', [
            'title' => 'Trial Balance Report',
            'filters' => $filters,
            'groups' => $this->reports->currencyGroups($rows),
        ]);
    }

    public function balanceSheet(FinancialReportRequest $request): View
    {
        $filters = $request->validated();

        return view('reports::balance-sheet', [
            'filters' => $filters,
            'groups' => $this->reports->balanceSheet($filters['as_of'], $filters['currency'] ?? null),
        ]);
    }

    private function partyReport(FinancialReportRequest $request, bool $payables): View
    {
        $filters = $request->validated();
        $rows = $this->reports->partyBalances(
            $payables,
            $filters['as_of'],
            $filters['currency'] ?? null,
            empty($filters['party_id']) ? null : (int) $filters['party_id'],
        );

        return view('reports::party-balances', [
            'title' => $payables ? 'Accounts Payable Report' : 'Accounts Receivable Report',
            'partyLabel' => $payables ? 'Vendor' : 'Customer',
            'filters' => $filters,
            'parties' => $this->reports->parties($payables),
            'groups' => $this->reports->currencyGroups($rows),
        ]);
    }
}
