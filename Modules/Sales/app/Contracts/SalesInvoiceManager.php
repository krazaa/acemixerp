<?php

declare(strict_types=1);

namespace Modules\Sales\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Sales\Data\SalesInvoiceData;
use Modules\Sales\Models\SalesInvoice;
use Modules\Sales\Models\SalesOrder;

interface SalesInvoiceManager
{
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function create(SalesInvoiceData $data, int $userId): SalesInvoice;

    public function createFromSalesOrder(SalesOrder $order, int $userId): SalesInvoice;

    public function update(SalesInvoice $invoice, SalesInvoiceData $data, int $userId): SalesInvoice;

    public function match(SalesInvoice $invoice, int $userId): SalesInvoice;

    public function approve(SalesInvoice $invoice, int $userId, bool $overrideMismatch = false): SalesInvoice;

    public function reject(SalesInvoice $invoice, int $userId, string $reason): SalesInvoice;

    public function post(SalesInvoice $invoice, int $userId): SalesInvoice;

    public function cancel(SalesInvoice $invoice, int $userId, ?string $reason = null): SalesInvoice;

    public function reverse(SalesInvoice $invoice, int $userId, string $reason): SalesInvoice;

    public function delete(SalesInvoice $invoice): void;
}
