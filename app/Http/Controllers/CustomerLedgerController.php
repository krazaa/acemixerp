<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\PartyLedgerService;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerLedgerController extends Controller
{
    public function __construct(private readonly PartyLedgerService $ledger) {}

    public function __invoke(Request $request, Customer $customer): View
    {
        $this->authorize('view', $customer);

        $from = $request->date('from');
        $to = $request->date('to');

        return view('ledgers.customer', [
            'customer' => $customer,
            'lines' => $this->ledger->forCustomer($customer, $from, $to),
            'balance' => $this->ledger->customerBalance($customer),
            'openItems' => $this->ledger->openItemsForCustomer($customer),
            'from' => $from,
            'to' => $to,
        ]);
    }
}
