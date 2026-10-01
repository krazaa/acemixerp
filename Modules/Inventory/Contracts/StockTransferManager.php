<?php

namespace Modules\Inventory\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Inventory\Data\StockTransferData;
use Modules\Inventory\Models\StockTransfer;

interface StockTransferManager
{
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function create(StockTransferData $data, int $userId): StockTransfer;

    public function submit(StockTransfer $t, int $userId): StockTransfer;

    public function approve(StockTransfer $t, int $userId): StockTransfer;

    public function dispatch(StockTransfer $t, int $userId): StockTransfer;

    public function receive(StockTransfer $t, array $receivedQuantities, int $userId): StockTransfer;

    public function cancel(StockTransfer $t, int $userId, ?string $reason = null): StockTransfer;
}
