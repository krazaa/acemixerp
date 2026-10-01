<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Customer;
use App\Models\Vendor;
use Illuminate\Support\Collection;

interface PartyLedgerService
{
    /** @return Collection<int, object> */
    public function forCustomer(Customer $customer, ?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): Collection;

    public function customerBalance(Customer $customer): string;

    /** @return Collection<int, object> */
    public function forVendor(Vendor $vendor, ?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): Collection;

    /** @return array{lines: Collection<int, object>, opening_balance: string, total_debits: string, total_credits: string, total_invoices: string, total_payments: string, total_wht: string, total_adjustments: string, closing_balance: string} */
    public function vendorStatement(Vendor $vendor, \DateTimeInterface $from, \DateTimeInterface $to): array;

    public function vendorBalance(Vendor $vendor): string;

    /** Open-item statement: unpaid invoices with allocation status. */
    public function openItemsForCustomer(Customer $customer): Collection;

    public function openItemsForVendor(Vendor $vendor): Collection;
}
