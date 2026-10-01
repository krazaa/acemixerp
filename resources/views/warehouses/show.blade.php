<x-default-layout>
@section('title', $warehouse->name)

@section('sub-title')
    <div class="text-muted small">{{ $warehouse->code }} — {{ $warehouse->name }}
        @if($warehouse->is_default)
            <span class="badge badge-primary ms-2">Default</span>
        @endif
        · <span class="badge badge-{{ $warehouse->type->badgeClass() }}">
            {{ $warehouse->type->label() }}
        </span>
    </div>
@endsection

@section('toolbar-button')
    @can('update', $warehouse)
        <a href="{{ route('warehouses.edit', $warehouse) }}" class="btn btn-outline-primary">Edit</a>
    @endcan
    @can('makeDefault', $warehouse)
        @if(! $warehouse->is_default && $warehouse->type->canBeDefault() && $warehouse->status === \App\Enums\RecordStatus::Active)
            <form method="POST" action="{{ route('warehouses.make-default', $warehouse) }}">
                @csrf @method('PATCH')
                <button class="btn btn-outline-secondary">Make Default</button>
            </form>
        @endif
    @endcan
    @can('delete', $warehouse)
        <form method="POST" action="{{ route('warehouses.destroy', $warehouse) }}"
                  onsubmit="return confirm('Delete this warehouse?');">
        @csrf @method('DELETE')
            <button class="btn btn-outline-danger">Delete</button>
        </form>
    @endcan
   <a href="{{ route('warehouses.index') }}" class="btn btn-sm btn-secondary btn-sm">
            <i class="fas fa-arrow-left fa-sm"></i>  Back
        </a>
@endsection



<div class="row g-3">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header">
                <h3 class="card-title">Details</h3>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Status</dt>
                    <dd class="col-sm-8">
                        <span class="badge badge-{{ $warehouse->status->badgeClass() }}">
                            {{ $warehouse->status->label() }}
                        </span>
                    </dd>
                    <dt class="col-sm-4">Manager</dt>
                    <dd class="col-sm-8">{{ $warehouse->manager?->name ?? '—' }}</dd>
                    <dt class="col-sm-4">Department</dt>
                    <dd class="col-sm-8">{{ $warehouse->department?->name ?? '—' }}</dd>
                    <dt class="col-sm-4">Cost Center</dt>
                    <dd class="col-sm-8">{{ $warehouse->costCenter?->name ?? '—' }}</dd>
                    <dt class="col-sm-4">Email</dt>
                    <dd class="col-sm-8">{{ $warehouse->email ?: '—' }}</dd>
                    <dt class="col-sm-4">Phone</dt>
                    <dd class="col-sm-8">{{ $warehouse->phone ?: '—' }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header">
                <h3 class="card-title">Operations</h3>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-6">Allow Negative Stock</dt>
                    <dd class="col-sm-6 text-end">{{ $warehouse->allow_negative_stock ? 'Yes' : 'No' }}</dd>
                    <dt class="col-sm-6">Pickable</dt>
                    <dd class="col-sm-6 text-end">{{ $warehouse->is_pickable ? 'Yes' : 'No' }}</dd>
                    <dt class="col-sm-6">Default</dt>
                    <dd class="col-sm-6 text-end">{{ $warehouse->is_default ? 'Yes' : 'No' }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header">
                <h3 class="card-title">Addresses</h3>
                <div class="card-toolbar">
                <span class="text-muted small">{{ $warehouse->addresses->count() }} on file</span>
                </div>
            </div>
            <div class="card-body">
                @forelse($warehouse->addresses as $addr)
                    <div class="border-bottom pb-3 mb-3">
                        <div class="fw-semibold">
                            {{ $addr->label ?: ucfirst($addr->type) }}
                            @if($addr->is_primary)
                                <span class="badge text-bg-info ms-1">Primary</span>
                            @endif
                        </div>
                        <div class="text-muted small mt-1">{{ $addr->oneLine() }}</div>
                    </div>
                @empty
                    <div class="text-muted">No addresses on file.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header">
                <h3 class="card-title">Audit</h3></div>
            <div class="card-body small text-muted">
                <div>Created: {{ $warehouse->created_at?->format('Y-m-d H:i') }}
                    @if($warehouse->creator) by {{ $warehouse->creator->name }}@endif</div>
                <div>Updated: {{ $warehouse->updated_at?->format('Y-m-d H:i') }}
                    @if($warehouse->updater) by {{ $warehouse->updater->name }}@endif</div>
            </div>
        </div>
    </div>
</div>
</x-default-layout>
