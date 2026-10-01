<?php

namespace Modules\Procurement\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Procurement\Data\PurchaseRequisitionData;
use Modules\Procurement\Models\PurchaseRequisition;

interface PurchaseRequisitionManager
{
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function create(PurchaseRequisitionData $data, int $userId): PurchaseRequisition;

    public function update(PurchaseRequisition $pr, PurchaseRequisitionData $data, int $userId): PurchaseRequisition;

    public function submit(PurchaseRequisition $pr, int $userId): PurchaseRequisition;

    public function startReview(PurchaseRequisition $pr, int $userId): PurchaseRequisition;

    public function approve(PurchaseRequisition $pr, int $userId): PurchaseRequisition;

    public function reject(PurchaseRequisition $pr, int $userId, string $reason): PurchaseRequisition;

    public function cancel(PurchaseRequisition $pr, int $userId, ?string $reason = null): PurchaseRequisition;

    public function close(PurchaseRequisition $pr, int $userId): PurchaseRequisition;

    public function delete(PurchaseRequisition $pr): void;
}
