<x-default-layout>
@section('title', "Warehouse — {$warehouse->name}")

@section('toolbar-button')
    <a href="{{ route('inventory.dashboard') }}" class="btn btn-sm btn-secondary btn-sm">
        <i class="fas fa-arrow-left fa-sm"></i> Back
    </a>
@endsection

        <div class="text-muted small"> Total value: {{ number_format((float) $totalValue, 2) }}</div>


<form method="GET" class="row g-2 mb-3">
    <div class="col-md-4">
        <input name="search" value="{{ request('search') }}" class="form-control form-control-sm"
               placeholder="Search item name or code">
    </div>
    <div class="col-md-2">
        <button class="btn btn-sm btn-light-info w-100">Search</button>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-body">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr class="fw-bold fs-6 text-gray-800">
                    <th>Item</th>
                    <th>Batch Number</th>
                    <th>Expiry</th>
                    <th>Unit</th>
                    <th class="text-end">On Hand</th>
                    <th class="text-end">Value</th>
                    <th class="text-end">Avg Cost</th>
                    <th class="text-end">Reorder</th>
                    <th></th>
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
                        <td><code>{{ $b->batch?->number ?? '—' }}</code></td>
                        <td>{{ $b->batch?->expiry_date?->format('d M Y') ?? '—' }}</td>
                        <td>{{ $b->item?->unit?->code ?? '—' }}</td>
                        <td class="text-end {{ bccomp((string) $b->quantity, '0', 2) < 0 ? 'text-danger fw-semibold' : '' }}">
                            {{ number_format((float) $b->quantity, 4) }}
                        </td>
                        <td class="text-end">{{ number_format((float) $b->total_value, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $b->averageUnitCost(), 2) }}</td>
                        <td class="text-end">{{ number_format((float) $b->item?->reorder_level, 2) }}</td>
                        <td class="text-end">
                            <a href="{{ route('inventory.stock.show', ['item' => $b->item_id, 'warehouse_id' => $warehouse->id]) }}"
                               class="btn btn-sm btn-light-secondary">Movements</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">No stock in this warehouse.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
<div class="mt-3">{{ $balances->links() }}</div>
</x-default-layout>
