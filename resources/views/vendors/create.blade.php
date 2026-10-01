<x-default-layout>
@section('title', 'New Vendor')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 m-0">New Vendor</h1>
    <a href="{{ route('vendors.index') }}" class="btn btn-dark btn-sm">Back to list</a>
</div>

<form method="POST" action="{{ route('vendors.store') }}" novalidate>
    @csrf
    @include('vendors._form', ['vendor' => $vendor])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('vendors.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit">Create Vendor</button>
    </div>
</form>


@push('scripts')
@include('vendors._form-scripts')
@endpush

</x-default-layout>
