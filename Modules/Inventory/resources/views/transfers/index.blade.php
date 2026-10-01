<x-default-layout>
@section('title', 'Stock Transfers')

@section('toolbar-button')
 @can('create', \Modules\Inventory\Models\StockTransfer::class)
        <a href="{{ route('inventory.transfers.create') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg"></i> New Transfer
        </a>
    @endcan
    <a href="{{ route('inventory.dashboard') }}" class="btn btn-sm btn-sm btn-secondary btn-sm">
            <i class="fas fa-arrow-left fa-sm"></i> Back
        </a>
@endsection


<form method="GET" class="row g-2 mb-3">
    <div class="col-md-3"><input name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Number"></div>
    <div class="col-md-2">
        <select name="status" class="form-select form-select-sm">
            <option value="">All statuses</option>
            @foreach(\Modules\Inventory\Enums\TransferStatus::cases() as $s)
                <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="from_warehouse_id" class="form-select form-select-sm">
            <option value="">From (any)</option>
            @foreach($warehouses as $w)<option value="{{ $w->id }}" @selected(request('from_warehouse_id') == $w->id)>{{ $w->name }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="to_warehouse_id" class="form-select form-select-sm">
            <option value="">To (any)</option>
            @foreach($warehouses as $w)<option value="{{ $w->id }}" @selected(request('to_warehouse_id') == $w->id)>{{ $w->name }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-1"><button class="btn btn-outline-secondary w-100">Filter</button></div>
    <div class="col-md-1"><a href="{{ route('inventory.transfers.index') }}" class="btn btn-outline-secondary w-100">Reset</a></div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-body">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                	<tr class="fw-bold fs-6 text-gray-800">
                    <th>Number</th><th>Date</th><th>From</th><th>To</th>
                    <th class="text-center">Lines</th><th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transfers as $t)
                    <tr>
                        <td><code>{{ $t->number }}</code></td>
                        <td>{{ $t->transfer_date->format('Y-m-d') }}</td>
                        <td>{{ $t->fromWarehouse?->name }}</td>
                        <td>{{ $t->toWarehouse?->name }}</td>
                        <td class="text-center">{{ $t->lines_count }}</td>
                        <td><span class="badge text-bg-{{ $t->status->badgeClass() }}">{{ $t->status->label() }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('inventory.transfers.show', $t) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            @can('update', $t)
                                <a href="{{ route('inventory.transfers.edit', $t) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No transfers.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    </div>
</div>
<div class="mt-3">{{ $transfers->links() }}</div>
</x-default-layout>
