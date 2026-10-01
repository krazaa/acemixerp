<?php

namespace App\Services\MasterData;

use App\Contracts\CategoryManager;
use App\Enums\RecordStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class CategoryService implements CategoryManager
{
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return Category::query()
            ->with('parent:id,code,name')
            ->withCount(['items', 'children'])
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$s}%")
                ->orWhere('code', 'like', "%{$s}%")))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function allActive(): Collection
    {
        return Category::query()->active()->orderBy('name')->get(['id', 'code', 'name']);
    }

    public function create(array $data): Category
    {
        return DB::transaction(fn () => Category::query()->create([
            'code' => $data['code'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'parent_id' => $data['parent_id'] ?? null,
            'status' => $data['status'] ?? RecordStatus::Active,
        ]));
    }

    public function update(Category $category, array $data): Category
    {
        return DB::transaction(function () use ($category, $data) {
            if (array_key_exists('parent_id', $data)
                && $data['parent_id'] !== null
                && (int) $data['parent_id'] !== $category->parent_id) {
                $this->guardCycle($category, (int) $data['parent_id']);
            }

            $category->fill([
                'code' => $data['code'] ?? $category->code,
                'name' => $data['name'] ?? $category->name,
                'description' => $data['description'] ?? $category->description,
                'parent_id' => $data['parent_id'] ?? $category->parent_id,
            ])->save();

            return $category->fresh();
        });
    }

    public function delete(Category $category): void
    {
        if ($category->isReferenced()) {
            throw BusinessRuleException::make(
                'Category is referenced by items or sub-categories. Archive it instead.'
            );
        }

        DB::transaction(fn () => $category->delete());
    }

    public function changeStatus(Category $category, RecordStatus $status): Category
    {
        if (! $category->status->canTransitionTo($status)) {
            throw BusinessRuleException::make(
                "Cannot transition from {$category->status->value} to {$status->value}."
            );
        }

        $category->forceFill(['status' => $status])->save();

        return $category->fresh();
    }

    private function guardCycle(Category $category, int $newParentId): void
    {
        if ($newParentId === $category->id) {
            throw BusinessRuleException::make('A category cannot be its own parent.');
        }
        if (in_array($newParentId, $category->descendantIds(), true)) {
            throw BusinessRuleException::make(
                'Cannot assign a descendant as parent (would create a cycle).'
            );
        }
    }
}
