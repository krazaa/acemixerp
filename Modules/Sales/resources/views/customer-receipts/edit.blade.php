<x-default-layout>
@section('title', 'Edit Customer Receipt - ' . $receipt->number )

    @section('toolbar-button')
    <a href="{{ route('sales.customer-receipts.show', $receipt) }}" class="btn btn-sm btn-light-primary btn-sm">
        View</a>

        <a href="{{ route('sales.customer-receipts.index') }}" class="btn btn-sm btn-light-secondary btn-sm">
        <i class="fa fa-arrow-left"></i>Back</a>
@endsection

@if(! $receipt->status->isEditable())
    <div class="alert alert-warning">
        This receipt is <strong>{{ $receipt->status->label() }}</strong> and cannot be edited.
    </div>
@endif

<form method="POST" action="{{ route('sales.customer-receipts.update', $receipt) }}" novalidate>
    @csrf @method('PUT')
    @include('sales::customer-receipts._form', [
        'receipt'      => $receipt,
        'customers'    => $customers,
        'bankAccounts' => $bankAccounts,
        'outstandingInvoices' => $outstandingInvoices ?? collect(),
    ])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('sales.customer-receipts.show', $receipt) }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit"
                @if(! $receipt->status->isEditable()) disabled @endif>
            Save Changes
        </button>
    </div>
</form>

@push('scripts')
@include('sales::customer-receipts._form-scripts', ['receipt' => $receipt])
@endpush
</x-default-layout>
