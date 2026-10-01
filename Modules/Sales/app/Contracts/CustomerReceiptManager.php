<?php

declare(strict_types=1);

namespace Modules\Sales\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Sales\Data\CustomerReceiptData;
use Modules\Sales\Models\CustomerReceipt;

interface CustomerReceiptManager
{
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function create(CustomerReceiptData $data, int $userId): CustomerReceipt;

    public function update(CustomerReceipt $receipt, CustomerReceiptData $data, int $userId): CustomerReceipt;

    public function submit(CustomerReceipt $receipt, int $userId): CustomerReceipt;

    public function approve(CustomerReceipt $receipt, int $userId): CustomerReceipt;

    public function reject(CustomerReceipt $receipt, int $userId, string $reason): CustomerReceipt;

    public function post(CustomerReceipt $receipt, int $userId): CustomerReceipt;

    public function cancel(CustomerReceipt $receipt, int $userId, ?string $reason = null): CustomerReceipt;

    public function reverse(CustomerReceipt $receipt, int $userId, string $reason): CustomerReceipt;

    public function delete(CustomerReceipt $receipt): void;
}
