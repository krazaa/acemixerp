<x-default-layout>
@section('title', 'Supplier Invoices')

@section('toolbar-button')
     @can('create', \Modules\Procurement\Models\SupplierInvoice::class)
        <a href="{{ route('procurement.supplier-invoices.create') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg"></i> New Supplier Invoice
        </a>
    @endcan
@endsection

<form method="GET" class="row g-2 mb-3">
    <div class="col-md-4"><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Our number or vendor's reference"></div>
    <div class="col-md-2">
        <select name="status" class="form-select">
            <option value="">All statuses</option>
            @foreach(\Modules\Procurement\Enums\SupplierInvoiceStatus::cases() as $s)
                <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="vendor_id" class="form-select">
            <option value="">All vendors</option>
            @foreach($vendors as $v)<option value="{{ $v->id }}" @selected(request('vendor_id') == $v->id)>{{ $v->name }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-1"><input name="from" type="date" value="{{ request('from') }}" class="form-control form-control-sm"></div>
    <div class="col-md-1"><input name="to"   type="date" value="{{ request('to') }}"   class="form-control form-control-sm"></div>
    <div class="col-md-1"><button class="btn btn-outline-secondary w-100">Filter</button></div>
    <div class="col-md-1"><a href="{{ route('procurement.supplier-invoices.index') }}" class="btn btn-outline-secondary w-100">Reset</a></div>
</form>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Number</th><th>Vendor Ref</th><th>Date</th><th>Vendor</th><th>PO</th>
                    <th class="text-end">WHT</th><th class="text-end">Payable</th><th>Status</th><th>Match</th><th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $si)
                    <tr>
                        <td><code>{{ $si->number }}</code></td>
                        <td>{{ $si->vendor_invoice_number }}</td>
                        <td>{{ $si->invoice_date->format('Y-m-d') }}</td>
                        <td>{{ $si->vendor?->name }}</td>
                        <td>
                            @if($si->purchase_order_id)
                                <a href="{{ route('procurement.purchase-orders.show', $si->purchase_order_id) }}">
                                    {{ $si->purchaseOrder?->number }}
                                </a>
                            @else
                                <span class="text-muted">Standalone</span>
                            @endif
                        </td>
                        <td class="text-end text-danger">{{ number_format((float) $si->wht_tax_total, 4) }}</td>
                        <td class="text-end">{{ number_format((float) $si->total, 4) }}</td>
                        <td><span class="badge text-bg-{{ $si->status->badgeClass() }}">{{ $si->status->label() }}</span></td>
                        <td>
                            <span class="badge text-bg-{{ $si->match_status === 'matched' ? 'success' : ($si->match_status === 'mismatch' ? 'danger' : 'secondary') }}">
                                {{ ucfirst($si->match_status) }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('procurement.supplier-invoices.show', $si) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            @can('update', $si)
                                <a href="{{ route('procurement.supplier-invoices.edit', $si) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center text-muted py-4">No supplier invoices.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $invoices->links() }}</div>
</x-default-layout>
