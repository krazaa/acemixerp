<x-default-layout>
@section('title', 'Deliveries')

@section('toolbar-button')
    @can('create', \Modules\Sales\Models\Delivery::class)
        <a href="{{ route('sales.deliveries.create') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg"></i> New Delivery
        </a>
    @endcan
@endsection

<form method="GET" class="row g-2 mb-3">
    <div class="col-md-3"><input name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Number, reference, or tracking"></div>
    <div class="col-md-1">
        <select name="status" class="form-select form-select-sm">
            <option value="">Statuses</option>
            @foreach(\Modules\Sales\Enums\DeliveryStatus::cases() as $s)
                <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="customer_id" class="form-select form-select-sm">
            <option value="">All customers</option>
            @foreach($customers as $c)<option value="{{ $c->id }}" @selected(request('customer_id') == $c->id)>{{ $c->name }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-2"><input name="from" type="date" value="{{ request('from') }}" class="form-control form-control-sm"></div>
    <div class="col-md-2"><input name="to"   type="date" value="{{ request('to') }}"   class="form-control form-control-sm"></div>
    <div class="col-md-1"><button class="btn btn-light-info w-100">Filter</button></div>
    <div class="col-md-1"><a href="{{ route('sales.deliveries.index') }}" class="btn btn-outline-secondary w-100">Reset</a></div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-body">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Number</th><th>Date</th><th>SO</th><th>Customer</th>
                    <th>Warehouse</th>
                    <th class="text-center">Lines</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($deliveries as $d)
                    <tr>
                        <td><code>{{ $d->number }}</code></td>
                        <td>{{ $d->delivery_date->format('d/m/Y') }}</td>
                        <td>
                            <a href="{{ route('sales.sales-orders.show', $d->sales_order_id) }}" class="text-decoration-none">
                                {{ $d->salesOrder?->number }}
                            </a>
                        </td>
                        <td>{{ $d->customer?->name }}</td>
                        <td>{{ $d->warehouse?->name }}</td>
                        <td class="text-center">{{ $d->lines_count }}</td>
                        <td><span class="badge badge-{{ $d->status->badgeClass() }}">{{ $d->status->label() }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('sales.deliveries.show', $d) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            @can('update', $d)
                                <a href="{{ route('sales.deliveries.edit', $d) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            @endcan
                            <a href="{{ route('sales.deliveries.note', $d) }}"
                                target="_blank"
                                class="btn btn-sm btn-outline-secondary"
                                title="Print delivery note">
                                    <i class="bi bi-printer"></i>
                                </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No deliveries.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
<div class="mt-3">{{ $deliveries->links() }}</div>
</x-default-layout>
