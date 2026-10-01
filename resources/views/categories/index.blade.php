<x-default-layout>
@section('title', 'Categories')

@section('toolbar-button')
    @can('create', \App\Models\Category::class)
        <a href="{{ route('categories.create') }}" class="btn btn-sm btn-primary">New Category</a>
    @endcan
@endsection



<form method="GET" class="row g-2 mb-3">
    <div class="col-md-5">
        <input name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Search name or code">
    </div>
    <div class="col-md-3">
        <select name="status" class="form-select form-select-sm">
            <option value="">All statuses</option>
            @foreach(\App\Enums\RecordStatus::cases() as $s)
                <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-sm btn-light-info w-100">Filter</button></div>
    <div class="col-md-2"><a href="{{ route('categories.index') }}" class="btn btn-sm btn-light-secondary w-100">Reset</a></div>
</form>

<div class="card border-0 shadow-sm">
        <div class="card-body">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr class="fw-bold fs-6 text-gray-800">
                    <th>Code</th>
                    <th>Name</th>
                    <th>Parent</th>
                    <th>Items</th>
                    <th>Sub-cats</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $c)
                    <tr>
                        <td><code>{{ $c->code }}</code></td>
                        <td>
                            <div class="fw-semibold">{{ $c->name }}</div>
                            @if($c->description)<div class="text-muted small">{{ $c->description }}</div>@endif
                        </td>
                        <td>{{ $c->parent?->name ?? '—' }}</td>
                        <td>{{ $c->items_count }}</td>
                        <td>{{ $c->children_count }}</td>
                        <td>
                            <span class="badge badge-{{ $c->status->badgeClass() }}">
                                {{ $c->status->label() }}
                            </span>
                        </td>
                        <td class="text-end">
                            @can('update', $c)
                                <a href="{{ route('categories.edit', $c) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            @endcan
                            @can('delete', $c)
                                <form method="POST" action="{{ route('categories.destroy', $c) }}"
                                      class="d-inline" onsubmit="return confirm('Delete this category?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No categories.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
<div class="mt-3">{{ $categories->links() }}</div>

</x-default-layout>
