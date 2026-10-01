<?php

declare(strict_types=1);

namespace Modules\Hr\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Hr\Http\Requests\StoreAttendanceRequest;
use Modules\Hr\Models\Attendance;
use Modules\Hr\Models\Employee;

class AttendanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        abort_unless(auth()->user()?->can('attendance.manage'), 403);

        return view('hr::attendances.index', ['attendances' => Attendance::query()->with('employee')->latest('attendance_date')->paginate()]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        abort_unless(auth()->user()?->can('attendance.manage'), 403);

        return view('hr::attendances.create', ['employees' => Employee::query()->where('status', 'active')->orderBy('first_name')->get()]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAttendanceRequest $request): RedirectResponse
    {
        Attendance::query()->updateOrCreate(['employee_id' => $request->integer('employee_id'), 'attendance_date' => $request->date('attendance_date')], $request->safe()->except(['employee_id', 'attendance_date']));

        return redirect()->route('attendances.index')->with('status', 'Attendance saved.');
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
