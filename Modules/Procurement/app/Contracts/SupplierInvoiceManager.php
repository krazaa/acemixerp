<?php

declare(strict_types=1);

namespace Modules\Procurement\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Procurement\Data\SupplierInvoiceData;
use Modules\Procurement\Models\SupplierInvoice;

interface SupplierInvoiceManager
{
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function create(SupplierInvoiceData $data, int $userId): SupplierInvoice;

    public function update(SupplierInvoice $invoice, SupplierInvoiceData $data, int $userId): SupplierInvoice;

    public function match(SupplierInvoice $invoice, int $userId): SupplierInvoice;

    public function approve(SupplierInvoice $invoice, int $userId, bool $overrideMismatch = false): SupplierInvoice;

    public function reject(SupplierInvoice $invoice, int $userId, string $reason): SupplierInvoice;

    public function post(SupplierInvoice $invoice, int $userId): SupplierInvoice;

    public function cancel(SupplierInvoice $invoice, int $userId, ?string $reason = null): SupplierInvoice;

    public function delete(SupplierInvoice $invoice): void;
}
