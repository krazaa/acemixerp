@extends('layouts.app')
@section('title', 'Edit Stock Transfer')
@section('content')
<h1 class="h3 mb-3">Edit {{ $transfer->number }}</h1>
<form method="POST" action="{{ route('inventory.transfers.update', $transfer) }}" novalidate>
    @csrf @method('PUT')
    @include('inventory::transfers._form', ['transfer' => $transfer])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('inventory.transfers.show', $transfer) }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit">Save Changes</button>
    </div>
</form>
@endsection
@push('scripts')@include('inventory::transfers._form-scripts', ['transfer' => $transfer])@endpush
