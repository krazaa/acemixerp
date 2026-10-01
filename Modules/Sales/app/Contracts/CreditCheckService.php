<?php

namespace Modules\Sales\Contracts;

use App\Models\Customer;

interface CreditCheckService
{
    /** Outstanding AR balance for a customer (positive = they owe money). */
    public function outstandingAr(Customer $customer): string;

    /** Open committed amount: confirmed Sales Orders not yet fully invoiced. */
    public function committedAmount(Customer $customer): string;

    /** Total exposure = outstanding AR + committed amount. */
    public function totalExposure(Customer $customer): string;

    /** Available credit = limit - exposure (clamped at zero). */
    public function availableCredit(Customer $customer): string;

    /** Whether the customer can accept an additional $amount. */
    public function canExtend(Customer $customer, string $amount): bool;
}
