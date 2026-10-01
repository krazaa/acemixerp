<x-default-layout>
@section('title', 'Edit Purchase Order - ' . $purchaseOrder->number)

@section('toolbar-button')
    <a href="{{ route('procurement.purchase-orders.show', $purchaseOrder) }}" class="btn btn-sm btn-light-secondary">View</a>
@endsection


<form method="POST" action="{{ route('procurement.purchase-orders.update', $purchaseOrder) }}" novalidate>
    @csrf @method('PUT')
    @include('procurement::purchase-orders._form', ['purchaseOrder' => $purchaseOrder])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('procurement.purchase-orders.show', $purchaseOrder) }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit">Save Changes</button>
    </div>
</form>


@push('scripts')
@include('procurement::purchase-orders._form-scripts', ['purchaseOrder' => $purchaseOrder])
@endpush
</x-default-layout>
