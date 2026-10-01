<x-default-layout>
@section('title', 'New Designation')
@section('toolbar-button')
    <a href="{{ route('designations.index') }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
@endsection

<h1 class="h3 mb-3">New Designation</h1>
<form method="POST" action="{{ route('designations.store') }}">
    @csrf
    @include('designations._form')
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('designations.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary">Create Designation</button>
    </div>
</form>
<x-default-layout>
