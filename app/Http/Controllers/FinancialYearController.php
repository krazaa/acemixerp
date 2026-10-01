<?php

namespace App\Http\Controllers;

use App\Contracts\FinancialYearManager;
use App\Enums\FinancialYearStatus;
use App\Http\Requests\FinancialYears\StoreFinancialYearRequest;
use App\Models\FinancialYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinancialYearController extends Controller
{
    public function __construct(
        private readonly FinancialYearManager $years,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', FinancialYear::class);

        return view('financial-years.index', [
            'years' => $this->years->all(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', FinancialYear::class);

        return view('financial-years.create', [
            'year' => new FinancialYear([
                'start_date' => now()->startOfYear()->toDateString(),
                'end_date' => now()->endOfYear()->toDateString(),
                'period_count' => 12,
            ]),
        ]);
    }

    public function store(StoreFinancialYearRequest $request): RedirectResponse
    {
        $year = $this->years->create($request->validated());

        return redirect()->route('financial-years.index')
            ->with('status', "Financial year {$year->name} created with {$year->periods()->count()} periods.");
    }

    public function show(FinancialYear $financialYear): View
    {
        $this->authorize('view', $financialYear);

        return view('financial-years.show', [
            'year' => $financialYear->load(['periods', 'closer']),
            'periods' => $financialYear->periods()->orderBy('start_date')->get(),
        ]);
    }

    public function edit(FinancialYear $financialYear): View
    {
        $this->authorize('update', $financialYear);

        return view('financial-years.edit', [
            'year' => $financialYear,
        ]);
    }

    public function update(Request $request, FinancialYear $financialYear): RedirectResponse
    {
        $this->authorize('update', $financialYear);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:32', 'unique:financial_years,name,'.$financialYear->id],
        ]);

        if ($financialYear->status !== FinancialYearStatus::Open) {
            return back()->withErrors(['name' => 'Only open financial years can be renamed.']);
        }

        $financialYear->update($validated);

        return redirect()->route('financial-years.index')
            ->with('status', 'Financial year updated.');
    }

    public function markCurrent(FinancialYear $financialYear): RedirectResponse
    {
        $this->authorize('markCurrent', $financialYear);

        $this->years->markCurrent($financialYear);

        return back()->with('status', "{$financialYear->name} is now the current financial year.");
    }

    public function beginClosing(FinancialYear $financialYear): RedirectResponse
    {
        $this->authorize('close', $financialYear);

        $this->years->beginClosing($financialYear);

        return back()->with('status', 'Financial year moved to Closing. All periods must be closed before finalizing.');
    }

    public function close(Request $request, FinancialYear $financialYear): RedirectResponse
    {
        $this->authorize('close', $financialYear);

        $request->validate(['notes' => ['nullable', 'string', 'max:1000']]);

        $this->years->close($financialYear, $request->input('notes'));

        return back()->with('status', "Financial year {$financialYear->name} closed.");
    }
}
