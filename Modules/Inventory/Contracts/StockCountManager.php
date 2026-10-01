<?php

declare(strict_types=1);

namespace Modules\Inventory\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Inventory\Data\StockCountData;
use Modules\Inventory\Models\StockCount;

interface StockCountManager
{
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function create(StockCountData $data, int $userId): StockCount;

    public function start(StockCount $c, int $userId): StockCount;

    public function snapshot(StockCount $c): StockCount;

    public function recordCount(StockCount $c, array $counted, int $userId): StockCount;

    public function submit(StockCount $c, int $userId): StockCount;

    public function approve(StockCount $c, int $userId): StockCount;

    public function post(StockCount $c, int $userId): StockCount;

    public function cancel(StockCount $c, int $userId, ?string $reason = null): StockCount;
}
