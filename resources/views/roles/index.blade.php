<x-default-layout>
@section('title', 'Roles')

@section('toolbar-button')

    @can('create', \App\Models\Role::class)
        <a href="{{ route('roles.create') }}" class="btn btn-sm btn-primary"><i class="ki-outline ki-plus fs-3" aria-hidden="true"></i> New Role</a>
    @endcan

@endsection

<section class="bg-body p-5 p-md-7" aria-label="Role directory">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-4 mb-6">
        <div class="d-flex align-items-center gap-3">
            <h2 class="fs-4 fw-semibold mb-0">Role Directory</h2>
            <span class="badge badge-light-primary">{{ $roles->total() }}</span>
        </div>
        <form method="GET" action="{{ route('roles.index') }}" class="d-flex flex-wrap gap-2" role="search">
            <label for="role-search" class="visually-hidden">Search roles</label>
            <input id="role-search" name="search" value="{{ request('search') }}" type="search" class="form-control form-control-sm w-auto mw-100" placeholder="Search roles">
            <button type="submit" class="btn btn-sm btn-light-primary" title="Search roles" aria-label="Search roles"><i class="ki-outline ki-magnifier fs-3 p-0" aria-hidden="true"></i></button>
            @if(request()->filled('search'))
                <a href="{{ route('roles.index') }}" class="btn btn-sm btn-light" title="Clear search" aria-label="Clear search"><i class="ki-outline ki-cross fs-3 p-0" aria-hidden="true"></i></a>
            @endif
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-row-bordered table-row-gray-200 align-middle gy-5 mb-0">
            <thead>
                <tr class="fw-semibold fs-7 text-muted text-uppercase">
                    <th class="min-w-200px">Role</th>
                    <th>Level</th>
                    <th>Permissions</th>
                    <th>Users</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($roles as $role)
                    <tr>
                        <td style="max-width: 440px; overflow-wrap: anywhere;">
                            <div class="fw-semibold text-gray-900">{{ str($role->name)->replace(['-', '_'], ' ')->title()->replace('Ceo', 'CEO')->replace('Hr ', 'HR ') }}</div>
                            <div class="text-muted fs-8 mt-1">{{ $role->name }}</div>
                            @if($role->description)<div class="text-gray-600 fs-7 mt-2">{{ $role->description }}</div>@endif
                        </td>
                        <td><span class="badge badge-light">{{ $role->level ?? '-' }}</span></td>
                        <td class="fw-semibold">{{ number_format($role->permissions_count) }}</td>
                        <td><span class="badge {{ $role->users_count ? 'badge-light-success' : 'badge-light' }}">{{ number_format($role->users_count) }}</span></td>
                        <td><div class="d-flex justify-content-end gap-2">
                            @can('update', $role)
                                <a href="{{ route('roles.edit', $role) }}" class="btn btn-icon btn-sm btn-light-primary" title="Edit {{ $role->name }}" aria-label="Edit {{ $role->name }}"><i class="ki-outline ki-pencil fs-4" aria-hidden="true"></i></a>
                            @endcan
                            @can('delete', $role)
                                <form method="POST" action="{{ route('roles.destroy', $role) }}" class="d-inline"
                                      onsubmit="return confirm('Delete this role?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-icon btn-sm btn-light-danger" title="Delete {{ $role->name }}" aria-label="Delete {{ $role->name }}"><i class="ki-outline ki-trash fs-4" aria-hidden="true"></i></button>
                                </form>
                            @endcan
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-10">{{ request()->filled('search') ? 'No roles match your search.' : 'No roles defined.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 pt-5 mt-3 border-top">
        <span class="text-muted fs-7">Showing {{ $roles->firstItem() ?? 0 }} to {{ $roles->lastItem() ?? 0 }} of {{ $roles->total() }} roles</span>
        <div>{{ $roles->withQueryString()->links() }}</div>
    </div>
</section>
</x-default-layout>
