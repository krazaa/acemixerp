<?php

namespace Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Sales\Http\Requests\StoreSalesReturnRequest;
use Modules\Sales\Http\Requests\TransitionSalesReturnRequest;
use Modules\Sales\Models\SalesInvoice;
use Modules\Sales\Models\SalesReturn;
use Modules\Sales\Services\SalesReturnWorkflow;

class SalesReturnController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', SalesReturn::class);
        $returns = SalesReturn::query()->with(['invoice.customer', 'creditNote'])
            ->when($request->string('search')->toString(), fn ($q, $term) => $q->where('number', 'like', "%{$term}%"))
            ->latest('id')->paginate(25)->withQueryString();

        return view('sales::returns.index', compact('returns'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', SalesReturn::class);
        $request->validate(['invoice_id' => ['nullable', 'integer', 'exists:sales_invoices,id']]);
        $invoice = $request->integer('invoice_id') ? SalesInvoice::query()->with(['lines.item', 'lines.unit', 'customer'])
            ->whereIn('status', ['posted', 'partially_paid', 'paid'])->findOrFail($request->integer('invoice_id')) : null;

        return view('sales::returns.create', [
            'invoice' => $invoice,
            'invoices' => SalesInvoice::query()->with('customer')->whereIn('status', ['posted', 'partially_paid', 'paid'])->latest('id')->get(),
            'warehouses' => Warehouse::query()->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreSalesReturnRequest $request, SalesReturnWorkflow $workflow): RedirectResponse
    {
        $return = $workflow->create($request->validated(), $request->user());

        return redirect()->route('sales.returns.show', $return)->with('status', 'Return request submitted.');
    }

    public function show(SalesReturn $salesReturn): View
    {
        $this->authorize('view', $salesReturn);
        $salesReturn->load(['invoice.customer', 'warehouse', 'creator', 'lines.invoiceLine.item', 'lines.invoiceLine.unit', 'creditNote.journalEntry']);

        return view('sales::returns.show', ['return' => $salesReturn]);
    }

    public function transition(TransitionSalesReturnRequest $request, SalesReturn $salesReturn, string $step, SalesReturnWorkflow $workflow): RedirectResponse
    {
        $workflow->transition($salesReturn, $step, $request->validated(), $request->user());

        return redirect()->route('sales.returns.show',$salesReturn)->with('status','Return workflow updated.');
    }
}
