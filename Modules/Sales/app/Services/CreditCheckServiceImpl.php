<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Modules\Sales\Contracts\CreditCheckService;
use Modules\Sales\Enums\SalesOrderStatus;
use Modules\Sales\Models\SalesOrder;

final class CreditCheckServiceImpl implements CreditCheckService
{
    public function outstandingAr(Customer $customer): string
    {
        // Sum of posted journal lines tagged to this customer on the AR control account.
        // Debit increases AR; credit decreases.
        $row = DB::table('journal_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_entries.status', 'posted')
            ->where('journal_lines.customer_id', $customer->id)
            ->first([
                DB::raw('COALESCE(SUM(journal_lines.debit), 0)  AS d'),
                DB::raw('COALESCE(SUM(journal_lines.credit), 0) AS c'),
            ]);

        return bcsub((string) $row->d, (string) $row->c, 4);
    }

    public function committedAmount(Customer $customer): string
    {
        // Confirmed Sales Orders not yet fully invoiced.
        // "Committed" means the goods will be delivered and invoiced; the customer
        // has committed to paying. Subtract anything already invoiced.
        $total = '0.0000';

        $orders = SalesOrder::query()
            ->where('customer_id', $customer->id)
            ->whereIn('status', [
                SalesOrderStatus::Confirmed->value,
                SalesOrderStatus::PartiallyDelivered->value,
            ])
            ->with('lines')
            ->get();

        foreach ($orders as $order) {
            foreach ($order->lines as $line) {
                // Open quantity not yet invoiced
                $open = bcsub((string) $line->quantity, (string) $line->invoiced_quantity, 4);
                if (bccomp($open, '0', 4) <= 0) {
                    continue;
                }

                // Convert open qty to a monetary amount using the line's unit price * (1 + tax)
                $lineValue = bcmul($open, (string) $line->unit_price, 4);
                $taxMult = bcadd('1', bcdiv((string) $line->tax_rate, '100', 8), 8);
                $gross = bcmul($lineValue, $taxMult, 4);

                $total = bcadd($total, $gross, 4);
            }
        }

        return $total;
    }

    public function totalExposure(Customer $customer): string
    {
        return bcadd($this->outstandingAr($customer), $this->committedAmount($customer), 4);
    }

    public function availableCredit(Customer $customer): string
    {
        $limit = (string) $customer->credit_limit;
        $available = bcsub($limit, $this->totalExposure($customer), 4);

        return bccomp($available, '0', 4) > 0 ? $available : '0.0000';
    }

    public function canExtend(Customer $customer, string $amount): bool
    {
        // Zero or negative limit means "no limit set" — always allow.
        if (bccomp((string) $customer->credit_limit, '0', 4) <= 0) {
            return true;
        }

        $exposure = $this->totalExposure($customer);
        $proposed = bcadd($exposure, $amount, 4);

        return bccomp($proposed, (string) $customer->credit_limit, 4) <= 0;
    }
}
