<x-default-layout>
@section('title', 'Sales Invoices')

@section('toolbar-button')
    @can('create', \Modules\Sales\Models\SalesInvoice::class)
        <a href="{{ route('sales.sales-invoices.create') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg"></i> New Invoice
        </a>
    @endcan
@endsection

<form method="GET" class="row g-2 mb-3">
    <div class="col-md-3"><input name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Number or reference"></div>
    <div class="col-md-1">
        <select name="status" class="form-select form-select-sm">
            <option value="">Statuses</option>
            @foreach(\Modules\Sales\Enums\SalesInvoiceStatus::cases() as $s)
                <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="customer_id" class="form-select form-select-sm" data-control="select2">
            <option value="">Customers</option>
            @foreach($customers as $c)<option value="{{ $c->id }}" @selected(request('customer_id') == $c->id)>{{ $c->name }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-2"><input name="from" type="date" value="{{ request('from') }}" class="form-control form-control-sm"></div>
    <div class="col-md-2"><input name="to"   type="date" value="{{ request('to') }}"   class="form-control form-control-sm"></div>
    <div class="col-md-1"><button class="btn btn-sm btn-light-info w-100">Filter</button></div>
    <div class="col-md-1"><a href="{{ route('sales.sales-invoices.index') }}" class="btn btn-sm btn-outline-secondary w-100">Reset</a></div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-body">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr class="fw-bold fs-6 text-gray-800">
                    <th>Number</th>
                    <th>Date</th>
                    <th>Due</th>
                    <th>Customer</th>
                    <th class="text-end">Total</th>
                    <th class="text-end">Paid</th>
                    <th class="text-end">Outstanding</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $inv)
                    <tr>
                        <td><code>{{ $inv->number }}</code></td>
                        <td>{{ $inv->invoice_date->format('Y-m-d') }}</td>
                        <td class="{{ $inv->daysOverdue() > 0 ? 'text-danger fw-semibold' : '' }}">
                            {{ $inv->due_date->format('Y-m-d') }}
                            @if($inv->daysOverdue() > 0)
                                <div class="small">{{ $inv->daysOverdue() }}d overdue</div>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('customers.show', $inv->customer_id) }}" class="text-decoration-none">
                                {{ $inv->customer?->name }}
                            </a>
                        </td>
                        <td class="text-end">{{ number_format((float) $inv->total, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $inv->paid_amount, 2) }}</td>
                        <td class="text-end {{ bccomp($inv->outstanding(), '0', 4) > 0 ? 'text-warning fw-semibold' : '' }}">
                            {{ number_format((float) $inv->outstanding(), 2) }}
                        </td>
                        <td>
                            <span class="badge badge-{{ $inv->status->badgeClass() }}">{{ $inv->status->label() }}</span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('sales.sales-invoices.show', $inv) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            @can('update', $inv)
                                <a href="{{ route('sales.sales-invoices.edit', $inv) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">No invoices.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    </div>
</div>
<div class="mt-3">{{ $invoices->links() }}</div>
</x-default-layout>
