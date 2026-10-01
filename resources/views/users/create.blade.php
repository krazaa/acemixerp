<x-default-layout>
    @section('title', 'Create User')

    @section('toolbar-button')
        @can('create', \App\Models\User::class)
            <a href="{{ route('users.index') }}" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left fa-sm"></i> Back
            </a>
        @endcan
    @endsection

    <form method="POST" action="{{ route('users.store') }}">
        @csrf

        @include('users._form', [
            'user' => null,
            'roles' => $roles,
        ])

        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">
                Cancel
            </a>

            <button type="submit" class="btn btn-primary">
                Create User
            </button>
        </div>
    </form>
</x-default-layout>
