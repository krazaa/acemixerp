<x-default-layout>
@section('title', 'Edit Sales Invoice -  '. $invoice->number)

@section('toolbar-button')
    <a href="{{ route('sales.sales-invoices.show', $invoice) }}" class="btn btn-outline-secondary btn-sm">View</a>
@endsection

@if(! $invoice->status->isEditable())
    <div class="alert alert-warning">
        This invoice is <strong>{{ $invoice->status->label() }}</strong> and cannot be edited.
    </div>
@endif

<form method="POST" action="{{ route('sales.sales-invoices.update', $invoice) }}" novalidate>
    @csrf @method('PUT')
    @include('sales::sales-invoices._form', ['invoice' => $invoice])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('sales.sales-invoices.show', $invoice) }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit"
                @if(! $invoice->status->isEditable()) disabled @endif>
            Save Changes
        </button>
    </div>
</form>

@push('scripts')
@include('sales::sales-invoices._form-scripts', ['invoice' => $invoice])
@endpush

</x-default-layout>
