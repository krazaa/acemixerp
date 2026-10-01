<?php

namespace App\Http\Controllers;

use App\Contracts\CostCenterManager;
use App\Contracts\DepartmentManager;
use App\Enums\RecordStatus;
use App\Http\Requests\CostCenters\StoreCostCenterRequest;
use App\Http\Requests\CostCenters\UpdateCostCenterRequest;
use App\Models\CostCenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CostCenterController extends Controller
{
    public function __construct(
        private readonly CostCenterManager $costCenters,
        private readonly DepartmentManager $departments,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', CostCenter::class);

        return view('cost-centers.index', [
            'costCenters' => $this->costCenters->paginate(
                $request->only(['search', 'status', 'department_id'])
            ),
            'departments' => $this->departments->allActive(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', CostCenter::class);

        return view('cost-centers.create', [
            'costCenter' => new CostCenter(['status' => RecordStatus::Active]),
            'departments' => $this->departments->allActive(),
        ]);
    }

    public function store(StoreCostCenterRequest $request): RedirectResponse
    {
        $costCenter = $this->costCenters->create($request->validated());

        return redirect()
            ->route('cost-centers.index')
            ->with('status', "Cost center {$costCenter->name} created.");
    }

    public function edit(CostCenter $costCenter): View
    {
        $this->authorize('update', $costCenter);

        return view('cost-centers.edit', [
            'costCenter' => $costCenter->load('department'),
            'departments' => $this->departments->allActive(),
        ]);
    }

    public function update(UpdateCostCenterRequest $request, CostCenter $costCenter): RedirectResponse
    {
        $this->costCenters->update($costCenter, $request->validated());

        return redirect()
            ->route('cost-centers.index')
            ->with('status', 'Cost center updated.');
    }

    public function destroy(CostCenter $costCenter): RedirectResponse
    {
        $this->authorize('delete', $costCenter);

        $this->costCenters->delete($costCenter);

        return redirect()
            ->route('cost-centers.index')
            ->with('status', 'Cost center deleted.');
    }

    public function changeStatus(Request $request, CostCenter $costCenter): RedirectResponse
    {
        $this->authorize('changeStatus', $costCenter);

        $request->validate(['status' => ['required', 'string']]);

        $this->costCenters->changeStatus(
            $costCenter,
            RecordStatus::from((string) $request->input('status')),
        );

        return back()->with('status', 'Status updated.');
    }
}
