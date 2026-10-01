<?php

declare(strict_types=1);

namespace Modules\Hr\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Hr\Http\Requests\StoreSalaryStructureRequest;
use Modules\Hr\Http\Requests\UpdateSalaryStructureRequest;
use Modules\Hr\Models\SalaryStructure;

class SalaryStructureController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->can('hr.manage'), 403);

        return view('hr::salary-structures.index', [
            'salaryStructures' => SalaryStructure::query()->orderBy('code')->paginate(25),
        ]);
    }

    public function create(): View
    {
        abort_unless(auth()->user()?->can('hr.manage'), 403);

        return view('hr::salary-structures.create', ['salaryStructure' => new SalaryStructure(['is_active' => true])]);
    }

    public function store(StoreSalaryStructureRequest $request): RedirectResponse
    {
        $salaryStructure = SalaryStructure::query()->create($request->validated());

        return redirect()->route('salary-structures.show', $salaryStructure)->with('status', 'Salary structure created.');
    }

    public function show(SalaryStructure $salaryStructure): View
    {
        abort_unless(auth()->user()?->can('hr.manage'), 403);

        return view('hr::salary-structures.show', ['salaryStructure' => $salaryStructure->loadCount('employees')]);
    }

    public function edit(SalaryStructure $salaryStructure): View
    {
        abort_unless(auth()->user()?->can('hr.manage'), 403);

        return view('hr::salary-structures.edit', compact('salaryStructure'));
    }

    public function update(UpdateSalaryStructureRequest $request, SalaryStructure $salaryStructure): RedirectResponse
    {
        $salaryStructure->update($request->validated());

        return redirect()->route('salary-structures.show', $salaryStructure)->with('status', 'Salary structure updated.');
    }

    public function destroy(SalaryStructure $salaryStructure): RedirectResponse
    {
        abort_unless(auth()->user()?->can('hr.manage'), 403);

        if ($salaryStructure->employees()->exists()) {
            return back()->withErrors(['salary_structure' => 'This salary structure is assigned to employees and cannot be deleted.']);
        }

        $salaryStructure->delete();

        return redirect()->route('salary-structures.index')->with('status', 'Salary structure deleted.');
    }
}
