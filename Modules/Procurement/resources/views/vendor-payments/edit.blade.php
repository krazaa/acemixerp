<x-default-layout>
@section('title', 'Edit Vendor Payment - '. $payment->number)

@section('toolbar-button')
    <a href="{{ route('procurement.vendor-payments.show', $payment) }}" class="btn btn-outline-secondary btn-sm">View</a>
@endsection

@if(! $payment->status->isEditable())
    <div class="alert alert-warning">
        This payment is <strong>{{ $payment->status->label() }}</strong> and cannot be edited.
    </div>
@endif

<form method="POST" action="{{ route('procurement.vendor-payments.update', $payment) }}" novalidate>
    @csrf @method('PUT')

    @include('procurement::vendor-payments._form', [
        'payment'      => $payment,
        'vendors'      => $vendors,
        'bankAccounts' => $bankAccounts,
        'outstandingInvoices' => $outstandingInvoices ?? collect(),
    ])

    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('procurement.vendor-payments.show', $payment) }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit"
                @if(! $payment->status->isEditable()) disabled @endif>
            Save Changes
        </button>
    </div>
</form>


@push('scripts')
@include('procurement::vendor-payments._form-scripts')
@endpush
</x-default-layout>
