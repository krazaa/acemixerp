<x-default-layout>
@section('title', 'Cost Centers')

@section('toolbar-button')
   @can('create', \App\Models\CostCenter::class)
        <a href="{{ route('cost-centers.create') }}" class="btn btn-sm btn-primary">New Cost Center</a>
    @endcan
@endsection

<form method="GET" class="row g-2 mb-3">
    <div class="col-md-4">
        <input name="search" value="{{ request('search') }}" class="form-control" placeholder="Search name or code">
    </div>
    <div class="col-md-3">
        <select name="department_id" class="form-select">
            <option value="">All departments</option>
            @foreach($departments as $d)
                <option value="{{ $d->id }}" @selected(request('department_id') == $d->id)>{{ $d->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <select name="status" class="form-select">
            <option value="">All statuses</option>
            @foreach(\App\Enums\RecordStatus::cases() as $s)
                <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <button class="btn btn-light-info w-100">Filter</button>
    </div>
</form>

<div class="card border-0 shadow-sm">
        <div class="card-body">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr class="fw-bold fs-6 text-gray-800">
                    <th>Code</th>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($costCenters as $c)
                    <tr>
                        <td><code>{{ $c->code }}</code></td>
                        <td>
                            <div class="fw-semibold">{{ $c->name }}</div>
                            @if($c->description)
                                <div class="text-muted small">{{ $c->description }}</div>
                            @endif
                        </td>
                        <td>{{ $c->department?->name ?? '—' }}</td>
                        <td>
                            <span class="badge badge-{{ $c->status->badgeClass() }}">
                                {{ $c->status->label() }}
                            </span>
                        </td>
                        <td class="text-end">
                            @can('update', $c)
                                <a href="{{ route('cost-centers.edit', $c) }}"
                                   class="btn btn-sm btn-outline-primary">Edit</a>
                            @endcan
                            @can('delete', $c)
                                <form method="POST" action="{{ route('cost-centers.destroy', $c) }}"
                                      class="d-inline"
                                      onsubmit="return confirm('Delete this cost center?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No cost centers.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
<div class="mt-3">{{ $costCenters->links() }}</div>
</x-default-layout>
