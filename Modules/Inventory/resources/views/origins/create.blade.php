<x-default-layout>

@section('title', 'Add Origin')
@section('toolbar-button')
        <a href="{{ route('inventory.origins.index') }}" class="btn btn-sm btn-light-secondary">
            <i class="fa fa-arrow-left"></i> Back
        </a>
    </div>
@endsection

<div class="container-fluid">
@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Please fix the following errors:</strong>

        <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>

        <button type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Close"></button>
    </div>
@endif
    <div class="card">
        <div class="card-header">
                   <h3 class="card-title">Create Origin</h3>
        </div>

        <form action="{{ route('inventory.origins.store') }}" method="POST">

            @csrf

            <div class="card-body">

                @include('inventory::origins._form')

            </div>

            <div class="card-footer d-flex gap-2">

                <button type="submit" class="btn btn-primary">
                    Save Origin
                </button>

                <a href="{{ route('inventory.origins.index') }}"
                   class="btn btn-secondary">
                    Cancel
                </a>

            </div>

        </form>

    </div>

</div>
</x-default-layout>
