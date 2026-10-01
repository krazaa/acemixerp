<x-default-layout>
@section('title', 'New Purchase Requisition')

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Please fix the following errors:</strong>

        <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>

        <button type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Close"></button>
    </div>
@endif

<form method="POST" action="{{ route('procurement.purchase-requisitions.store') }}" novalidate>
    @csrf
    @include('procurement::purchase-requisitions._form', ['requisition' => $requisition])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('procurement.purchase-requisitions.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit">Save Draft</button>
    </div>
</form>


@push('scripts')
@include('procurement::purchase-requisitions._form-scripts', [
    'requisition' => $requisition,
    'items' => $items, 'units' => $units,
])
@endpush
</x-default-layout>
