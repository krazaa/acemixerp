<x-default-layout>
@section('title', 'Vendor Invoices')
@section('sub-title', 'Vendor bills managed from Expense.')

@section('toolbar-button')
    <a href="{{ route('expense.index') }}" class="btn btn-sm btn-light-secondary">Expense Claims</a>
    <a href="{{ route('expense.vendor-invoices.create') }}" class="btn  btn-sm btn-primary">+ New Vendor Invoice</a>
@endsection


<div class="card border-0 shadow-sm">
    <div class="card-body">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr class="fw-bold fs-6 text-gray-800">
                    <th>Invoice</th>
                    <th>Vendor</th>
                    <th>Billing Month</th>
                    <th>Date</th>
                    <th class="text-end">Subtotal</th>
                    <th class="text-end">GST</th>
                    <th class="text-end">Withholding Tax</th>
                    <th class="text-end">Payable</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $invoice)
                <tr>
                    <td>
                        <a href="{{ route('expense.vendor-invoices.show', $invoice) }}" class="text-decoration-none"><code>{{ $invoice->number }}</code></a><div class="small text-muted">{{ $invoice->vendor_invoice_number }}</div></td>
                        <td>{{ $invoice->vendor?->name }}</td>
                        <td>{{ $invoice->billing_month }}</td>
                        <td>{{ $invoice->invoice_date?->format('d M Y') }}</td>
                        <td class="text-end">{{ number_format((float) $invoice->subtotal, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $invoice->tax_total, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $invoice->whttax_total, 2) }}</td>
                        <td class="text-end fw-semibold">{{ number_format((float) $invoice->total, 2) }}</td>
                        <td><span class="badge badge-secondary">{{ str($invoice->status)->replace('_', ' ')->title() }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-5">No vendor invoices found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        </div>
        @if($invoices->hasPages())<div class="card-body">{{ $invoices->links() }}</div>@endif</div>
</x-default-layout>
