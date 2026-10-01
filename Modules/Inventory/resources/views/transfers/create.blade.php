<x-default-layout>
@section('title', 'New Stock Transfer')
<h1 class="h3 mb-3">New Stock Transfer</h1>
<form method="POST" action="{{ route('inventory.transfers.store') }}" novalidate>
    @csrf
    @include('inventory::transfers._form', ['transfer' => $transfer])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('inventory.transfers.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit">Save Draft</button>
    </div>
</form>

@push('scripts')@include('inventory::transfers._form-scripts', ['transfer' => $transfer])@endpush
</x-default-layout>
