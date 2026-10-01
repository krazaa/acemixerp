<x-default-layout>
@section('title', 'Stock Valuation')


@section('toolbar-button')
 <a href="{{ route('inventory.valuation.print', request()->query()) }}" target="_blank" class="btn btn-outline-secondary btn-sm">Print / PDF</a>
        <a href="{{ route('inventory.valuation.csv', request()->query()) }}" class="btn btn-outline-success btn-sm">Export CSV</a>
    <a href="{{ route('inventory.dashboard') }}" class="btn btn-sm btn-secondary btn-sm">
        <i class="fas fa-arrow-left fa-sm"></i> Back
    </a>
@endsection

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>

        <div class="text-muted small">Valuation method: {{ ucwords(str_replace('_', ' ', $method)) }}</div>
    </div>
    <div class="d-flex gap-2 no-print">

    </div>
</div>

<form method="GET" class="row g-2 mb-3">
    <div class="col-md-4">
        <input name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Item name or code">
    </div>
    <div class="col-md-3">
        <select name="warehouse_id" class="form-select form-select-sm">
            <option value="">All warehouses</option>
            @foreach($warehouses as $w)
                <option value="{{ $w->id }}" @selected(request('warehouse_id') == $w->id)>{{ $w->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-sm btn-light-info w-100">Filter</button></div>
    <div class="col-md-2"><a href="{{ route('inventory.valuation') }}" class="btn  btn-sm btn-outline-secondary w-100">Reset</a></div>
</form>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="small text-muted">On Hand</div><div class="h4 mb-0">{{ number_format((float) $summary->total_quantity, 2) }}</div></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="small text-muted">Reserved</div><div class="h4 mb-0">{{ number_format((float) $summary->total_reserved, 2) }}</div></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="small text-muted">Available</div><div class="h4 mb-0">{{ number_format((float) $summary->total_available, 2) }}</div></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="small text-muted">Inventory Value</div><div class="h4 mb-0">{{ number_format((float) $summary->total_value, 2) }}</div></div></div></div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
			    <tr class="fw-bold fs-6 text-gray-800">
                    <th>Item</th>
                    <th>Unit</th>
                    <th class="text-end">On Hand</th>
                    <th class="text-end">Reserved</th>
                    <th class="text-end">Available</th>
                    <th class="text-end">Avg Unit Cost</th>
                    <th class="text-end">Total Value</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>
                            <a href="{{ route('inventory.stock.show', $row->item_id) }}" class="text-decoration-none">
                               {{ $row->item?->name }}
                            </a>
                        </td>
                        <td>{{ $row->item?->unit?->name ?? '—' }}</td>
                        <td class="text-end">{{ number_format((float) $row->total_quantity, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $row->total_reserved, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $row->total_available, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $row->avg_unit_cost, 2) }}</td>
                        <td class="text-end fw-semibold">{{ number_format((float) $row->total_value, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No stock to value.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    </div>
</div>
<div class="mt-3">{{ $rows->links() }}</div>

@push('styles')
<style>@media print { .no-print { display: none !important; } }</style>
@endpush
</x-default-layout>
