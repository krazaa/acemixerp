<x-default-layout>

@section('title', "Edit {$invoice->number}")

@section('sub-title' )
    Update the draft before matching and approval.
@endsection

@section('toolbar-button')
        <a href="{{ route('procurement.supplier-invoices.show', $invoice) }}" class="btn btn-sm btn-light-secondary btn-sm">
            <i class="fas fa-arrow-left fa-sm"></i>  Back
        </a>
 @endsection

    <form method="POST" action="{{ route('procurement.supplier-invoices.update', $invoice) }}" novalidate>
        @csrf
        @method('PUT')

        @include('procurement::supplier-invoices._form', [
            'invoice' => $invoice,
            'purchaseOrder' => $purchaseOrder,
            'purchaseOrders' => $purchaseOrders,
            'prefilledLines' => [],
        ])

        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('procurement.supplier-invoices.show', $invoice) }}" class="btn btn-outline-secondary">Cancel</a>
            <button class="btn btn-primary" type="submit">Update Draft</button>
        </div>
    </form>

@push('scripts')
    @include('procurement::supplier-invoices._form-scripts', ['invoice' => $invoice])
@endpush
</x-default-layout>
