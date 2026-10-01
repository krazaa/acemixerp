<x-default-layout>
@section('title', 'New Request for Quotation')

@section('sub-title')
     <div class="text-muted small">
            A draft RFQ is created first. Vendors are notified when you issue it.
        </div>
@endsection


@section('toolbar-button')
        <a href="{{ route('procurement.rfqs.index') }}" class="btn btn-sm btn-light-secondary btn-sm">
            <i class="fas fa-arrow-left fa-sm"></i>  Back
        </a>
 @endsection


<form method="POST" action="{{ route('procurement.rfqs.store') }}" novalidate>
    @csrf
    @include('procurement::rfqs._form', ['rfq' => $rfq])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('procurement.rfqs.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit">Save Draft</button>
    </div>
</form>


@push('scripts')
@include('procurement::rfqs._form-scripts', [
    'rfq'     => $rfq,
    'items'   => $items,
    'units'   => $units,
    'vendors' => $vendors,
])
@endpush
</x-default-layout>
