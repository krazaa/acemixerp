<?php

declare(strict_types=1);

namespace Modules\Inventory\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Inventory\Data\StockAdjustmentData;
use Modules\Inventory\Models\StockAdjustment;

interface StockAdjustmentManager
{
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    /** @return Collection<int, StockAdjustment> */
    public function all(array $filters = []): Collection;

    public function create(StockAdjustmentData $data, int $userId): StockAdjustment;

    public function submit(StockAdjustment $a, int $userId): StockAdjustment;

    public function approve(StockAdjustment $a, int $userId): StockAdjustment;

    public function post(StockAdjustment $a, int $userId): StockAdjustment;

    public function cancel(StockAdjustment $a, int $userId, ?string $reason = null): StockAdjustment;
}
