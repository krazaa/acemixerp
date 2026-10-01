<?php

declare(strict_types=1);

namespace Modules\Procurement\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Procurement\Data\GoodsReceiptData;
use Modules\Procurement\Models\GoodsReceipt;

interface GoodsReceiptManager
{
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function createDraft(GoodsReceiptData $data, int $userId): GoodsReceipt;

    public function update(GoodsReceipt $grn, GoodsReceiptData $data, int $userId): GoodsReceipt;

    public function post(GoodsReceipt $grn, int $userId): GoodsReceipt;

    public function cancel(GoodsReceipt $grn, int $userId, ?string $reason = null): GoodsReceipt;

    public function delete(GoodsReceipt $grn): void;

    /** Cumulative accepted quantity for a PO line across all posted GRNs. */
    public function acceptedQuantityForLine(int $purchaseOrderLineId): string;
}
