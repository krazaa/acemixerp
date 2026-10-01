<?php

namespace App\Http\Controllers;

use App\Contracts\PermissionManager;
use App\Http\Requests\Permissions\StorePermissionRequest;
use App\Http\Requests\Permissions\UpdatePermissionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function __construct(private readonly PermissionManager $permissions) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Permission::class);

        return view('permissions.index', [
            'permissions' => $this->permissions->paginate($request->only(['search', 'group'])),
            'groups' => $this->permissions->grouped()->keys(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Permission::class);

        return view('permissions.create');
    }

    public function store(StorePermissionRequest $request): RedirectResponse
    {
        $permission = $this->permissions->create($request->validated());

        return redirect()
            ->route('permissions.index')
            ->with('status', "Permission {$permission->name} created.");
    }

    public function edit(Permission $permission): View
    {
        $this->authorize('update', $permission);

        return view('permissions.edit', ['permission' => $permission]);
    }

    public function update(UpdatePermissionRequest $request, Permission $permission): RedirectResponse
    {
        $this->permissions->update($permission, $request->validated());

        return redirect()
            ->route('permissions.index')
            ->with('status', 'Permission updated.');
    }

    public function destroy(Permission $permission): RedirectResponse
    {
        $this->authorize('delete', $permission);

        $this->permissions->delete($permission);

        return redirect()
            ->route('permissions.index')
            ->with('status', 'Permission deleted.');
    }
}
