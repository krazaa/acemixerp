<x-default-layout>
@section('title', 'Edit Role' .$role->name)
@section('toolbar-button')

      <a href="{{ route('roles.index') }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left fa-sm"></i>
            Back
        </a>
@endsection

<form method="POST" action="{{ route('roles.update', $role) }}">
    @csrf @method('PUT')
    @include('roles._form', ['role' => $role, 'permissionGroups' => $permissionGroups])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary">Save Changes</button>
    </div>
</form>
</x-default-layout>
