<x-default-layout>
@section('title', 'Sales Orders')

@section('toolbar-button')
    @can('create', \Modules\Sales\Models\SalesOrder::class)
        <a href="{{ route('sales.sales-orders.create') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg"></i> New Sales Order
        </a>
    @endcan
@endsection

<form method="GET" class="row g-2 mb-3">
    <div class="col-md-4"><input name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Number or reference"></div>
    <div class="col-md-2">
        <select name="status" class="form-select form-select-sm">
            <option value="">All statuses</option>
            @foreach(\Modules\Sales\Enums\SalesOrderStatus::cases() as $s)
                <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="customer_id" class="form-select form-select-sm" data-control="select2">
            <option value="">All customers</option>
            @foreach($customers as $c)<option value="{{ $c->id }}" @selected(request('customer_id') == $c->id)>{{ $c->name }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-1"><input name="from" type="date" value="{{ request('from') }}" class="form-control form-control-sm"></div>
    <div class="col-md-1"><input name="to"   type="date" value="{{ request('to') }}"   class="form-control form-control-sm"></div>
    <div class="col-md-1"><button class="btn btn-outline-secondary w-100">Filter</button></div>
    <div class="col-md-1"><a href="{{ route('sales.sales-orders.index') }}" class="btn btn-outline-secondary w-100">Reset</a></div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-body">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr class="fw-bold fs-6 text-gray-800">
                    <th>Number</th><th>Order Date</th><th>Customer</th>
                    <th class="text-center">Lines</th>
                    <th class="text-end">Total</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $o)
                    <tr>
                        <td><code>{{ $o->number }}</code></td>
                        <td>{{ $o->order_date->format('Y-m-d') }}</td>
                        <td>
                            <a href="{{ route('customers.show', $o->customer_id) }}" class="text-decoration-none">
                                {{ $o->customer?->name }}
                            </a>
                        </td>
                        <td class="text-center">{{ $o->lines_count }}</td>
                        <td class="text-end">{{ number_format((float) $o->total, 2) }}</td>
                        <td><span class="badge badge-{{ $o->status->badgeClass() }}">{{ $o->status->label() }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('sales.sales-orders.show', $o) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            @can('update', $o)
                                <a href="{{ route('sales.sales-orders.edit', $o) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No sales orders.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    </div>
</div>
<div class="mt-3">{{ $orders->links() }}</div>
</x-default-layout>
