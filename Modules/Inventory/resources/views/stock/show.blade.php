<x-default-layout>
@section('title', $item->name)

@section('toolbar-button')
    <a href="{{ route('inventory.stock.index') }}" class="btn btn-sm btn-secondary btn-sm">
        <i class="fas fa-arrow-left fa-sm"></i> Back
    </a>
@endsection


<div class="d-flex justify-content-between align-items-start mb-3">
    <div>

        <div class="text-muted small">
            Total on-hand: <strong>{{ number_format((float) $totalOnHand, 2) }}</strong>
            · Total value: <strong>{{ number_format((float) $totalValue, 2) }}</strong>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('items.show', $item) }}" class="btn btn-outline-secondary btn-sm">Item Master</a>
        <a href="{{ route('inventory.stock.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header">
        <h3 class="card-title">Balances by Warehouse</h3>
    </div>
    <div class="card-body">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Warehouse</th>
                    <th class="text-end">On Hand</th>
                    <th class="text-end">Reserved</th>
                    <th class="text-end">Available</th>
                    <th class="text-end">Avg Unit Cost</th>
                    <th class="text-end">Value</th>
                </tr>
            </thead>
            <tbody>
                @forelse($balances as $b)
                    <tr>
                        <td>{{ $b->warehouse?->name }}</td>
                        <td class="text-end">{{ number_format((float) $b->quantity, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $b->reserved_quantity, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $b->availableQuantity(), 2) }}</td>
                        <td class="text-end">{{ number_format((float) $b->averageUnitCost(), 2) }}</td>
                        <td class="text-end">{{ number_format((float) $b->total_value, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-3">No balance in any warehouse.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header d-flex justify-content-between">
        <h3 class="card-title">Movement History</h3>
          <div class="card-toolbar">
        <form method="GET" class="d-flex gap-2">
            <input type="hidden" name="item" value="{{ $item->id }}">
            <select name="warehouse_id" class="form-select form-select-sm">
                <option value="">All warehouses</option>
                @foreach($warehouses as $w)
                    <option value="{{ $w->id }}" @selected(request('warehouse_id') == $w->id)>{{ $w->name }}</option>
                @endforeach
            </select>
            <input name="from" type="date" value="{{ request('from') }}" class="form-control form-control-sm">
            <input name="to"   type="date" value="{{ request('to') }}"   class="form-control form-control-sm">
            <button class="btn btn-sm btn-outline-secondary">Filter</button>
        </form>
        </div>
    </div>
    <div class="card-body">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>When</th>
                    <th>Type</th>
                    <th>Warehouse</th>
                    <th>Reference</th>
                    <th class="text-end">Qty</th>
                    <th class="text-end">Unit Cost</th>
                    <th class="text-end">Balance</th>
                    <th>By</th>
                </tr>
            </thead>
            <tbody>
                @forelse($movements as $m)
                    <tr>
                        <td class="small text-muted">{{ $m->occurred_at?->format('d/m/Y H:i') }}</td>
                        <td><span class="badge text-bg-light border">{{ $m->type->label() }}</span></td>
                        <td>{{ $m->warehouse?->name }}</td>
                        <td><code>{{ $m->reference }}</code></td>
                        <td class="text-end {{ bccomp((string) $m->quantity, '0', 2) > 0 ? 'text-success' : 'text-danger' }}">
                            {{ number_format((float) $m->quantity, 2) }}
                        </td>
                        <td class="text-end">{{ number_format((float) $m->unit_cost, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $m->balance_quantity, 2) }}</td>
                        <td class="small text-muted">{{ $m->creator?->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No movements recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    </div>
</div>
<div class="mt-3">{{ $movements->links() }}</div>
</x-default-layout>
