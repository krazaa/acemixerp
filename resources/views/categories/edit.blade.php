<x-default-layout>
@section('title', 'Edit Category - '. $category->name)

@section('toolbar-button')
        <a href="{{ route('categories.index') }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left"></i> New Bank
        </a>

@endsection

<form method="POST" action="{{ route('categories.update', $category) }}" novalidate>
    @csrf
    @method('PUT')
    @include('categories._form', ['category' => $category, 'parents' => $parents])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('categories.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit">Save Changes</button>
    </div>
</form>
</x-default-layout>
