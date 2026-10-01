<?php

namespace Modules\Procurement\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Procurement\Data\RfqData;
use Modules\Procurement\Models\RequestForQuotation;

interface RfqManager
{
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function create(RfqData $data, int $userId): RequestForQuotation;

    public function update(RequestForQuotation $rfq, RfqData $data, int $userId): RequestForQuotation;

    public function issue(RequestForQuotation $rfq, int $userId): RequestForQuotation;

    public function startReceiving(RequestForQuotation $rfq, int $userId): RequestForQuotation;

    public function cancel(RequestForQuotation $rfq, int $userId, ?string $reason = null): RequestForQuotation;

    public function close(RequestForQuotation $rfq, int $userId): RequestForQuotation;

    public function delete(RequestForQuotation $rfq): void;
}
