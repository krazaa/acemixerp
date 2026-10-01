<x-default-layout>
@section('title', 'Edit Warehouse')
@section('sub-title')
<div class="text-muted small">{{ $warehouse->code }} — {{ $warehouse->name }}</div>
@endsection

@section('toolbar-button')
   <a href="{{ route('warehouses.index') }}" class="btn btn-sm btn-secondary btn-sm">
            <i class="fas fa-arrow-left fa-sm"></i>  Back
        </a>
@endsection

<form method="POST" action="{{ route('warehouses.update', $warehouse) }}" novalidate>
    @csrf
    @method('PUT')
    @include('warehouses._form', [
        'warehouse'   => $warehouse,
        'departments' => $departments,
        'managers'    => $managers,
    ])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('warehouses.show', $warehouse) }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit">Save Changes</button>
    </div>
</form>


@push('scripts')
@include('warehouses._form-scripts')
@endpush

</x-default-layout>
