<?php

namespace App\Http\Controllers;

use App\Contracts\AccountingPeriodManager;
use App\Contracts\FinancialYearManager;
use App\Http\Requests\AccountingPeriods\GeneratePeriodsRequest;
use App\Models\AccountingPeriod;
use App\Models\FinancialYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountingPeriodController extends Controller
{
    public function __construct(
        private readonly AccountingPeriodManager $periods,
        private readonly FinancialYearManager $years,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', AccountingPeriod::class);

        $yearId = $request->integer('financial_year_id')
            ?: $this->years->current()?->id;

        $year = $yearId
            ? FinancialYear::query()->find($yearId)
            : null;

        return view('accounting-periods.accounting-periods.index', [
            'years' => $this->years->all(),
            'year' => $year,
            'periods' => $year ? $this->periods->forYear($year) : collect(),
        ]);
    }

    public function show(AccountingPeriod $accountingPeriod): View
    {
        $this->authorize('view', $accountingPeriod);

        return view('accounting-periods.accounting-periods.show', [
            'period' => $accountingPeriod->load(['financialYear', 'closer']),
        ]);
    }

    public function generate(GeneratePeriodsRequest $request, FinancialYear $financialYear): RedirectResponse
    {
        $this->authorize('viewAny', AccountingPeriod::class);

        $created = $this->periods->generateForYear(
            $financialYear,
            $request->integer('months'),
        );

        return redirect()
            ->route('accounting-periods.index', ['financial_year_id' => $financialYear->id])
            ->with('status', "{$created->count()} period(s) generated.");
    }

    public function close(Request $request, AccountingPeriod $accountingPeriod): RedirectResponse
    {
        $this->authorize('close', $accountingPeriod);

        $request->validate(['notes' => ['nullable', 'string', 'max:500']]);

        $this->periods->close($accountingPeriod, $request->input('notes'));

        return back()->with('status', "Period {$accountingPeriod->name} closed.");
    }

    public function reopen(Request $request, AccountingPeriod $accountingPeriod): RedirectResponse
    {
        $this->authorize('reopen', $accountingPeriod);

        $request->validate(['notes' => ['nullable', 'string', 'max:500']]);

        $this->periods->reopen($accountingPeriod, $request->input('notes'));

        return back()->with('status', "Period {$accountingPeriod->name} reopened for adjustments.");
    }
}
