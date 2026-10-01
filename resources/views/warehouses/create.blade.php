<x-default-layout>
@section('title', 'New Warehouse')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 m-0">New Warehouse</h1>
    <a href="{{ route('warehouses.index') }}" class="btn btn-outline-secondary btn-sm">Back to list</a>
</div>

<form method="POST" action="{{ route('warehouses.store') }}" novalidate>
    @csrf
    @include('warehouses._form', [
        'warehouse'   => $warehouse,
        'departments' => $departments,
        'managers'    => $managers,
    ])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('warehouses.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit">Create Warehouse</button>
    </div>
</form>


@push('scripts')
@include('warehouses._form-scripts')
@endpush
</x-default-layout>
