<x-default-layout>
@section('title', 'Departments')

@section('toolbar-button')
    @can('create', \App\Models\Department::class)
        <a href="{{ route('departments.create') }}" class="btn btn-sm btn-primary">New Department</a>
    @endcan

@endsection


<form method="GET" class="row g-2 mb-3">
    <div class="col-md-5">
        <input name="search" value="{{ request('search') }}" class="form-control"
               placeholder="Search name or code">
    </div>
    <div class="col-md-3">
        <select name="status" class="form-select">
            <option value="">All statuses</option>
            @foreach(\App\Enums\RecordStatus::cases() as $s)
                <option value="{{ $s->value }}" @selected(request('status') === $s->value)>
                    {{ $s->label() }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <button class="btn btn-outline-secondary w-100">Filter</button>
    </div>
    <div class="col-md-2">
        <a href="{{ route('departments.index') }}" class="btn btn-outline-secondary w-100">Reset</a>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Parent</th>
                    <th>Manager</th>
                    <th>Users</th>
                    <th>Sub-depts</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($departments as $dept)
                    <tr>
                        <td><code>{{ $dept->code }}</code></td>
                        <td>
                            <div class="fw-semibold">{{ $dept->name }}</div>
                            @if($dept->description)
                                <div class="text-muted small">{{ $dept->description }}</div>
                            @endif
                        </td>
                        <td>{{ $dept->parent?->name ?? '—' }}</td>
                        <td>{{ $dept->manager?->name ?? '—' }}</td>
                        <td>{{ $dept->users_count }}</td>
                        <td>{{ $dept->children_count }}</td>
                        <td>
                            <span class="badge badge-{{ $dept->status->badgeClass() }}">
                                {{ $dept->status->label() }}
                            </span>
                        </td>
                        <td class="text-end">
                            @can('update', $dept)
                                <a href="{{ route('departments.edit', $dept) }}"
                                   class="btn btn-sm btn-outline-primary">Edit</a>
                            @endcan
                            @can('delete', $dept)
                                <form method="POST" action="{{ route('departments.destroy', $dept) }}"
                                      class="d-inline"
                                      onsubmit="return confirm('Delete this department?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No departments.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $departments->links() }}</div>
</x-default-layout>
