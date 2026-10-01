<x-default-layout>
@section('title', 'New Vendor Payment')

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 m-0">New Vendor Payment</h1>
        <div class="text-muted small">
            A draft payment is created. Approve and post it to settle the vendor's outstanding invoices.
        </div>
    </div>
    <a href="{{ route('procurement.vendor-payments.index') }}" class="btn btn-outline-secondary btn-sm">
        Back to list
    </a>
</div>

<form method="POST" action="{{ route('procurement.vendor-payments.store') }}" novalidate>
    @csrf

    @include('procurement::vendor-payments._form', [
        'payment'      => $payment,
        'vendors'      => $vendors,
        'bankAccounts' => $bankAccounts,
        'prefillInvoice' => $prefillInvoice ?? null,
        'outstandingInvoices' => $outstandingInvoices ?? collect(),
    ])

    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('procurement.vendor-payments.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit">Save Draft</button>
    </div>
</form>


@push('scripts')
@include('procurement::vendor-payments._form-scripts')
@endpush
</x-default-layout>
