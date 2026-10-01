<?php

declare(strict_types=1);

namespace Modules\Manufacturing\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Manufacturing\Data\ProductionOrderData;
use Modules\Manufacturing\Models\ProductionOrder;

interface ProductionOrderManager
{
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function create(ProductionOrderData $data, int $userId): ProductionOrder;

    public function plan(ProductionOrder $order, int $userId): ProductionOrder;

    public function release(ProductionOrder $order, int $userId): ProductionOrder;

    public function start(ProductionOrder $order, int $userId): ProductionOrder;

    public function complete(ProductionOrder $order, string $producedQuantity, int $userId): ProductionOrder;

    public function close(ProductionOrder $order, int $userId): ProductionOrder;

    public function cancel(ProductionOrder $order, int $userId, ?string $reason = null): ProductionOrder;

    public function update(ProductionOrder $order, ProductionOrderData $data, int $userId): ProductionOrder;
}
