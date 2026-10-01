<?php

namespace App\Http\Controllers;

use App\Contracts\DepartmentManager;
use App\Contracts\DesignationManager;
use App\Enums\RecordStatus;
use App\Http\Requests\Designations\StoreDesignationRequest;
use App\Http\Requests\Designations\UpdateDesignationRequest;
use App\Models\Designation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DesignationController extends Controller
{
    public function __construct(
        private readonly DesignationManager $designations,
        private readonly DepartmentManager $departments,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Designation::class);

        return view('designations.index', [
            'designations' => $this->designations->paginate(
                $request->only(['search', 'status', 'department_id'])
            ),
            'departments' => $this->departments->allActive(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Designation::class);

        return view('designations.create', [
            'designation' => new Designation(['level' => 100, 'status' => RecordStatus::Active]),
            'departments' => $this->departments->allActive(),
        ]);
    }

    public function store(StoreDesignationRequest $request): RedirectResponse
    {
        $designation = $this->designations->create($request->validated());

        return redirect()
            ->route('designations.index')
            ->with('status', "Designation {$designation->name} created.");
    }

    public function edit(Designation $designation): View
    {
        $this->authorize('update', $designation);

        return view('designations.edit', [
            'designation' => $designation->load('department'),
            'departments' => $this->departments->allActive(),
        ]);
    }

    public function update(UpdateDesignationRequest $request, Designation $designation): RedirectResponse
    {
        $this->designations->update($designation, $request->validated());

        return redirect()
            ->route('designations.index')
            ->with('status', 'Designation updated.');
    }

    public function destroy(Designation $designation): RedirectResponse
    {
        $this->authorize('delete', $designation);

        $this->designations->delete($designation);

        return redirect()
            ->route('designations.index')
            ->with('status', 'Designation deleted.');
    }

    public function changeStatus(Request $request, Designation $designation): RedirectResponse
    {
        $this->authorize('changeStatus', $designation);

        $request->validate(['status' => ['required', 'string']]);

        $this->designations->changeStatus(
            $designation,
            RecordStatus::from((string) $request->input('status')),
        );

        return back()->with('status', 'Status updated.');
    }
}
