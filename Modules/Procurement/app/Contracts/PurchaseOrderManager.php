<?php

declare(strict_types=1);

namespace Modules\Procurement\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Procurement\Data\PurchaseOrderData;
use Modules\Procurement\Models\PurchaseOrder;
use Modules\Procurement\Models\RequestForQuotation;

interface PurchaseOrderManager
{
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function create(PurchaseOrderData $data, int $userId): PurchaseOrder;

    public function createFromRfq(RequestForQuotation $rfq, int $userId): PurchaseOrder;

    /** @return Collection<int, PurchaseOrder> */
    public function createOrdersFromRfq(RequestForQuotation $rfq, int $userId): Collection;

    public function update(PurchaseOrder $po, PurchaseOrderData $data, int $userId): PurchaseOrder;

    public function submit(PurchaseOrder $po, int $userId): PurchaseOrder;

    public function approve(PurchaseOrder $po, int $userId): PurchaseOrder;

    public function reject(PurchaseOrder $po, int $userId, string $reason): PurchaseOrder;

    public function issue(PurchaseOrder $po, int $userId): PurchaseOrder;

    public function cancel(PurchaseOrder $po, int $userId, ?string $reason = null): PurchaseOrder;

    public function close(PurchaseOrder $po, int $userId): PurchaseOrder;

    public function delete(PurchaseOrder $po): void;

    public function recalculateReceiptStatus(PurchaseOrder $po): void;
}
