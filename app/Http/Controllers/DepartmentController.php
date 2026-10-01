<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\DepartmentManager;
use App\Enums\RecordStatus;
use App\Enums\UserStatus;
use App\Http\Requests\Departments\StoreDepartmentRequest;
use App\Http\Requests\Departments\UpdateDepartmentRequest;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function __construct(
        private readonly DepartmentManager $departments,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Department::class);

        return view('departments.index', [
            'departments' => $this->departments->paginate(
                $request->only(['search', 'status'])
            ),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Department::class);

        return view('departments.create', [
            'department' => new Department(['status' => RecordStatus::Active]),
            'parents' => $this->departments->allActive(),
            'managers' => $this->managerCandidates(),
        ]);
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        $department = $this->departments->create($request->validated());

        return redirect()
            ->route('departments.index')
            ->with('status', "Department {$department->name} created.");
    }

    public function edit(Department $department): View
    {
        $this->authorize('update', $department);

        return view('departments.edit', [
            'department' => $department->load(['parent', 'manager']),
            'parents' => $this->departments->allActive()
                ->where('id', '!=', $department->id),
            'managers' => $this->managerCandidates(),
        ]);
    }

    public function update(UpdateDepartmentRequest $request, Department $department): RedirectResponse
    {
        $this->departments->update($department, $request->validated());

        return redirect()
            ->route('departments.index')
            ->with('status', 'Department updated.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        $this->authorize('delete', $department);

        $this->departments->delete($department);

        return redirect()
            ->route('departments.index')
            ->with('status', 'Department deleted.');
    }

    public function changeStatus(Request $request, Department $department): RedirectResponse
    {
        $this->authorize('changeStatus', $department);

        $request->validate(['status' => ['required', 'string']]);

        $this->departments->changeStatus(
            $department,
            RecordStatus::from((string) $request->input('status')),
        );

        return back()->with('status', 'Status updated.');
    }

    /**
     * Simple manager list — active users only. Not paginated because
     * department count is small. Swapped for a searchable select once
     * the users module grows.
     */
    private function managerCandidates(): Collection
    {
        return User::query()
            ->where('status', UserStatus::Active->value)
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
