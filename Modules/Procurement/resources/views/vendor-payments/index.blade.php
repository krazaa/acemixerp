<<x-default-layout>
@section('title', 'Vendor Payments')

@section('toolbar-button')

    @can('create', \Modules\Procurement\Models\VendorPayment::class)
        <a href="{{ route('procurement.vendor-payments.create') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg"></i> New Payment
        </a>
    @endcan

        <a href="{{ route('dashboard') }}" class="btn btn-sm btn-secondary">
            <i class="fa fa-arrow-left"></i> Back
        </a>

@endsection

<form method="GET" class="row g-2 mb-3">
    <div class="col-md-4">
        <input name="search" value="{{ request('search') }}" class="form-control form-control-sm"
               placeholder="Payment number or reference">
    </div>
    <div class="col-md-2">
        <select name="status" class="form-select form-select-sm">
            <option value="">All statuses</option>
            @foreach(\Modules\Procurement\Enums\VendorPaymentStatus::cases() as $s)
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
    <div class="col-md-1"><input name="to"   type="date" value="{{ request('to') }}"   class="form-control form-control-sm"></div>
    <div class="col-md-1"><button class="btn btn-light-info w-100">Filter</button></div>
    <div class="col-md-1"><a href="{{ route('procurement.vendor-payments.index') }}" class="btn btn-light-secondary w-100">Reset</a></div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-body">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr class="fw-bold fs-6 text-gray-800">
                    <th>Number</th>
                    <th>Date</th>
                    <th>Vendor</th>
                    <th>Method</th>
                    <th>Bank</th>
                    <th class="text-end">Amount</th>
                    <th class="text-end">Allocated</th>
                    <th class="text-end">Unallocated</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $p)
                    <tr>
                        <td>
                            <code>
                            <a href="{{ route('procurement.vendor-payments.show', $p) }}" class="btn btn-sm btn-active-light">
                            {{ $p->number }}
                        </a>
                            </code>

                        </td>
                        <td>{{ $p->payment_date->format('d/m/Y') }}</td>
                        <td>
                            <a href="{{ route('vendors.show', $p->vendor_id) }}" class="text-decoration-none">
                                {{ $p->vendor?->name }}
                            </a>
                        </td>
                        <td><span class="badge badge-light border">{{ $p->payment_method->label() }}</span></td>
                        <td class="small">{{ $p->bankAccount?->name ?? '—' }}</td>
                        <td class="text-end">{{ number_format((float) $p->amount, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $p->allocated_amount, 2) }}</td>
                        <td class="text-end {{ bccomp($p->unallocatedAmount(), '0', 4) > 0 ? 'text-warning fw-semibold' : '' }}">
                            {{ number_format((float) $p->unallocatedAmount(), 2) }}
                        </td>
                        <td><span class="badge badge-{{ $p->status->badgeClass() }}">{{ $p->status->label() }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('procurement.vendor-payments.show', $p) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            @can('update', $p)
                                <a href="{{ route('procurement.vendor-payments.edit', $p) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center text-muted py-4">No vendor payments yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
<div class="mt-3">{{ $payments->links() }}</div>

</x-default-layout>
