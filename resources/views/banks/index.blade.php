<x-default-layout>
@section('title', 'Banks')

@section('toolbar-button')
   @can('create', \App\Models\Bank::class)
        <a href="{{ route('banks.create') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg"></i> New Bank
        </a>
    @endcan
@endsection

<form method="GET" class="row g-2 mb-3">
    <div class="col-md-5">
        <input name="search" value="{{ request('search') }}" class="form-control"
               placeholder="Search name, code, short name, SWIFT">
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
    <div class="col-md-2"><button class="btn btn-light-info w-100">Filter</button></div>
    <div class="col-md-2"><a href="{{ route('banks.index') }}" class="btn btn-light-secondary w-100">Reset</a></div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-body">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr class="fw-bold fs-6 text-gray-800">
                    <th>Code</th>
                    <th>Name</th>
                    <th>SWIFT</th>
                    <th>Country</th>
                    <th>Contact</th>
                    <th class="text-center">Addresses</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($banks as $b)
                    <tr>
                        <td><code>{{ $b->code }}</code></td>
                        <td>
                            <div class="fw-semibold">
                                <a href="{{ route('banks.show', $b) }}" class="text-decoration-none">
                                    {{ $b->name }}
                                </a>
                            </div>
                            @if($b->short_name)
                                <div class="text-muted small">{{ $b->short_name }}</div>
                            @endif
                        </td>
                        <td>{{ $b->swift_code ?? '—' }}</td>
                        <td>{{ $b->country }}</td>
                        <td>
                            @if($b->email)<div class="small">{{ $b->email }}</div>@endif
                            @if($b->phone)<div class="small text-muted">{{ $b->phone }}</div>@endif
                        </td>
                        <td class="text-center">
                            <span class="badge badge-light border">{{ $b->addresses_count }}</span>
                        </td>
                        <td>
                            <span class="badge badge-{{ $b->status->badgeClass() }}">
                                {{ $b->status->label() }}
                            </span>
                        </td>
                        <td class="text-end">
                            @can('view', $b)
                                <a href="{{ route('banks.show', $b) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            @endcan
                            @can('update', $b)
                                <a href="{{ route('banks.edit', $b) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            @endcan
                            @can('delete', $b)
                                <form method="POST" action="{{ route('banks.destroy', $b) }}" class="d-inline"
                                      onsubmit="return confirm('Delete this bank?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            No banks found.
                            @can('create', \App\Models\Bank::class)
                                <a href="{{ route('banks.create') }}">Create the first bank</a>.
                            @endcan
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
<div class="mt-3">{{ $banks->links() }}</div>
</x-default-layout>
