<?php

namespace Modules\Procurement\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Procurement\Data\VendorPaymentData;
use Modules\Procurement\Models\VendorPayment;

interface VendorPaymentManager
{
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function create(VendorPaymentData $data, int $userId): VendorPayment;

    public function update(VendorPayment $payment, VendorPaymentData $data, int $userId): VendorPayment;

    public function submit(VendorPayment $payment, int $userId): VendorPayment;

    public function approve(VendorPayment $payment, int $userId): VendorPayment;

    public function reject(VendorPayment $payment, int $userId, string $reason): VendorPayment;

    public function post(VendorPayment $payment, int $userId): VendorPayment;

    public function cancel(VendorPayment $payment, int $userId, ?string $reason = null): VendorPayment;

    public function delete(VendorPayment $payment): void;

    public function reverse(VendorPayment $payment, int $userId, string $reason): VendorPayment;
}
