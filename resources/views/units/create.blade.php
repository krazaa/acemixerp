<x-default-layout>
@section('title', 'New Unit')

@section('toolbar-button')
  <a href="{{ route('units.index') }}" class="btn btn-sm btn-secondary">
        <i class="fas fa-arrow-left fa-sm"></i>Back</a>
@endsection

<form method="POST" action="{{ route('units.store') }}" novalidate>
    @csrf
    @include('units._form', ['unit' => $unit])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('units.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit">Create Unit</button>
    </div>
</form>
</x-default-layout>
