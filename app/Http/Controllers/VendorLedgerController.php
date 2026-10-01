<?php

namespace App\Http\Controllers;

use App\Contracts\PartyLedgerService;
use App\Models\Organization;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VendorLedgerController extends Controller
{
    public function __construct(private readonly PartyLedgerService $ledger) {}

    // public function __invoke(Request $request, Vendor $vendor): View
    // {
    //     $this->authorize('view', $vendor);

    //     $from = $request->date('from');
    //     $to = $request->date('to');

    //     return view('ledgers.vendor', [
    //         'vendor' => $vendor,
    //         'lines' => $this->ledger->forVendor($vendor, $from, $to),
    //         'balance' => $this->ledger->vendorBalance($vendor),
    //         'openItems' => $this->ledger->openItemsForVendor($vendor),
    //         'from' => $from,
    //         'to' => $to,
    //     ]);
    // }

    public function __invoke(Request $request, Vendor $vendor): View
    {
        $this->authorize('view', $vendor);

        $from = $request->date('from');
        $to = $request->date('to');

        return view('ledgers.vendor', [
            'vendor' => $vendor,
            'lines' => $this->ledger->forVendor($vendor, $from, $to),
            'balance' => $this->ledger->vendorBalance($vendor),
            'openItems' => $this->ledger->openItemsForVendor($vendor),
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function statement(Request $request, Vendor $vendor): View
    {
        $this->authorize('view', $vendor);
        $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $from = $request->date('from') ?? now()->startOfYear();
        $to = $request->date('to') ?? now();
        if ($to->toDateString() < $from->toDateString()) {
            throw ValidationException::withMessages(['to' => 'The end date must be on or after the start date.']);
        }

        $statement = $this->ledger->vendorStatement($vendor, $from, $to);

        return view('ledgers.vendor-statement', [
            'vendor' => $vendor,
            'organization' => Organization::currentOrNull(),
            'from' => $from,
            'to' => $to,
            'lines' => $statement['lines'],
            'openingBalance' => $statement['opening_balance'],
            'totalInvoices' => $statement['total_invoices'],
            'totalPayments' => $statement['total_payments'],
            'totalWht' => $statement['total_wht'],
            'totalAdjustments' => $statement['total_adjustments'],
            'closingBalance' => $statement['closing_balance'],
        ]);
    }
}
