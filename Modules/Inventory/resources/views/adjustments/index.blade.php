<x-dynamic-component :component="request()->boolean('print') ? 'inventory-print-layout' : 'default-layout'">

@section('title', 'Stock Adjustments')

@section('toolbar-button')
    <a href="{{ route('inventory.adjustments.index', array_merge(request()->except(['page', 'print']), ['print' => 1])) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">Print / PDF</a>
 @can('create', \Modules\Inventory\Models\StockAdjustment::class)
            <a href="{{ route('inventory.adjustments.create') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg"></i> New Adjustment
            </a>
        @endcan
  <a href="{{ route('inventory.adjustments.index') }}" class="btn btn-sm btn-secondary btn-sm">
            <i class="fas fa-arrow-left fa-sm"></i> Back
        </a>
@endsection

    @if(!request()->boolean('print'))
<form method="GET" class="row g-2 mb-3">
        <div class="col-md-3">
            <input name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Number">
        </div>
        <div class="col-md-2">
            <select name="status" class="form-select form-select-sm">
                <option value="">All statuses</option>
                @foreach(\Modules\Inventory\Enums\AdjustmentStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>
                        {{ $status->label() }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select name="warehouse_id" class="form-select form-select-sm">
                <option value="">All warehouses</option>
                @foreach($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}" @selected(request('warehouse_id') == $warehouse->id)>
                        {{ $warehouse->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-sm btn-light-info w-100">Filter</button>
        </div>
        <div class="col-md-2">
            <a href="{{ route('inventory.adjustments.index') }}" class="btn btn-sm btn-light-secondary w-100">Reset</a>
        </div>
    </form>
@endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr class="fw-bold fs-6 text-gray-800">
                        <th>Number</th>
                        <th>Date</th>
                        <th>Warehouse</th>
                        <th>Reason</th>
                        <th class="text-center">Lines</th>
                        <th>Status</th>
                        @if(!request()->boolean('print'))<th class="text-end">Actions</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($adjustments as $adjustment)
                        <tr>
                            <td><code>{{ $adjustment->number }}</code></td>
                            <td>{{ $adjustment->adjustment_date->format('Y-m-d') }}</td>
                            <td>{{ $adjustment->warehouse?->name }}</td>
                            <td>{{ $adjustment->reason }}</td>
                            <td class="text-center">{{ $adjustment->lines_count }}</td>
                            <td>
                                <span class="badge badge-{{ $adjustment->status->badgeClass() }}">
                                    {{ $adjustment->status->label() }}
                                </span>
                            </td>
                            @if(!request()->boolean('print'))
                            <td class="text-end">
                                <a href="{{ route('inventory.adjustments.show', $adjustment) }}"
                                   class="btn btn-sm btn-outline-secondary">View</a>
                                @can('update', $adjustment)
                                    <a href="{{ route('inventory.adjustments.edit', $adjustment) }}"
                                       class="btn btn-sm btn-outline-primary">Edit</a>
                                @endcan
                            </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No stock adjustments found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>

    @if(!request()->boolean('print'))
<div class="mt-3">{{ $adjustments->links() }}</div>
@endif
</x-dynamic-component>
