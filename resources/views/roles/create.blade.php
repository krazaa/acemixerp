<x-default-layout>
    @section('title', 'Create Role')

    @section('toolbar-button')
        <a href="{{ route('roles.index') }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left fa-sm"></i>
            Back
        </a>
    @endsection

    <form method="POST" action="{{ route('roles.store') }}">
        @csrf

        @include('roles._form', [
            'role' => null,
            'permissionGroups' => $permissionGroups,
        ])

        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">
                Cancel
            </a>

            <button type="submit" class="btn btn-primary">
                Create Role
            </button>
        </div>
    </form>
</x-default-layout>
