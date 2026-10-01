<?php

declare(strict_types=1);

namespace Modules\Hr\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Hr\Http\Requests\StoreLeaveRequest;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\LeaveRequest;

class LeaveRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        abort_unless(auth()->user()?->can('hr.view'), 403);

        return view('hr::leave-requests.index', ['leaveRequests' => LeaveRequest::query()->with('employee')->latest('start_date')->paginate()]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        abort_unless(auth()->user()?->can('leave.approve'), 403);

        return view('hr::leave-requests.create', ['employees' => Employee::query()->where('status', 'active')->orderBy('first_name')->get()]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLeaveRequest $request): RedirectResponse
    {
        $data = $request->validated();
        LeaveRequest::query()->create([
            ...$data,
            'days' => (string) ($request->date('start_date')->diffInDays($request->date('end_date')) + 1),
        ]);

        return redirect()->route('leave-requests.index')->with('status', 'Leave request submitted.');
    }

    public function approve(LeaveRequest $leaveRequest): RedirectResponse
    {
        abort_unless(auth()->user()?->can('leave.approve'), 403);
        $leaveRequest->update(['status' => 'approved', 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);

        return back()->with('status', 'Leave request approved.');
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
