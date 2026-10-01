<x-default-layout>
@section('title', 'New Item')

@section('toolbar-button')
    <a href="{{ route('items.index') }}" class="btn btn-sm btn-secondary btn-sm">
            <i class="fas fa-arrow-left fa-sm"></i> Back
        </a>
@endsection

<form method="POST" action="{{ route('items.store') }}"
      enctype="multipart/form-data" novalidate>
    @csrf
    @include('items._form', [
        'item'       => $item,
        'categories' => $categories,
        'units'      => $units,
    ])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('items.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit">Create Item</button>
    </div>
</form>

@push('scripts')
@include('items._form-scripts')
@endpush
</x-default-layout>
