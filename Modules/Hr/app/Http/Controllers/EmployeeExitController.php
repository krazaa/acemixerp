<?php

declare(strict_types=1);

namespace Modules\Hr\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Hr\Http\Requests\StoreEmployeeExitRequest;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\EmployeeExit;

class EmployeeExitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('hr::employee-exits.index', ['exits' => EmployeeExit::query()->with('employee')->latest('exit_date')->paginate()]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('hr::employee-exits.create', ['employees' => Employee::query()->where('status', 'active')->orderBy('first_name')->get()]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEmployeeExitRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $exit = EmployeeExit::query()->create([...$request->validated(), 'processed_by' => $request->user()->id]);
            $exit->employee()->update(['status' => 'exited', 'updated_by' => $request->user()->id]);
        });

        return redirect()->route('employee-exits.index')->with('status', 'Employee exit processed.');
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('hr::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('hr::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}
}
