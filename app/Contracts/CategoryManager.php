<?php

namespace App\Contracts;

use App\Enums\RecordStatus;
use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface CategoryManager
{
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function allActive(): Collection;

    public function create(array $data): Category;

    public function update(Category $category, array $data): Category;

    public function delete(Category $category): void;

    public function changeStatus(Category $category, RecordStatus $status): Category;
}
