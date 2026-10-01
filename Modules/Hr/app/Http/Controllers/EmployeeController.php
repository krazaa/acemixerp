<?php

declare(strict_types=1);

namespace Modules\Hr\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Designation;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Hr\Contracts\EmployeeManager;
use Modules\Hr\Http\Requests\StoreEmployeeRequest;
use Modules\Hr\Http\Requests\UpdateEmployeeRequest;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\LeavePolicy;
use Modules\Hr\Models\SalaryStructure;

class EmployeeController extends Controller
{
    public function __construct(private readonly EmployeeManager $employees) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        abort_unless(auth()->user()?->can('hr.view'), 403);

        return view('hr::employees.index', ['employees' => $this->employees->paginate(request()->only('search'))]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        abort_unless(auth()->user()?->can('hr.manage'), 403);

        return view('hr::employees.create', $this->formData() + ['employee' => new Employee(['joining_date' => now()->toDateString(), 'attendance_enabled' => true])]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $employee = $this->employees->create($request->validated(), $request->user()->id);

        return redirect()->route('employees.show', $employee)->with('status', 'Employee created in onboarding.');
    }

    /**
     * Show the specified resource.
     */
    public function show(Employee $employee): View
    {
        abort_unless(auth()->user()?->can('hr.view'), 403);

        return view('hr::employees.show', ['employee' => $employee->load(['department', 'designation', 'salaryStructure', 'leavePolicy', 'contracts'])]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Employee $employee): View
    {
        abort_unless(auth()->user()?->can('hr.manage'), 403);

        return view('hr::employees.edit', $this->formData() + ['employee' => $employee]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $this->employees->update($employee, $request->safe()->except(['contract_type', 'contract_end_date', 'monthly_salary']), $request->user()->id);

        return redirect()->route('employees.show', $employee);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employee $employee): RedirectResponse
    {
        abort_unless(auth()->user()?->can('hr.manage'), 403);
        $this->employees->delete($employee);

        return redirect()->route('employees.index');
    }

    public function activate(Employee $employee): RedirectResponse
    {
        abort_unless(auth()->user()?->can('hr.manage'), 403);
        $this->employees->activate($employee, auth()->id());

        return back()->with('status', 'Employee activated.');
    }

    private function formData(): array
    {
        return ['departments' => Department::active()->orderBy('name')->get(), 'designations' => Designation::active()->orderBy('name')->get(), 'salaryStructures' => SalaryStructure::where('is_active', true)->orderBy('name')->get(), 'leavePolicies' => LeavePolicy::where('is_active', true)->orderBy('name')->get()];
    }
}
