<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\FinancialYearManager;
use App\Http\Requests\FinancialYears\StoreFinancialYearRequest;
use App\Models\FinancialYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinancialYearController extends Controller
{
    public function __construct(private readonly FinancialYearManager $years) {}

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

        return view('financial-years.create');
    }

    public function store(StoreFinancialYearRequest $request): RedirectResponse
    {
        $year = $this->years->create($request->validated());

        return redirect()->route('financial-years.index')
            ->with('status', "Financial year {$year->name} created.");
    }

    public function markCurrent(FinancialYear $financialYear): RedirectResponse
    {
        $this->authorize('markCurrent', $financialYear);

        $this->years->markCurrent($financialYear);

        return back()->with('status', "{$financialYear->name} is now the current year.");
    }

    public function beginClosing(FinancialYear $financialYear): RedirectResponse
    {
        $this->authorize('close', $financialYear);

        $this->years->beginClosing($financialYear);

        return back()->with('status', 'Year marked as closing.');
    }

    public function close(Request $request, FinancialYear $financialYear): RedirectResponse
    {
        $this->authorize('close', $financialYear);

        $request->validate(['notes' => ['nullable', 'string', 'max:1000']]);

        $this->years->close($financialYear, $request->input('notes'));

        return back()->with('status', "Financial year {$financialYear->name} closed.");
    }
}
