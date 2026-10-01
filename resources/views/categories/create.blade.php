<x-default-layout>
@section('title', 'New Category')
@section('toolbar-button')

        <a href="{{ route('categories.create') }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>

@endsection


<form method="POST" action="{{ route('categories.store') }}" novalidate>
    @csrf
    @include('categories._form', ['category' => $category, 'parents' => $parents])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('categories.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit">Create Category</button>
    </div>
</form>
</x-default-layout>
