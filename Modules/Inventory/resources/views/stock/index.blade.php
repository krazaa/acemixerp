<x-dynamic-component :component="request()->boolean('print') ? 'inventory-print-layout' : 'default-layout'">
@section('title', 'Stock On-Hand')

@section('toolbar-button')
    <a href="{{ route('inventory.stock.index', array_merge(request()->except(['page', 'print']), ['print' => 1])) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">Print / PDF</a>
    <a href="{{ route('inventory.dashboard') }}" class="btn btn-sm btn-secondary btn-sm">
        <i class="fas fa-arrow-left fa-sm"></i> Back
    </a>
@endsection


<div class="d-flex justify-content-between align-items-center mb-3">

    <div class="text-muted small">Total value: {{ number_format((float) $totalValue, 2) }}</div>
</div>

@if(!request()->boolean('print'))
<form method="GET" class="row g-2 mb-3">
    <div class="col-md-3"><input name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Item name or code"></div>
    <div class="col-md-2">
        <select name="warehouse_id" class="form-select form-select-sm">
            <option value="">All warehouses</option>
            @foreach($warehouses as $w)<option value="{{ $w->id }}" @selected(request('warehouse_id') == $w->id)>{{ $w->name }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-3">
        <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" id="low_stock" name="low_stock" value="1"
                   @checked(request('low_stock'))>
            <label class="form-check-label" for="low_stock">Show low stock only</label>
        </div>
    </div>
    <div class="col-md-2"><button class="btn btn-sm btn-light-info w-100">Filter</button></div>
    <div class="col-md-2"><a href="{{ route('inventory.stock.index') }}" class="btn btn-sm btn-light-secondary w-100">Reset</a></div>
</form>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr class="fw-bold fs-6 text-gray-800">
                    <th>Item</th>
                    <th>Warehouse</th>
                    <th class="text-end">On Hand</th>
                    <th class="text-end">Reserved</th>
                    <th class="text-end">Available</th>
                    <th class="text-end">Value</th>
                    @if(!request()->boolean('print'))<th></th>@endif
                </tr>
            </thead>
            <tbody>
                @forelse($balances as $b)
                    <tr>
                        <td>
                            <a href="{{ route('inventory.stock.show', $b->item_id) }}" class="text-decoration-none">
                                 {{ $b->item?->name }}
                            </a>
                        </td>
                        <td>{{ $b->warehouse?->name }}</td>
                        <td class="text-end {{ bccomp((string) $b->quantity, '0', 2) < 0 ? 'text-danger fw-semibold' : '' }}">
                            {{ number_format((float) $b->quantity, 2) }} {{ $b->item?->unit?->code ?? '—' }}
                        </td>
                        <td class="text-end">{{ number_format((float) $b->reserved_quantity,2) }} {{ $b->item?->unit?->code ?? '—' }}</td>
                        <td class="text-end">{{ number_format((float) $b->availableQuantity(), 2) }} {{ $b->item?->unit?->code ?? '—' }}</td>
                        <td class="text-end">{{ number_format((float) $b->total_value, 2) }}</td>
                        @if(!request()->boolean('print'))
                        <td class="text-end">
                            <a href="{{ route('inventory.stock.show', ['item' => $b->item_id, 'warehouse_id' => $b->warehouse_id]) }}"
                               class="btn btn-sm btn-outline-secondary">Detail</a>
                        </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No stock records.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
@if(!request()->boolean('print'))
<div class="mt-3">{{ $balances->links() }}</div>
@endif
</x-dynamic-component>
