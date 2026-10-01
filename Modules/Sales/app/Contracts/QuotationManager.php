<?php

namespace Modules\Sales\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Sales\Data\QuotationData;
use Modules\Sales\Models\Quotation;

interface QuotationManager
{
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function create(QuotationData $data, int $userId): Quotation;

    public function update(Quotation $quotation, QuotationData $data, int $userId): Quotation;

    public function send(Quotation $quotation, int $userId): Quotation;

    public function accept(Quotation $quotation, int $userId): Quotation;

    public function reject(Quotation $quotation, int $userId, ?string $reason = null): Quotation;

    public function cancel(Quotation $quotation, int $userId, ?string $reason = null): Quotation;

    public function expireOverdue(?int $userId = null): int; // returns count

    public function delete(Quotation $quotation): void;
}
