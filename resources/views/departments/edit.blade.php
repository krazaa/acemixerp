<x-default-layout>
@section('title', 'Edit Department - '.  $department->name)

@section('toolbar-button')

   <a href="{{ route('departments.index') }}" class="btn btn-sm btn-secondary btn-sm">
        <i class="fas fa-arrow-left fa-sm"></i> Back
    </a>
@endsection

<form method="POST" action="{{ route('departments.update', $department) }}">
    @csrf @method('PUT')
    @include('departments._form')
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('departments.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary">Save Changes</button>
    </div>
</form>
</x-default-layout>
