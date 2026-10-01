<x-default-layout>

@section('title', 'Purchase Orders')

@section('toolbar-button')
     @can('create', \Modules\Procurement\Models\PurchaseOrder::class)
        <a href="{{ route('procurement.purchase-orders.create') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg"></i> New Purchase Order
        </a>
    @endcan
@endsection


<form method="GET" class="row g-2 mb-3">
    <div class="col-md-2">
        <input name="search" value="{{ request('search') }}" class="form-control form-control-sm"
               placeholder="Number or reference">
    </div>
    <div class="col-md-2">
        <select name="status" class="form-select form-select-sm">
            <option value="">All statuses</option>
            @foreach(\Modules\Procurement\Enums\PurchaseOrderStatus::cases() as $s)
                <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="vendor_id" class="form-select form-select-sm">
            <option value="">All vendors</option>
            @foreach($vendors as $v)
                <option value="{{ $v->id }}" @selected(request('vendor_id') == $v->id)>{{ $v->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-1"><input name="from" type="date" value="{{ request('from') }}" class="form-control form-control-sm"></div>
    <div class="col-md-2">
        <select name="warehouse_id" class="form-select form-select-sm" data-control="select2" aria-label="Warehouse">
            <option value="">All warehouses</option>
            @foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}" @selected(request('warehouse_id') == $warehouse->id)>{{ $warehouse->name }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-1"><input name="to"   type="date" value="{{ request('to') }}"   class="form-control form-control-sm"></div>
    <div class="col-md-1"><button class="btn btn-sm btn-light-info w-100">Filter</button></div>
    <div class="col-md-1"><a href="{{ route('procurement.purchase-orders.index') }}" class="btn btn-outline-secondary w-100">Reset</a></div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-body">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr class="fw-bold fs-6 text-gray-800">
                    <th>Number</th>
                    <th>Order Date</th>
                    <th>Vendor</th>
                    <th>Warehouse</th>
                    <th class="text-center">Lines</th>
                    <th class="text-center">GRNs</th>
                    <th class="text-end">Total</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchaseOrders as $po)
                    <tr>
                        <td><code>{{ $po->number }}</code></td>
                        <td>{{ $po->order_date->format('Y-m-d') }}</td>
                        <td>
                            <a href="{{ route('vendors.show', $po->vendor_id) }}" class="text-decoration-none">
                                {{ $po->vendor?->name }}
                            </a>
                        </td>
                        <td>{{ $po->warehouse?->name ?? '-' }}</td>
                        <td class="text-center">{{ $po->lines_count }}</td>
                        <td class="text-center">{{ $po->goods_receipts_count }}</td>
                        <td class="text-end">{{ number_format((float) $po->total, 2) }}</td>
                        <td><span class="badge badge-{{ $po->status->badgeClass() }}">{{ $po->status->label() }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('procurement.purchase-orders.show', $po) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            @can('update', $po)
                                <a href="{{ route('procurement.purchase-orders.edit', $po) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            @endcan
                            <a href="{{ route('procurement.purchase-orders.print', $po->id) }}"
                                target="_blank"
                                class="btn btn-sm btn-outline-dark">
                                    <i class="fas fa-print me-1"></i>
                                    Print
                                </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">No purchase orders.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
<div class="mt-3">{{ $purchaseOrders->links() }}</div>

</x-default-layout>
