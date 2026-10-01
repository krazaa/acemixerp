<x-dynamic-component :component="request()->boolean('print') ? 'inventory-print-layout' : 'default-layout'">
@section('title', 'Low Stock Alerts')

@section('toolbar-button')
    <a href="{{ route('inventory.low-stock', array_merge(request()->except(['page', 'print']), ['print' => 1])) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">Print / PDF</a>
    <a href="{{ route('inventory.dashboard') }}" class="btn btn-sm btn-secondary btn-sm">
        <i class="fas fa-arrow-left fa-sm"></i> Back
    </a>
@endsection


<div class="alert alert-info">
    Items whose on-hand quantity in a warehouse is at or below the item's reorder level.
    Reorder levels are set per item in the item master.
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Item</th>
                    <th>Warehouse</th>
                    <th class="text-end">On Hand</th>
                    <th class="text-end">Reorder Level</th>
                    <th class="text-end">Minimum</th>
                    <th class="text-end">Maximum</th>
                    <th class="text-end">Shortfall</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    @php($shortfall = bcsub((string) $row->item?->reorder_level, (string) $row->quantity, 4))
                    @php($unit = $row->item?->unit?->code ?? '')
                    <tr>
                        <td>
                            <a href="{{ route('inventory.stock.show', $row->item_id) }}" class="text-decoration-none">
                                 {{ $row->item?->name }}
                            </a>
                        </td>
                        <td>{{ $row->warehouse?->name }}</td>
                        <td class="text-end text-warning fw-semibold">{{ number_format((float) $row->quantity, 2) }} {{ $unit }}</td>
                        <td class="text-end">{{ number_format((float) $row->item?->reorder_level, 2) }} {{ $unit }}</td>
                        <td class="text-end">{{ number_format((float) $row->item?->minimum_stock, 2) }} {{ $unit }}</td>
                        <td class="text-end">{{ $row->item?->maximum_stock ? number_format((float) $row->item->maximum_stock, 0) : '—' }} @if($row->item?->maximum_stock > 0){{ $unit }} @endif</td>
                        <td class="text-end text-danger fw-semibold">{{ number_format((float) $shortfall, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No low stock items. All inventory is above reorder thresholds.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    </div>
</div>
@if(!request()->boolean('print'))
<div class="mt-3">{{ $rows->links() }}</div>
@endif
</x-dynamic-component>
