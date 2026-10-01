<?php

declare(strict_types=1);

namespace App\Services\Rbac;

use App\Contracts\PermissionManager;
use App\Exceptions\BusinessRuleException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

final class PermissionService implements PermissionManager
{
    public function paginate(array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        return Permission::query()
            ->withCount('roles')
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%");
            }))
            ->when($filters['group'] ?? null, fn ($q, $g) => $q->where('group', $g))
            // ->orderBy('group')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): Permission
    {
        // Enforce the `resource.action` convention (§8).
        if (! preg_match('/^[a-z][a-z0-9_]*\.[a-z][a-z0-9_-]*$/', $data['name'])) {
            throw BusinessRuleException::make(
                'Permission name must follow resource.action format (e.g. journal.post).'
            );
        }

        return DB::transaction(function () use ($data) {
            $permission = Permission::create([
                'name' => $data['name'],
                'guard_name' => 'web',
                // 'group' => $data['group'] ?? 'custom',
                'description' => $data['description'] ?? null,
            ]);

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return $permission;
        });
    }

    public function update(Permission $permission, array $data): Permission
    {
        return DB::transaction(function () use ($permission, $data) {
            // Renaming a permission would break role bindings — only allow
            // description/group edits in the UI; name is immutable (§57).
            $permission->fill([
                'group' => $data['group'] ?? $permission->group,
                'description' => $data['description'] ?? $permission->description,
            ])->save();

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return $permission->fresh();
        });
    }

    public function delete(Permission $permission): void
    {
        if ($permission->roles()->exists()) {
            throw BusinessRuleException::make(
                'Cannot delete a permission assigned to roles. Detach it first.'
            );
        }

        DB::transaction(function () use ($permission) {
            $permission->delete();
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        });
    }

    public function grouped(): Collection
    {
        return Permission::query()
            // ->orderBy('group')
            ->orderBy('name')
            ->get()
            ->groupBy('name');
    }
}
