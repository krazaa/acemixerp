<x-default-layout>
@section('title', 'Customer Receipts')

@section('toolbar-button')
    @can('create', \Modules\Sales\Models\CustomerReceipt::class)
        <a href="{{ route('sales.customer-receipts.create') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg"></i> New Receipt
        </a>
    @endcan
@endsection

<form method="GET" class="row g-2 mb-3">
    <div class="col-md-2"><input name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Number or reference"></div>
    <div class="col-md-2">
        <select name="status" class="form-select form-select-sm">
            <option value="">All statuses</option>
            @foreach(\Modules\Sales\Enums\CustomerReceiptStatus::cases() as $s)
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
    <div class="col-md-1"><a href="{{ route('sales.customer-receipts.index') }}" class="btn btn-light w-100">Reset</a></div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-body">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr class="fw-bold fs-6 text-gray-800">
                    <th>Number</th><th>Date</th><th>Customer</th><th>Method</th>
                    <th class="text-end">Amount</th>
                    <th class="text-end">Allocated</th>
                    <th class="text-end">Unallocated</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($receipts as $r)
                    <tr>
                        <td><code>{{ $r->number }}</code></td>
                        <td>{{ $r->receipt_date->format('d/mY') }}</td>
                        <td>
                            <a href="{{ route('customers.show', $r->customer_id) }}" class="text-decoration-none">
                                {{ $r->customer?->name }}
                            </a>
                        </td>
                        <td><span class="badge badge-light border">{{ $r->payment_method->label() }}</span></td>
                        <td class="text-end">{{ number_format((float) $r->amount, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $r->allocated_amount, 2) }}</td>
                        <td class="text-end {{ bccomp($r->unallocatedAmount(), '0', 4) > 0 ? 'text-warning fw-semibold' : '' }}">
                            {{ number_format((float) $r->unallocatedAmount(), 2) }}
                        </td>
                        <td><span class="badge badge-{{ $r->status->badgeClass() }}">{{ $r->status->label() }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('sales.customer-receipts.show', $r) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            @can('update', $r)
                                <a href="{{ route('sales.customer-receipts.edit', $r) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">No receipts yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
<div class="mt-3">{{ $receipts->links() }}</div>
</x-default-layout>
