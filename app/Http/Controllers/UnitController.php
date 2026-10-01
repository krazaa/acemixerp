<?php

namespace App\Http\Controllers;

use App\Contracts\UnitManager;
use App\Enums\RecordStatus;
use App\Http\Requests\Units\StoreUnitRequest;
use App\Http\Requests\Units\UpdateUnitRequest;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UnitController extends Controller
{
    public function __construct(private readonly UnitManager $units) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Unit::class);

        return view('units.index', [
            'units' => $this->units->paginate($request->only(['search', 'status'])),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Unit::class);

        return view('units.create', [
            'unit' => new Unit(['status' => RecordStatus::Active, 'quantity_precision' => 2]),
        ]);
    }

    public function store(StoreUnitRequest $request): RedirectResponse
    {
        $unit = $this->units->create($request->validated());

        return redirect()->route('units.index')
            ->with('status', "Unit {$unit->name} created.");
    }

    public function edit(Unit $unit): View
    {
        $this->authorize('update', $unit);

        return view('units.edit', ['unit' => $unit]);
    }

    public function update(UpdateUnitRequest $request, Unit $unit): RedirectResponse
    {
        $this->units->update($unit, $request->validated());

        return redirect()->route('units.index')->with('status', 'Unit updated.');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        $this->authorize('delete', $unit);
        $this->units->delete($unit);

        return redirect()->route('units.index')->with('status', 'Unit deleted.');
    }

    public function changeStatus(Request $request, Unit $unit): RedirectResponse
    {
        $this->authorize('changeStatus', $unit);
        $request->validate(['status' => ['required', 'string']]);

        $this->units->changeStatus($unit, RecordStatus::from((string) $request->input('status')));

        return back()->with('status', 'Unit status updated.');
    }
}
