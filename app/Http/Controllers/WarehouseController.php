<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\DepartmentManager;
use App\Contracts\WarehouseManager;
use App\Enums\RecordStatus;
use App\Enums\UserStatus;
use App\Enums\WarehouseType;
use App\Http\Requests\Warehouses\StoreWarehouseRequest;
use App\Http\Requests\Warehouses\UpdateWarehouseRequest;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class WarehouseController extends Controller
{
    public function __construct(
        private readonly WarehouseManager $warehouses,
        private readonly DepartmentManager $departments,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Warehouse::class);

        return view('warehouses.index', [
            'warehouses' => $this->warehouses->paginate(
                $request->only(['search', 'status', 'type'])
            ),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Warehouse::class);

        return view('warehouses.create', [
            'warehouse' => new Warehouse([
                'status' => RecordStatus::Active,
                'type' => WarehouseType::Main,
            ]),
            'departments' => $this->departments->allActive(),
            'managers' => $this->managerCandidates(),
        ]);
    }

    public function store(StoreWarehouseRequest $request): RedirectResponse
    {
        $warehouse = $this->warehouses->create($request->validated());

        return redirect()
            ->route('warehouses.show', $warehouse)
            ->with('status', "Warehouse {$warehouse->name} created.");
    }

    public function show(Warehouse $warehouse): View
    {
        $this->authorize('view', $warehouse);

        return view('warehouses.show', [
            'warehouse' => $warehouse->load([
                'addresses', 'manager', 'department', 'costCenter', 'creator', 'updater',
            ]),
        ]);
    }

    public function edit(Warehouse $warehouse): View
    {
        $this->authorize('update', $warehouse);

        return view('warehouses.edit', [
            'warehouse' => $warehouse->load('addresses'),
            'departments' => $this->departments->allActive(),
            'managers' => $this->managerCandidates(),
        ]);
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse): RedirectResponse
    {
        $this->warehouses->update($warehouse, $request->validated());

        return redirect()
            ->route('warehouses.show', $warehouse)
            ->with('status', 'Warehouse updated.');
    }

    public function destroy(Warehouse $warehouse): RedirectResponse
    {
        $this->authorize('delete', $warehouse);

        $this->warehouses->delete($warehouse);

        return redirect()
            ->route('warehouses.index')
            ->with('status', 'Warehouse deleted.');
    }

    public function changeStatus(Request $request, Warehouse $warehouse): RedirectResponse
    {
        $this->authorize('changeStatus', $warehouse);

        $request->validate(['status' => ['required', 'string']]);

        $this->warehouses->changeStatus(
            $warehouse,
            RecordStatus::from((string) $request->input('status')),
        );

        return back()->with('status', 'Warehouse status updated.');
    }

    public function makeDefault(Warehouse $warehouse): RedirectResponse
    {
        $this->authorize('makeDefault', $warehouse);

        $this->warehouses->makeDefault($warehouse);

        return back()->with('status', "{$warehouse->name} is now the default warehouse.");
    }

    /** @return Collection<int, User> */
    private function managerCandidates(): Collection
    {
        return User::query()
            ->where('status', UserStatus::Active->value)
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
