<x-default-layout>
@section('title', 'Origins List')

@section('toolbar-button')
        <a href="{{ route('inventory.origins.create') }}" class="btn btn-sm btn-primary">
            Add Origin
        </a>
    </div>
@endsection

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}

            <button type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row">
                <div class="col-md-5">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        class="form-control"
                        placeholder="Search name or code">
                </div>

                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>

                        <option value="active"
                            @selected(request('status') === 'active')>
                            Active
                        </option>

                        <option value="inactive"
                            @selected(request('status') === 'inactive')>
                            Inactive
                        </option>
                    </select>
                </div>

                <div class="col-md-4">
                    <button class="btn btn-primary">
                        Search
                    </button>

                    <a href="{{ route('inventory.origins.index') }}"
                       class="btn btn-secondary">
                        Reset
                    </a>
                </div>

            </form>

        </div>
    </div>

    <div class="card">
        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr class="fw-bold fs-6 text-gray-800">
                        <th width="70">#</th>
                        <th>Origin</th>
                        <th>Code</th>
                        <th>Status</th>
                        <th width="180" class="text-end">Actions</th>
                    </tr>
                    </thead>

                    <tbody>

                    @forelse($origins as $origin)

                        <tr>
                            <td>
                                {{ $origins->firstItem() + $loop->index }}
                            </td>

                            <td>
                                <strong>{{ $origin->name }}</strong>
                            </td>

                            <td>
                                {{ $origin->code ?: '—' }}
                            </td>

                            <td>
                               <span class="badge badge-{{ $origin->status->badgeClass() }}">{{ $origin->status->label() }}</span>
                            </td>

                            <td class="text-end">

                                <a
                                    href="{{ route('inventory.origins.edit', $origin) }}"
                                    class="btn btn-sm btn-light-warning">
                                    Edit
                                </a>

                                <form
                                    action="{{ route('inventory.origins.destroy', $origin) }}"
                                    method="POST"
                                    class="d-inline"
                                    onsubmit="return confirm('Delete this origin?')">

                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="btn btn-sm btn-light-danger">
                                        Delete
                                    </button>

                                </form>

                            </td>
                        </tr>

                    @empty

                        <tr>
                            <td colspan="5"
                                class="text-center text-muted py-4">
                                No origins found.
                            </td>
                        </tr>

                    @endforelse

                    </tbody>
                </table>
            </div>

        </div>

        @if($origins->hasPages())
            <div class="card-footer">
                {{ $origins->links() }}
            </div>
        @endif
    </div>

</div>
</x-default-layout>
