<x-default-layout>
@section('title', 'Edit Unit')
@section('sub-title')
    <div class="text-muted small">{{ $unit->code }} — {{ $unit->name }}</div>
@endsection

@section('toolbar-button')
  <a href="{{ route('units.index') }}" class="btn btn-sm btn-secondary">
        <i class="fas fa-arrow-left fa-sm"></i>Back</a>
@endsection


<form method="POST" action="{{ route('units.update', $unit) }}" novalidate>
    @csrf
    @method('PUT')
    @include('units._form', ['unit' => $unit])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('units.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit">Save Changes</button>
    </div>
</form>
</x-default-layout>
