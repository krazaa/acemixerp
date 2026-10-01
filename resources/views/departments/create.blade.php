<x-default-layout>
@section('title', 'New Department')

@section('toolbar-button')

   <a href="{{ route('departments.index') }}" class="btn btn-sm btn-secondary btn-sm">
        <i class="fas fa-arrow-left fa-sm"></i> Back
    </a>
@endsection
<form method="POST" action="{{ route('departments.store') }}">
    @csrf
    @include('departments._form')
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('departments.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary">Create Department</button>
    </div>
</form>
</x-default-layout>
