<?php

namespace App\Http\Controllers;

use App\Contracts\PermissionManager;
use App\Contracts\RoleManager;
use App\Http\Requests\Roles\StoreRoleRequest;
use App\Http\Requests\Roles\UpdateRoleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct(
        private readonly RoleManager $roles,
        private readonly PermissionManager $permissions,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Role::class);

        return view('roles.index', [
            'roles' => $this->roles->paginate($request->only('search')),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Role::class);

        return view('roles.create', [
            'permissionGroups' => $this->permissions->grouped(),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = $this->roles->create($request->validated());

        return redirect()
            ->route('roles.index')
            ->with('status', "Role {$role->name} created.");
    }

    public function edit(Role $role): View
    {
        $this->authorize('update', $role);

        return view('roles.edit', [
            'role' => $role->load('permissions'),
            'permissionGroups' => $this->permissions->grouped(),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $this->roles->update($role, $request->validated());

        return redirect()
            ->route('roles.index')
            ->with('status', 'Role updated.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('delete', $role);

        $this->roles->delete($role);

        return redirect()
            ->route('roles.index')
            ->with('status', 'Role deleted.');
    }
}
