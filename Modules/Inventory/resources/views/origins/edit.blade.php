<x-default-layout>

@section('title', 'Origin Edit - ' . $origin->name)

@section('toolbar-button')
        <a href="{{ route('inventory.origins.index') }}" class="btn btn-sm btn-light-secondary">
            <i class="fa fa-arrow-left"></i> Back
        </a>
    </div>
@endsection


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
        <div class="card-body">
        <form
            action="{{ route('inventory.origins.update', $origin) }}"
            method="POST">

            @csrf
            @method('PUT')
            <div class="card-body">

                @include('inventory::origins._form')

            </div>

            <div class="card-footer d-flex gap-2">

                <button type="submit" class="btn btn-primary">
                    Update Origin
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
