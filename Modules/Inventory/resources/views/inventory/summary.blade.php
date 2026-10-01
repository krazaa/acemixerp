<x-default-layout>
@section('title', 'Inventory Summary')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 m-0">Inventory Summary</h1>
        <div class="text-muted small">Current stock position by warehouse</div>
    </div>
    <div class="d-flex gap-2 no-print">
        <a href="{{ route('inventory.summary.print', request()->query()) }}" target="_blank" class="btn btn-outline-secondary btn-sm">Print / PDF</a>
        <a href="{{ route('inventory.summary.csv', request()->query()) }}" class="btn btn-outline-success btn-sm">Export CSV</a>
        <a href="{{ route('inventory.dashboard') }}" class="btn btn-outline-secondary btn-sm">Back to Dashboard</a>
    </div>
</div>

<form method="GET" class="row g-2 mb-3 no-print">
    <div class="col-md-4">
        <select name="warehouse_id" class="form-select">
            <option value="">All warehouses</option>
            @foreach($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}" @selected(request('warehouse_id') == $warehouse->id)>{{ $warehouse->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-outline-secondary w-100">Filter</button></div>
    <div class="col-md-2"><a href="{{ route('inventory.summary') }}" class="btn btn-outline-secondary w-100">Reset</a></div>
</form>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="small text-muted">Warehouse Item Records</div><div class="h4 mb-0">{{ number_format((int) $totals->item_count) }}</div></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="small text-muted">On Hand</div><div class="h4 mb-0">{{ number_format((float) $totals->total_quantity, 2) }}</div></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="small text-muted">Available</div><div class="h4 mb-0">{{ number_format((float) $totals->total_available, 2) }}</div></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="small text-muted">Inventory Value</div><div class="h4 mb-0">{{ number_format((float) $totals->total_value, 2) }}</div></div></div></div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light"><tr><th>Warehouse</th><th class="text-end">Distinct Items</th><th class="text-end">On Hand</th><th class="text-end">Reserved</th><th class="text-end">Available</th><th class="text-end">Inventory Value</th><th></th></tr></thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>{{ $row->warehouse?->code }} — {{ $row->warehouse?->name }}</td>
                        <td class="text-end">{{ number_format((int) $row->item_count) }}</td>
                        <td class="text-end">{{ number_format((float) $row->total_quantity, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $row->total_reserved, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $row->total_available, 2) }}</td>
                        <td class="text-end fw-semibold">{{ number_format((float) $row->total_value, 2) }}</td>
                        <td class="text-end"><a href="{{ route('inventory.warehouse', $row->warehouse_id) }}" class="btn btn-sm btn-outline-secondary">View Stock</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No warehouse stock records.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $rows->links() }}</div>

@push('styles')
<style>@media print { .no-print { display: none !important; } }</style>
@endpush
</x-default-layout>
