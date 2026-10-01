<x-default-layout>
    @section('title', 'Vendors')

    @section('toolbar-button')
        @can('create', \App\Models\Vendor::class)
            <a href="{{ route('vendors.create') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg"></i> New Vendor
            </a>
        @endcan
    @endsection


<form method="GET" class="row g-2 mb-3">
    <div class="col-md-5">
        <input name="search" value="{{ request('search') }}" class="form-control"
               placeholder="Search name, code, email, tax #">
    </div>
    <div class="col-md-3">
        <select name="status" class="form-select">
            <option value="">All statuses</option>
            @foreach(\App\Enums\VendorStatus::cases() as $s)
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
        <a href="{{ route('vendors.index') }}" class="btn btn-outline-secondary w-100">Reset</a>
    </div>
</form>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
	    <table class="table">
            <thead>
                <tr class="fw-bold fs-6 text-gray-800">
                    <th>Code</th>
                    <th>Name</th>
                    <th>Contact</th>

                    <th class="text-center">Type</th>
                    <th class="text-center">Credit Days</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($vendors as $v)
                    <tr>
                        <td><code>{{ $v->code }}</code></td>
                        <td>
                            <div class="fw-semibold">
                                <a href="{{ route('vendors.show', $v) }}" class="text-decoration-none">
                                    {{ $v->name }}
                                </a>
                            </div>
                            @if($v->legal_name && $v->legal_name !== $v->name)
                                <div class="text-muted small">{{ $v->legal_name }}</div>
                            @endif
                            @if($v->tax_number)
                                <div class="text-muted small">Tax: {{ $v->tax_number }}</div>
                            @endif
                        </td>
                        <td>
                            <div class="fw-semibold">
                            @if($v->phone)
                                <div class="text-muted small">{{ $v->phone }}</div>
                            @endif

                            @if($v->email)

                                <div class="text-muted small">{{ $v->email }}</div>
                            @endif

                            </div>
                        </td>

                        <td class="text-center">
                           {{ ucfirst($v->vendor_type ?? '') }}
                        </td>
                        <td class="text-center">{{ $v->credit_days }}</td>
                        <td>
                            <span class="badge badge-{{ $v->status->badgeClass() }}">
                                {{ $v->status->label() }}
                            </span>
                        </td>
                        <td class="text-end">
                            @can('view', $v)
                                <a href="{{ route('vendors.show', $v) }}"
                                   class="btn btn-sm btn-outline-secondary">View</a>
                            @endcan
                            @can('update', $v)
                                <a href="{{ route('vendors.edit', $v) }}"
                                   class="btn btn-sm btn-outline-primary">Edit</a>
                            @endcan
                            @can('delete', $v)
                                <form method="POST" action="{{ route('vendors.destroy', $v) }}"
                                      class="d-inline"
                                      onsubmit="return confirm('Delete this vendor?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            No vendors found.
                            @can('create', \App\Models\Vendor::class)
                                <a href="{{ route('vendors.create') }}">Create the first vendor</a>.
                            @endcan
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>

<div class="mt-3">{{ $vendors->links() }}</div>
</x-default-layout>
