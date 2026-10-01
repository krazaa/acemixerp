<x-default-layout>
@section('title', 'New Purchase Order')

@section('toolbar-button')
    <a href="{{ route('procurement.purchase-orders.index') }}" class="btn btn-outline-secondary btn-sm">Back to list</a>
@endsection


<form method="POST" action="{{ route('procurement.purchase-orders.store') }}" novalidate>
    @csrf
    @include('procurement::purchase-orders._form', ['purchaseOrder' => $purchaseOrder])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('procurement.purchase-orders.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit">Save Draft</button>
    </div>
</form>


@push('scripts')
@include('procurement::purchase-orders._form-scripts', ['purchaseOrder' => $purchaseOrder])
@endpush
</x-default-layout>
