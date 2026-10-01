<x-default-layout>
@section('title', $employee->fullName())

@section('sub-title')
    <code>{{ $employee->number }}</code> · {{ $employee->status }}
@endsection

@section('toolbar-button')
    <div class="d-flex gap-2">
        <a href="{{ route('employees.edit', $employee) }}" class="btn btn-light">
            Edit
        </a>

        @if($employee->status === 'onboarding')
            <form method="POST" action="{{ route('employees.activate', $employee) }}" class="d-inline">
                @csrf
                @method('PATCH')

                <button type="submit" class="btn btn-success">
                    Activate Employee
                </button>
            </form>
        @endif
    </div>
@endsection


{{-- Employment Details --}}
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title mb-0">Employment Details</h3>
    </div>

    <div class="card-body">
        <dl class="row mb-0">

            <dt class="col-sm-3">Employee Number</dt>
            <dd class="col-sm-9">
                {{ $employee->number }}
            </dd>

            <dt class="col-sm-3">Department</dt>
            <dd class="col-sm-9">
                {{ $employee->department?->name ?? '—' }}
            </dd>

            <dt class="col-sm-3">Designation</dt>
            <dd class="col-sm-9">
                {{ $employee->designation?->name ?? '—' }}
            </dd>

            <dt class="col-sm-3">Joining Date</dt>
            <dd class="col-sm-9">
                {{ $employee->joining_date?->toDateString() ?? '—' }}
            </dd>

            <dt class="col-sm-3">Hire Date</dt>
            <dd class="col-sm-9">
                {{ $employee->hire_date?->toDateString() ?? '—' }}
            </dd>

            <dt class="col-sm-3">Salary Structure</dt>
            <dd class="col-sm-9">
                {{ $employee->salaryStructure?->name ?? '—' }}
            </dd>

            <dt class="col-sm-3">Leave Policy</dt>
            <dd class="col-sm-9">
                {{ $employee->leavePolicy?->name ?? '—' }}
            </dd>

            <dt class="col-sm-3">Attendance</dt>
            <dd class="col-sm-9">
                {{ $employee->attendance_enabled ? 'Enabled' : 'Disabled' }}
            </dd>

        </dl>
    </div>
</div>


{{-- Personal & Banking Details --}}
<div class="row g-3">

    {{-- Personal Details --}}
    <div class="col-12 col-lg-6">
        <div class="card h-100">

            <div class="card-header">
                <h3 class="card-title mb-0">Personal Details</h3>
            </div>

            <div class="card-body">

                <dl class="row mb-0">

                    <dt class="col-12 col-sm-5">Work Email</dt>
                    <dd class="col-12 col-sm-7 text-break">
                        {{ $employee->email ?? '—' }}
                    </dd>

                    <dt class="col-12 col-sm-5">Personal Email</dt>
                    <dd class="col-12 col-sm-7 text-break">
                        {{ $employee->personal_email ?? '—' }}
                    </dd>

                    <dt class="col-12 col-sm-5">Work Phone</dt>
                    <dd class="col-12 col-sm-7">
                        {{ $employee->phone ?? '—' }}
                    </dd>

                    <dt class="col-12 col-sm-5">Personal Phone</dt>
                    <dd class="col-12 col-sm-7">
                        {{ $employee->personal_phone ?? '—' }}
                    </dd>

                    <dt class="col-12 col-sm-5">Date of Birth</dt>
                    <dd class="col-12 col-sm-7">
                        {{ $employee->date_of_birth?->toDateString() ?? '—' }}
                    </dd>

                    <dt class="col-12 col-sm-5">Gender</dt>
                    <dd class="col-12 col-sm-7">
                        {{ $employee->gender ?? '—' }}
                    </dd>

                    <dt class="col-12 col-sm-5">Tax Number</dt>
                    <dd class="col-12 col-sm-7">
                        {{ $employee->tax_number ?? '—' }}
                    </dd>

                    <dt class="col-12 col-sm-5">Address</dt>
                    <dd class="col-12 col-sm-7 text-break">
                        {{
                            collect([
                                $employee->address_line1,
                                $employee->city,
                                $employee->postal_code,
                            ])->filter()->join(', ') ?: '—'
                        }}
                    </dd>

                </dl>

            </div>
        </div>
    </div>


    {{-- Bank & Emergency Contact --}}
    <div class="col-12 col-lg-6">
        <div class="card h-100">

            <div class="card-header">
                <h3 class="card-title mb-0">
                    Bank and Emergency Contact
                </h3>
            </div>

            <div class="card-body">

                <dl class="row mb-0">

                    <dt class="col-12 col-sm-5">Bank Name</dt>
                    <dd class="col-12 col-sm-7">
                        {{ $employee->bank_name ?? '—' }}
                    </dd>

                    <dt class="col-12 col-sm-5">Bank Branch</dt>
                    <dd class="col-12 col-sm-7">
                        {{ $employee->bank_branch ?? '—' }}
                    </dd>

                    <dt class="col-12 col-sm-5">Bank Account Number</dt>
                    <dd class="col-12 col-sm-7 text-break">
                        {{ $employee->bank_account_number ?? '—' }}
                    </dd>

                    <dt class="col-12 col-sm-5">Emergency Contact</dt>
                    <dd class="col-12 col-sm-7">
                        {{ $employee->emergency_contact_name ?? '—' }}
                    </dd>

                    <dt class="col-12 col-sm-5">Emergency Phone</dt>
                    <dd class="col-12 col-sm-7">
                        {{ $employee->emergency_contact_phone ?? '—' }}
                    </dd>

                </dl>

            </div>
        </div>
    </div>

</div>
</x-default-layout>
