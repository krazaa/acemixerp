<x-default-layout>
@section('title', 'New Customer Receipt')
@section('sub-title')
        <div class="text-muted small">A draft receipt is created. Submit, approve, and post it to settle invoices.</div>
@endsection
@section('toolbar-button')
    <a href="{{ route('sales.customer-receipts.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fa fa-arrow-left"></i>Back to list</a>
@endsection

<form method="POST" action="{{ route('sales.customer-receipts.store') }}" novalidate>
    @csrf
    @include('sales::customer-receipts._form', [
        'receipt'      => $receipt,
        'customers'    => $customers,
        'bankAccounts' => $bankAccounts,
        'outstandingInvoices' => $outstandingInvoices ?? collect(),
    ])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('sales.customer-receipts.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit">Save Draft</button>
    </div>
</form>

@push('scripts')
@include('sales::customer-receipts._form-scripts', ['receipt' => $receipt])
@endpush
</x-default-layout>
