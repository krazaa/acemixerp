<x-default-layout>
@section('title', 'HR Dashboard')

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card card-body">
            Active Employees <strong>{{ $activeEmployees }}</strong>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-body">Onboarding <strong>{{ $onboardingEmployees }}</strong>
        </div>
    </div>
    {{-- <div class="col-md-3">
        <div class="card card-body">Pending Leave <strong>{{ $pendingLeaveRequests }}</strong></div>
    </div> --}}
    <div class="col-md-3">
        <div class="card card-body">Draft Payroll <strong>{{ $draftPayrollRuns }}</strong></div>
    </div>
</div
><div class="d-flex gap-2 flex-wrap"><a href="{{ route('employees.index') }}" class="btn btn-outline-primary">Employees</a>
    @can('hr.manage')<a href="{{ route('salary-structures.index') }}" class="btn btn-outline-primary">Salary Structures</a>@endcan
    {{-- <a href="{{ route('attendances.index') }}" class="btn btn-outline-primary">Attendance</a>
    <a href="{{ route('leave-requests.index') }}" class="btn btn-outline-primary">Leave</a> --}}
    <a href="{{ route('payroll-runs.index') }}" class="btn btn-outline-primary">Payroll</a>
    <a href="{{ route('employee-exits.index') }}" class="btn btn-outline-primary">Employee Exits</a></div>
</x-default-layout>
