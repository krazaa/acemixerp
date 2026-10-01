<x-default-layout>
@section('title', 'Warehouses')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 m-0">Warehouses</h1>
    @can('create', \App\Models\Warehouse::class)
        <a href="{{ route('warehouses.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> New Warehouse
        </a>
    @endcan
</div>

<form method="GET" class="row g-2 mb-3">
    <div class="col-md-4">
        <input name="search" value="{{ request('search') }}" class="form-control"
               placeholder="Search name or code">
    </div>
    <div class="col-md-3">
        <select name="type" class="form-select">
            <option value="">All types</option>
            @foreach(\App\Enums\WarehouseType::cases() as $t)
                <option value="{{ $t->value }}" @selected(request('type') === $t->value)>
                    {{ $t->label() }}
                </option>
            @endforeach
        </select>
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
    <div class="col-md-1"><button class="btn btn-outline-secondary w-100">Filter</button></div>
    <div class="col-md-1"><a href="{{ route('warehouses.index') }}" class="btn btn-outline-secondary w-100">Reset</a></div>
</form>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Manager</th>
                    <th>Department</th>
                    <th class="text-center">Addresses</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($warehouses as $w)
                    <tr>
                        <td>
                            <code>{{ $w->code }}</code>
                            @if($w->is_default)
                                <span class="badge text-bg-primary ms-1">Default</span>
                            @endif
                        </td>
                        <td>
                            <div class="fw-semibold">
                                <a href="{{ route('warehouses.show', $w) }}" class="text-decoration-none">
                                    {{ $w->name }}
                                </a>
                            </div>
                            @if($w->description)
                                <div class="text-muted small">{{ $w->description }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="badge text-bg-{{ $w->type->badgeClass() }}">
                                {{ $w->type->label() }}
                            </span>
                        </td>
                        <td>{{ $w->manager?->name ?? '—' }}</td>
                        <td>{{ $w->department?->name ?? '—' }}</td>
                        <td class="text-center">
                            <span class="badge text-bg-light border">{{ $w->addresses_count }}</span>
                        </td>
                        <td>
                            <span class="badge text-bg-{{ $w->status->badgeClass() }}">
                                {{ $w->status->label() }}
                            </span>
                        </td>
                        <td class="text-end">
                            @can('update', $w)
                                <a href="{{ route('warehouses.edit', $w) }}"
                                   class="btn btn-sm btn-outline-primary">Edit</a>
                            @endcan
                            @can('makeDefault', $w)
                                @if(! $w->is_default && $w->type->canBeDefault() && $w->status === \App\Enums\RecordStatus::Active)
                                    <form method="POST" action="{{ route('warehouses.make-default', $w) }}"
                                          class="d-inline">
                                        @csrf @method('PATCH')
                                        <button class="btn btn-sm btn-outline-secondary"
                                                title="Set as default">Make Default</button>
                                    </form>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No warehouses.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $warehouses->links() }}</div>
</x-default-layout>
