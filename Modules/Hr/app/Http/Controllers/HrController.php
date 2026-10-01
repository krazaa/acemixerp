<?php

declare(strict_types=1);

namespace Modules\Hr\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\LeaveRequest;
use Modules\Hr\Models\PayrollRun;

class HrController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->can('hr.view'), 403);

        return view('hr::index', ['activeEmployees' => Employee::query()->where('status', 'active')->count(), 'onboardingEmployees' => Employee::query()->where('status', 'onboarding')->count(), 'pendingLeaveRequests' => LeaveRequest::query()->where('status', 'pending')->count(), 'draftPayrollRuns' => PayrollRun::query()->where('status', 'draft')->count()]);
    }
}
