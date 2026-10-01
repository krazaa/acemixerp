<x-default-layout>
@section('title', 'Customers')

@section('toolbar-button')
   @can('create', \App\Models\Customer::class)
         <a href="{{ route('customers.create') }}" class="btn btn-sm btn-primary">New Customer</a>
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
            @foreach(\App\Enums\CustomerStatus::cases() as $s)
                <option value="{{ $s->value }}" @selected(request('status') === $s->value)>
                    {{ $s->label() }}
                </option>
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
                    <th>Email</th>
                    <th>Credit Limit</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $c)
                    <tr>
                        <td><code>{{ $c->code }}</code></td>
                        <td>
                            <div class="fw-semibold">
                                <a href="{{ route('customers.show', $c) }}" class="text-decoration-none">
                                    {{ $c->name }}
                                </a>
                            </div>
                            @if($c->legal_name && $c->legal_name !== $c->name)
                                <div class="text-muted small">{{ $c->legal_name }}</div>
                            @endif
                        </td>
                        <td>{{ $c->email ?? '—' }}</td>
                        <td>
                            @if((float) $c->credit_limit > 0)
                                {{ number_format((float) $c->credit_limit, 2) }}
                            @else
                                <span class="text-muted">No limit</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-{{ $c->status->badgeClass() }}">
                                {{ $c->status->label() }}
                            </span>
                        </td>
                        <td class="text-end">
                            @can('update', $c)
                                <a href="{{ route('customers.edit', $c) }}"
                                   class="btn btn-sm btn-outline-primary">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No customers.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
<div class="mt-3">{{ $customers->links() }}</div>
</x-default-layout>
