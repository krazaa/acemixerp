<x-default-layout>
@section('title', 'Employees')

@section('toolbar-button')
    <a href="{{ route('employees.create') }}" class="btn btn-sm btn-primary">New Employee</a>
@endsection

<form class="mb-3">
    <input name="search" value="{{ request('search') }}" class="form-control" placeholder="Employee number or name"></form>
<div class="card">
    <div class="card-body">
    <div class="table-responsive">
	<table class="table">
        <thead>
            <tr class="fw-bold fs-6 text-gray-800">
                <th>Number</th>
                <th>Employee</th>
                <th>Department</th>
                <th>Designation</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($employees as $employee)
            <tr>
                <td>
                    <code>{{ $employee->number }}</code>
                </td>
                <td>{{ $employee->fullName() }}</td>
                <td>{{ $employee->department?->name }}</td>
                <td>{{ $employee->designation?->name }}</td>
                <td>{{ $employee->status }}</td>
                <td class="text-nowrap"><a href="{{ route('employees.show', $employee) }}" class="btn btn-sm btn-outline-secondary">View</a>
                    @can('hr.manage')
                    <a href="{{ route('employees.edit', $employee) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                    @endcan
                </td>
            </tr
            >@empty
            <tr>
                <td colspan="6" class="text-center text-muted">No employees.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
 </div>
<div class="mt-3">{{ $employees->links() }}</div>

</div>
</x-default-layout>
