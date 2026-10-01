<x-default-layout>
    @section('title', 'Edit User - ' . $user->name)

    @section('toolbar-button')
     

        @can('create', \App\Models\User::class)
            <a href="{{ route('users.index') }}" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left fa-sm"></i> Back
            </a>
        @endcan
    @endsection

    {{-- Success Messages --}}
    <div class="my-auto">
        @if (session('status'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('message'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('message') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
    </div>

    {{-- Edit User Form --}}
    <form method="POST" action="{{ route('users.update', $user) }}">
        @csrf
        @method('PUT')

        @include('users._form', [
            'user' => $user,
            'roles' => $roles,
        ])

        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('users.show', $user) }}" class="btn btn-outline-secondary">
                Cancel
            </a>

            <button type="submit" class="btn btn-primary">
                Save Changes
            </button>
        </div>
    </form>
</x-default-layout>
