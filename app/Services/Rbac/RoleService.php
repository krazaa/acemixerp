<?php

declare(strict_types=1);

namespace App\Services\Rbac;

use App\Contracts\RoleManager;
use App\Exceptions\BusinessRuleException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class RoleService implements RoleManager
{
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return Role::query()
            ->withCount(['permissions', 'users'])
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            // ->orderBy('level')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): Role
    {
        return DB::transaction(function () use ($data) {
            /** @var Role $role */
            $role = Role::create([
                'name' => $data['name'],
                'guard_name' => 'web',
                'description' => $data['description'] ?? null,
                // 'level'       => $data['level'] ?? 100,
                'is_system' => false,
            ]);

            $role->syncPermissions($data['permissions'] ?? []);

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return $role->fresh('permissions');
        });
    }

    public function update(Role $role, array $data): Role
    {
        // if ($role->isSystemRole()) {
        //     throw BusinessRuleException::make('System roles cannot be modified.');
        // }

        return DB::transaction(function () use ($role, $data) {
            $role->fill([
                'name' => $data['name'] ?? $role->name,
                'description' => $data['description'] ?? $role->description,
                // 'level'       => $data['level'] ?? $role->level,
            ])->save();

            if (array_key_exists('permissions', $data)) {
                $role->syncPermissions($data['permissions'] ?? []);
            }

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return $role->fresh('permissions');
        });
    }

    public function delete(Role $role): void
    {
        if ($role->isSystemRole()) {
            throw BusinessRuleException::make('System roles cannot be deleted.');
        }

        // Spatie prevents deleting a role assigned to a user? No — we enforce here.
        if ($role->users()->exists()) {
            throw BusinessRuleException::make(
                'Cannot delete a role that is assigned to users. Reassign them first.'
            );
        }

        DB::transaction(function () use ($role) {
            $role->syncPermissions([]);
            $role->delete();
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        });
    }

    public function all(): Collection
    {
        return Role::query()->orderBy('id')->orderBy('name')->get();
    }
}
