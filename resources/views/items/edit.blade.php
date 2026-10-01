<x-default-layout>
@section('title', 'Edit Item'. $item->name)
@section('toolbar-button')
<a href="{{ route('items.show', $item) }}" class="btn btn-outline-secondary btn-sm">View</a>
    <a href="{{ route('items.index') }}" class="btn btn-sm btn-secondary btn-sm">
            <i class="fas fa-arrow-left fa-sm"></i> Back
        </a>
@endsection


<form method="POST" action="{{ route('items.update', $item) }}"
      enctype="multipart/form-data" novalidate>
    @csrf
    @method('PUT')
    @include('items._form', [
        'item'       => $item,
        'categories' => $categories,
        'units'      => $units,
    ])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('items.show', $item) }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit">Save Changes</button>
    </div>
</form>


@push('scripts')
@include('items._form-scripts')
@endpush

</x-default-layout>
