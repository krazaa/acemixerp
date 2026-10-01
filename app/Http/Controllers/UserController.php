<?php

namespace App\Http\Controllers;

use App\Contracts\RoleManager;
use App\Contracts\UserManager;
use App\Enums\UserStatus;
use App\Http\Requests\Users\ChangeUserStatusRequest;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(
        private readonly UserManager $users,
        private readonly RoleManager $roles,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        return view('users.index', [
            'users' => $this->users->paginate($request->only(['search', 'status', 'role'])),
            'roles' => $this->roles->all(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('users.create', [
            'roles' => $this->roles->all(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = $this->users->create($request->validated());

        return redirect()
            ->route('users.show', $user)
            ->with('status', "User {$user->name} created.");
    }

    public function show(User $user): View
    {
        $this->authorize('view', $user);

        $user->load(['roles', 'permissions']);

        return view('users.show', ['user' => $user]);
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('users.edit', [
            'user' => $user->load('roles'),
            'roles' => $this->roles->all(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->users->update($user, $request->validated());

        return redirect()
            ->route('users.show', $user)
            ->with('status', 'User updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $this->users->delete($user);

        return redirect()
            ->route('users.index')
            ->with('status', 'User deleted.');
    }

    public function changeStatus(ChangeUserStatusRequest $request, User $user): RedirectResponse
    {
        $this->users->changeStatus($user, $request->enum('status', UserStatus::class));

        return back()->with('status', 'User status updated.');
    }
}
