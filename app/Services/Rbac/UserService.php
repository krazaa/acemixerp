<?php

namespace App\Services\Rbac;

use App\Contracts\UserManager;
use App\Enums\UserStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class UserService implements UserManager
{
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return User::query()
            ->with(['roles:id,name'])
            ->when($filters['search'] ?? null, function ($q, $s) {
                $q->where(function ($q) use ($s) {
                    $q->where('name', 'like', "%{$s}%")
                        ->orWhere('email', 'like', "%{$s}%")
                        ->orWhere('employee_number', 'like', "%{$s}%");
                });
            })
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['role'] ?? null, fn ($q, $r) => $q->whereHas('roles', fn ($q) => $q->where('name', $r)))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'employee_number' => $data['employee_number'] ?? null,
                'phone' => $data['phone'] ?? null,
                'status' => $data['status'] ?? UserStatus::Pending,
                'department_id' => $data['department_id'] ?? null,
                'designation_id' => $data['designation_id'] ?? null,
                'must_change_password' => true,
                'password_changed_at' => null,
            ]);

            if (! empty($data['roles'])) {
                $user->syncRoles($data['roles']);
            }

            return $user->fresh(['roles']);
        });
    }

    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $user->fill([
                'name' => $data['name'] ?? $user->name,
                'email' => $data['email'] ?? $user->email,
                'employee_number' => $data['employee_number'] ?? $user->employee_number,
                'phone' => $data['phone'] ?? $user->phone,
                'department_id' => $data['department_id'] ?? $user->department_id,
                'designation_id' => $data['designation_id'] ?? $user->designation_id,
            ]);

            // Password is only changed via dedicated endpoint.
            if (! empty($data['password'])) {
                $user->password = Hash::make($data['password']);
                $user->password_changed_at = now();
            }

            $user->save();

            if (array_key_exists('roles', $data)) {
                $user->syncRoles($data['roles'] ?? []);
            }

            return $user->fresh(['roles']);
        });
    }

    public function delete(User $user): void
    {
        // Soft-delete keeps FK integrity (§9, §57).
        $user->delete();
    }

    public function changeStatus(User $user, UserStatus $status): User
    {
        if (! $user->status->canTransitionTo($status)) {
            throw BusinessRuleException::make(
                "Cannot transition status from {$user->status->value} to {$status->value}."
            );
        }

        $user->forceFill([
            'status' => $status,
            'failed_login_attempts' => $status === UserStatus::Active ? 0 : $user->failed_login_attempts,
            'locked_until' => $status === UserStatus::Active ? null : $user->locked_until,
        ])->save();

        return $user->fresh();
    }

    public function resetPassword(User $user, string $newPassword, bool $mustChange = true): void
    {
        $user->forceFill([
            'password' => Hash::make($newPassword),
            'password_changed_at' => now(),
            'must_change_password' => $mustChange,
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ])->save();
    }
}
