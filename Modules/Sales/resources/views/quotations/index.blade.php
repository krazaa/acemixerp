<x-default-layout>
@section('title', 'Quotations')

@section('toolbar-button')
     @can('create', \Modules\Sales\Models\Quotation::class)
        <a href="{{ route('sales.quotations.create') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg"></i> New Quotation
        </a>
    @endcan
@endsection


<form method="GET" class="row g-2 mb-3">
    <div class="col-md-4"><input name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Number or reference"></div>
    <div class="col-md-2">
        <select name="status" class="form-select form-select-sm">
            <option value="">All statuses</option>
            @foreach(\Modules\Sales\Enums\QuotationStatus::cases() as $s)
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
    <div class="col-md-1"><input name="from" type="date" value="{{ request('from') }}" class="form-control form-control-sm"></div>
    <div class="col-md-1"><input name="to"   type="date" value="{{ request('to') }}"   class="form-control form-control-sm"></div>
    <div class="col-md-1"><button class="btn btn-light-info w-100">Filter</button></div>
    <div class="col-md-1"><a href="{{ route('sales.quotations.index') }}" class="btn btn-outline-secondary w-100">Reset</a></div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-body">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr class="fw-bold fs-6 text-gray-800">
                    <th>Number</th><th>Date</th><th>Valid Until</th><th>Customer</th>
                    <th class="text-center">Lines</th>
                    <th class="text-end">Total</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($quotations as $q)
                    <tr>
                        <td><code>{{ $q->number }}</code></td>
                        <td>{{ $q->quotation_date->format('d/m/Y') }}</td>
                        <td>{{ $q->valid_until?->format('d/m/Y') ?? '—' }}</td>
                        <td>
                            <a href="{{ route('customers.show', $q->customer_id) }}" class="text-decoration-none">
                                {{ $q->customer?->name }}
                            </a>
                        </td>
                        <td class="text-center">{{ $q->lines_count }}</td>
                        <td class="text-end">{{ number_format((float) $q->total, 2) }}</td>
                        <td><span class="badge badge-{{ $q->status->badgeClass() }}">{{ $q->status->label() }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('sales.quotations.show', $q) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            @can('update', $q)
                                <a href="{{ route('sales.quotations.edit', $q) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No quotations.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
<div class="mt-3">{{ $quotations->links() }}</div>
</x-default-layout>
