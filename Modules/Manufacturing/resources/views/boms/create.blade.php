<x-default-layout>
@section('title', 'New BOM')
@section('toolbar-button')
    <a href="{{ route('manufacturing.boms.index') }}" class="btn btn-sm btn-secondary btn-sm">
        <i class="fas fa-arrow-left fa-sm"></i> Back
    </a>
@endsection

<form method="POST" action="{{ route('manufacturing.boms.store') }}" novalidate>
    @csrf
    @include('manufacturing::boms._form', ['bom' => $bom])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('manufacturing.boms.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit">Create BOM</button>
    </div>
</form>
@push('scripts')
@include('manufacturing::boms._form-scripts', ['bom' => $bom])
@endpush
</x-default-layout>
