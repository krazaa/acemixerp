@extends('layouts.app')
@section('title', 'Permissions')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 m-0">Permissions</h1>
    @can('create', \App\Models\Permission::class)
        <a href="{{ route('permissions.create') }}" class="btn btn-primary">New Permission</a>
    @endcan
</div>

<form method="GET" class="row g-2 mb-3">
    <div class="col-md-4">
        <input name="search" value="{{ request('search') }}" class="form-control" placeholder="Search name or description">
    </div>
    <div class="col-md-3">
        <select name="group" class="form-select">
            <option value="">All groups</option>
            @foreach($groups as $g)
                <option value="{{ $g }}" @selected(request('group') === $g)>{{ $g }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <button class="btn btn-outline-secondary w-100">Filter</button>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Group</th>
                    <th>Roles</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($permissions as $perm)
                    <tr>
                        <td>
                            <code>{{ $perm->name }}</code>
                            <div class="text-muted small">{{ $perm->description }}</div>
                        </td>
                        <td><span class="badge text-bg-secondary">{{ $perm->group }}</span></td>
                        <td>{{ $perm->roles_count }}</td>
                        <td class="text-end">
                            @can('update', $perm)
                                <a href="{{ route('permissions.edit', $perm) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            @endcan
                            @can('delete', $perm)
                                <form method="POST" action="{{ route('permissions.destroy', $perm) }}" class="d-inline"
                                      onsubmit="return confirm('Delete this permission?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">No permissions.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $permissions->links() }}</div>
@endsection
