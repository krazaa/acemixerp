<x-default-layout>
@section('title', 'Inventory Dashboard')

@section('toolbar-button')

        @can('inventory.transfer')
            <a href="{{ route('inventory.transfers.create') }}" class="btn btn-outline-primary btn-sm">New Transfer</a>
        @endcan
        @can('inventory.adjust')
            <a href="{{ route('inventory.adjustments.create') }}" class="btn btn-outline-primary btn-sm">New Adjustment</a>
        @endcan
        @can('inventory.count')
            <a href="{{ route('inventory.counts.create') }}" class="btn btn-primary btn-sm">New Stock Count</a>
        @endcan
    <a href="{{ route('inventory.dashboard') }}" class="btn btn-sm btn-secondary btn-sm">
        <i class="fas fa-arrow-left fa-sm"></i> Back
    </a>
@endsection

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small text-uppercase">Stock Value</div>
                <div class="fs-3 fw-semibold">{{ number_format((float) $kpis['total_stock_value'], 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small text-uppercase">Active Items</div>
                <div class="fs-3 fw-semibold">{{ number_format($kpis['total_items']) }}</div>
                <div class="text-muted small">{{ $kpis['total_warehouses'] }} warehouse(s)</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm {{ $kpis['low_stock_items'] > 0 ? 'border-warning' : '' }}">
            <div class="card-body">
                <div class="text-muted small text-uppercase">Low Stock Items</div>
                <div class="fs-3 fw-semibold {{ $kpis['low_stock_items'] > 0 ? 'text-warning' : '' }}">
                    {{ number_format($kpis['low_stock_items']) }}
                </div>
                <a href="{{ route('inventory.low-stock') }}" class="small">View list</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm {{ $kpis['negative_balances'] > 0 ? 'border-danger' : '' }}">
            <div class="card-body">
                <div class="text-muted small text-uppercase">Negative Balances</div>
                <div class="fs-3 fw-semibold {{ $kpis['negative_balances'] > 0 ? 'text-danger' : '' }}">
                    {{ number_format($kpis['negative_balances']) }}
                </div>
                <div class="text-muted small">{{ $kpis['movements_last_24h'] }} movements in 24h</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header">
                        <h3 class="card-title">Low Stock Alerts</h3>

                <div class="card-toolbar">
                <a href="{{ route('inventory.low-stock') }}" class="small">View all</a>
                </div>
            </div>
                <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead >
                        <tr class="fw-bold fs-6 text-gray-800">
                            <th>Item</th><th>Warehouse</th>
                            <th class="text-end">On Hand</th>
                            <th class="text-end">Reorder</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($lowStock as $row)
                            <tr>
                                <td>
                                    <a href="{{ route('inventory.stock.show', $row->item_id) }}"
                                       class="text-decoration-none">

                                        {{ $row->item?->name }}
                                    </a>
                                </td>
                                <td>{{ $row->warehouse?->name }}</td>
                                <td class="text-end text-warning fw-semibold">{{ number_format((float) $row->quantity, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $row->item?->reorder_level, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">No items below reorder level.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header">
                <h3 class="card-title">Recent Movements</h3>
                <div class="card-toolbar">
                    <a href="{{ route('inventory.movements.index') }}" class="small">Full ledger</a>
                </div>
            </div>
            <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr class="fw-bold fs-6 text-gray-800">
                            <th>When</th><th>Item</th><th>Type</th>
                            <th class="text-end">Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentMovements as $m)
                            <tr>
                                <td class="text-muted small">{{ $m->occurred_at?->format('d/m/Y H:i') }}</td>
                                <td>
                                    {{ \Illuminate\Support\Str::limit($m->item?->name, 20) }}
                                </td>
                                <td><span class="badge text-bg-light border">{{ $m->type->label() }}</span></td>
                                <td class="text-end {{ bccomp((string) $m->quantity, '0', 4) > 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format((float) $m->quantity, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">No movements recorded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </div>

    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header">
                <h3 class="card-title">Value by Warehouse</h3>
            </div>
            <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr class="fw-bold fs-6 text-gray-800">
                            <th>Warehouse</th>
                            <th class="text-end">Distinct Items</th>
                            <th class="text-end">Total Quantity</th>
                            <th class="text-end">Total Value</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($warehouseSummary as $row)
                            <tr>
                                <td>{{ $row->warehouse?->name }}</td>
                                <td class="text-end">{{ number_format($row->item_count) }}</td>
                                <td class="text-end">{{ number_format((float) $row->total_qty, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $row->total_value, 2) }}</td>
                                <td class="text-end">
                                    <a href="{{ route('inventory.warehouse', $row->warehouse_id) }}"
                                       class="btn btn-sm btn-outline-secondary">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">No warehouses with stock.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>
</x-default-layout>
