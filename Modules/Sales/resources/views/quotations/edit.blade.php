<x-default-layout>
@section('title', 'Edit Quotation - '. $quotation->number)

@section('toolbar-button')
    <a href="{{ route('sales.quotations.show', $quotation) }}" class="btn btn-outline-secondary btn-sm">View</a>
@endsection

<form method="POST" action="{{ route('sales.quotations.update', $quotation) }}" novalidate>
    @csrf @method('PUT')
    @include('sales::quotations._form', ['quotation' => $quotation])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('sales.quotations.show', $quotation) }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit">Save Changes</button>
    </div>
</form>

@push('scripts')
@include('sales::quotations._form-scripts', ['quotation' => $quotation, 'items' => $items, 'units' => $units])
@endpush
</x-default-layout>
