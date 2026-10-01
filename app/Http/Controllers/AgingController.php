<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\AgingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgingController extends Controller
{
    public function __construct(private readonly AgingService $aging) {}

    public function receivables(Request $request): View
    {
        $this->authorize('reports.financial');
        $request->validate(['as_of' => ['nullable', 'date_format:Y-m-d']]);
        $asOf = $request->date('as_of') ?? now();

        return view('ledgers.aging', [
            'title' => 'Accounts Receivable Aging',
            'rows' => $this->aging->customerAging($asOf),
            'asOf' => $asOf,
            'type' => 'receivable',
        ]);
    }

    public function payables(Request $request): View
    {
        $this->authorize('reports.financial');
        $request->validate(['as_of' => ['nullable', 'date_format:Y-m-d']]);
        $asOf = $request->date('as_of') ?? now();

        return view('ledgers.aging', [
            'title' => 'Accounts Payable Aging',
            'rows' => $this->aging->vendorAging($asOf),
            'asOf' => $asOf,
            'type' => 'payable',
        ]);
    }
}
