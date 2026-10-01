<?php

namespace Modules\Sales\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Sales\Data\DeliveryData;
use Modules\Sales\Data\DispatchDeliveryData;
use Modules\Sales\Models\Delivery;
use Modules\Sales\Models\SalesOrder;

interface DeliveryManager
{
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function create(DeliveryData $data, int $userId): Delivery;

    public function createFromSalesOrder(SalesOrder $order, int $userId): Delivery;

    public function update(Delivery $delivery, DeliveryData $data, int $userId): Delivery;

    public function pick(Delivery $delivery, int $userId): Delivery;

    public function dispatch(Delivery $delivery, int $userId, array $dispatchDetails = []): Delivery;

    // public function dispatch(Delivery $delivery, DispatchDeliveryData $data, int $userId): Delivery;
    public function allTransportProviders(): Collection;

    public function markDelivered(Delivery $delivery, int $userId): Delivery;

    public function close(Delivery $delivery, int $userId): Delivery;

    public function cancel(Delivery $delivery, int $userId, ?string $reason = null): Delivery;

    public function delete(Delivery $delivery): void;
}
