<?php

namespace App\Http\Controllers;

use App\Contracts\TaxRateManager;
use App\Enums\RecordStatus;
use App\Enums\TaxRateComponent;
use App\Http\Requests\TaxRates\StoreTaxRateRequest;
use App\Http\Requests\TaxRates\UpdateTaxRateRequest;
use App\Models\TaxRate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaxRateController extends Controller
{
    public function __construct(
        private readonly TaxRateManager $taxRates,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', TaxRate::class);

        return view('tax-rates.index', [
            'taxRates' => $this->taxRates->paginate(
                $request->only(['search', 'status', 'component'])
            ),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', TaxRate::class);

        return view('tax-rates.create', [
            'taxRate' => new TaxRate([
                'type' => 'standard',
                'component' => TaxRateComponent::Output,
                'effective_from' => now()->toDateString(),
                'is_recoverable' => true,
                'status' => RecordStatus::Active,
            ]),
        ]);
    }

    public function store(StoreTaxRateRequest $request): RedirectResponse
    {
        $taxRate = $this->taxRates->create($request->validated());

        return redirect()
            ->route('tax-rates.index')
            ->with('status', "Tax rate {$taxRate->name} created.");
    }

    public function edit(TaxRate $taxRate): View
    {
        $this->authorize('update', $taxRate);

        return view('tax-rates.edit', [
            'taxRate' => $taxRate,
        ]);
    }

    public function update(UpdateTaxRateRequest $request, TaxRate $taxRate): RedirectResponse
    {
        $this->taxRates->update($taxRate, $request->validated());

        return redirect()
            ->route('tax-rates.index')
            ->with('status', 'Tax rate updated.');
    }

    public function destroy(TaxRate $taxRate): RedirectResponse
    {
        $this->authorize('delete', $taxRate);

        $this->taxRates->delete($taxRate);

        return redirect()
            ->route('tax-rates.index')
            ->with('status', 'Tax rate deleted.');
    }

    public function changeStatus(Request $request, TaxRate $taxRate): RedirectResponse
    {
        $this->authorize('changeStatus', $taxRate);

        $request->validate(['status' => ['required', 'string']]);

        $this->taxRates->changeStatus(
            $taxRate,
            RecordStatus::from((string) $request->input('status')),
        );

        return back()->with('status', 'Tax rate status updated.');
    }

    public function makeDefault(TaxRate $taxRate): RedirectResponse
    {
        $this->authorize('makeDefault', $taxRate);

        $this->taxRates->makeDefault($taxRate);

        return back()->with('status', "{$taxRate->name} is now the default for its component.");
    }
}
