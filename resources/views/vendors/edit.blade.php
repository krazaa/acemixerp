{{-- @extends('layouts.app') --}}
<x-default-layout>
@section('title', 'Edit Vendor - '.  $vendor->name)
@section('toolbar-button')

    <a href="{{ route('vendors.show', $vendor) }}" class="btn btn-outline-secondary btn-sm">View</a>
  <a href="{{ route('vendors.index') }}" class="btn btn-sm btn-secondary btn-sm">
        <i class="fas fa-arrow-left fa-sm"></i> Back
        </a>
@endsection

<form method="POST" action="{{ route('vendors.update', $vendor) }}" novalidate>
    @csrf
    @method('PUT')
    @include('vendors._form', ['vendor' => $vendor])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('vendors.show', $vendor) }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit">Save Changes</button>
    </div>
</form>
{{-- @endsection --}}

@push('scripts')
@include('vendors._form-scripts')
@endpush
</x-default-layout>
