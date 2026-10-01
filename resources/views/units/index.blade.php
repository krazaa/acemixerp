<x-default-layout>
@section('title', 'Units of Measure')
    @section('toolbar-button')
         @can('create', \App\Models\Unit::class)
        <a href="{{ route('units.create') }}" class="btn btn-sm btn-primary">New Unit</a>
    @endcan
    @endsection


<form method="GET" class="row g-2 mb-3">
    <div class="col-md-5">
        <input name="search" value="{{ request('search') }}" class="form-control" placeholder="Search name or code">
    </div>
    <div class="col-md-3">
        <select name="status" class="form-select">
            <option value="">All statuses</option>
            @foreach(\App\Enums\RecordStatus::cases() as $s)
                <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-light-info w-100">Filter</button></div>
    <div class="col-md-2"><a href="{{ route('units.index') }}" class="btn btn-light-secondary w-100">Reset</a></div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-body">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
			<tr class="fw-bold fs-6 text-gray-800">
                    <th>Code</th>
                    <th>Name</th>
                    <th class="text-center">Precision</th>
                    <th class="text-center">Items</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($units as $u)
                    <tr>
                        <td><code>{{ $u->code }}</code></td>
                        <td>{{ $u->name }}</td>
                        <td class="text-center">{{ $u->quantity_precision }}</td>
                        <td class="text-center">{{ $u->items_count }}</td>
                        <td>
                            <span class="badge badge-{{ $u->status->badgeClass() }}">{{ $u->status->label() }}</span>
                        </td>
                        <td class="text-end">
                            @can('update', $u)
                                <a href="{{ route('units.edit', $u) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            @endcan
                            @can('delete', $u)
                                <form method="POST" action="{{ route('units.destroy', $u) }}" class="d-inline"
                                      onsubmit="return confirm('Delete this unit?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No units.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
<div class="mt-3">{{ $units->links() }}</div>
</x-default-layout>
