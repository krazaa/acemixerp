<x-dynamic-component :component="request()->boolean('print') ? 'inventory-print-layout' : 'default-layout'">
@section('title', 'Movement Ledger')

@section('toolbar-button')
    <a href="{{ route('inventory.movements.index', array_merge(request()->except(['page', 'print']), ['print' => 1])) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">Print / PDF</a>
    <a href="{{ route('inventory.dashboard') }}" class="btn btn-sm btn-secondary btn-sm">
            <i class="fas fa-arrow-left fa-sm"></i> Back
        </a>
@endsection

@if(!request()->boolean('print'))
<form method="GET" class="row g-2 mb-3">
    <div class="col-md-2">
        <select name="warehouse_id" class="form-select form-select-sm">
            <option value="">All warehouses</option>
            @foreach($warehouses as $w)<option value="{{ $w->id }}" @selected(request('warehouse_id') == $w->id)>{{ $w->name }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="type" class="form-select form-select-sm">
            <option value="">All types</option>
            @foreach($types as $t)<option value="{{ $t->value }}" @selected(request('type') === $t->value)>{{ $t->label() }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-2"><input name="reference" value="{{ request('reference') }}" class="form-control form-control-sm" placeholder="Reference"></div>
    <div class="col-md-2"><input name="from" type="date" value="{{ request('from') }}" class="form-control form-control-sm"></div>
    <div class="col-md-2"><input name="to"   type="date" value="{{ request('to') }}"   class="form-control form-control-sm"></div>
    <div class="col-md-1"><button class="btn btn-sm btn-light-info w-100">Filter</button></div>
    <div class="col-md-1"><a href="{{ route('inventory.movements.index') }}" class="btn btn-sm btn-light-secondary w-100">Reset</a></div>
</form>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                	<tr class="fw-bold fs-6 text-gray-800">
                    <th>When</th>
                    <th>Item</th>
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
                        <td class="small text-muted">{{ $m->occurred_at?->format('Y-m-d H:i') }}</td>
                        <td>
                            <a href="{{ route('inventory.stock.show', $m->item_id) }}" class="text-decoration-none">
                              {{ $m->item?->name }}
                            </a>
                        </td>
                        <td><span class="badge text-bg-light border">{{ $m->type->label() }}</span></td>
                        <td>{{ $m->warehouse?->name }}</td>
                        <td><code>{{ $m->reference }}</code></td>
                        <td class="text-end {{ bccomp((string) $m->quantity, '0', 2) > 0 ? 'text-success' : 'text-danger' }} fw-semibold">
                            {{ number_format((float) $m->quantity, 2) }}
                        </td>
                        <td class="text-end">{{ number_format((float) $m->unit_cost, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $m->balance_quantity, 2) }}</td>
                        <td class="small text-muted">{{ $m->creator?->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">No movements found.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
@if(!request()->boolean('print'))
<div class="mt-3">{{ $movements->links() }}</div>
@endif
</x-dynamic-component>
