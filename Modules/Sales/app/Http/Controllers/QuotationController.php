<?php

namespace Modules\Sales\Http\Controllers;

use App\Contracts\CustomerManager;
use App\Contracts\DepartmentManager;
use App\Contracts\PaymentTermManager;
use App\Contracts\WarehouseManager;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Organization;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Sales\Contracts\QuotationManager;
use Modules\Sales\Contracts\SalesOrderManager;
use Modules\Sales\Data\QuotationData;
use Modules\Sales\Enums\QuotationStatus;
use Modules\Sales\Http\Requests\Quotations\StoreQuotationRequest;
use Modules\Sales\Http\Requests\Quotations\UpdateQuotationRequest;
use Modules\Sales\Models\Quotation;

class QuotationController extends Controller
{
    public function __construct(
        private readonly QuotationManager $quotations,
        private readonly SalesOrderManager $salesOrders,
        private readonly CustomerManager $customers,
        private readonly DepartmentManager $departments,
        private readonly WarehouseManager $warehouses,
        private readonly PaymentTermManager $paymentTerms,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Quotation::class);

        return view('sales::quotations.index', [
            'quotations' => $this->quotations->paginate(
                $request->only(['search', 'status', 'customer_id', 'from', 'to'])
            ),
            'customers' => $this->customers->allActive(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Quotation::class);

        return view('sales::quotations.create', $this->formDependencies() + [
            'quotation' => new Quotation([
                'quotation_date' => now()->toDateString(),
                'valid_until' => now()->addDays(30)->toDateString(),
                'currency_code' => Organization::current()->currency_code,
                'status' => QuotationStatus::Draft,
            ]),
        ]);
    }

    public function store(StoreQuotationRequest $request): RedirectResponse
    {
        $quotation = $this->quotations->create(
            QuotationData::fromRequest($request),
            $request->user()->id,
        );

        return redirect()->route('sales.quotations.show', $quotation)
            ->with('status', "Quotation {$quotation->number} created as draft.");
    }

    public function show(Quotation $quotation): View
    {
        $this->authorize('view', $quotation);

        return view('sales::quotations.show', [
            'quotation' => $quotation->load([
                'lines.item', 'lines.unit',
                'customer', 'department', 'warehouse', 'paymentTerm',
                'salesperson', 'sender', 'accepter', 'creator', 'updater',
            ]),
        ]);
    }

    public function edit(Quotation $quotation): View
    {
        $this->authorize('update', $quotation);

        return view('sales::quotations.edit', $this->formDependencies() + [
            'quotation' => $quotation->load('lines'),
        ]);
    }

    public function update(UpdateQuotationRequest $request, Quotation $quotation): RedirectResponse
    {
        $this->quotations->update(
            $quotation,
            QuotationData::fromRequest($request),
            $request->user()->id,
        );

        return redirect()->route('sales.quotations.show', $quotation)
            ->with('status', 'Quotation updated.');
    }

    public function destroy(Quotation $quotation): RedirectResponse
    {
        $this->authorize('delete', $quotation);
        $this->quotations->delete($quotation);

        return redirect()->route('sales.quotations.index')
            ->with('status', 'Quotation deleted.');
    }

    public function send(Quotation $quotation): RedirectResponse
    {
        $this->authorize('send', $quotation);
        $this->quotations->send($quotation, auth()->id());

        return back()->with('status', 'Quotation marked as sent.');
    }

    public function accept(Quotation $quotation): RedirectResponse
    {
        $this->authorize('accept', $quotation);
        $this->quotations->accept($quotation, auth()->id());

        return back()->with('status', 'Quotation accepted.');
    }

    public function reject(Request $request, Quotation $quotation): RedirectResponse
    {
        $this->authorize('reject', $quotation);
        $request->validate(['reason' => ['nullable', 'string', 'max:500']]);
        $this->quotations->reject($quotation, auth()->id(), $request->input('reason'));

        return back()->with('status', 'Quotation rejected.');
    }

    public function cancel(Request $request, Quotation $quotation): RedirectResponse
    {
        $this->authorize('cancel', $quotation);
        $request->validate(['reason' => ['nullable', 'string', 'max:500']]);
        $this->quotations->cancel($quotation, auth()->id(), $request->input('reason'));

        return back()->with('status', 'Quotation cancelled.');
    }

    public function convert(Quotation $quotation): RedirectResponse
    {
        $this->authorize('convert', $quotation);

        $order = $this->salesOrders->createFromQuotation($quotation, auth()->id());

        return redirect()->route('sales.sales-orders.show', $order)
            ->with('status', "Sales order {$order->number} created from quotation {$quotation->number}.");
    }

    /** @return array<string, mixed> */
    private function formDependencies(): array
    {
        return [
            'customers' => $this->customers->allActive(),
            'departments' => $this->departments->allActive(),
            'warehouses' => $this->warehouses->allActive(),
            'paymentTerms' => $this->paymentTerms->allActive(),
            'items' => Item::query()
                ->where('is_sellable', true)
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'selling_price', 'tax_rate_id']),
            'units' => Unit::query()
                ->where('status', 'active')
                ->orderBy('code')
                ->get(['id', 'code', 'name']),
            'salespersons' => User::query()
                ->where('status', UserStatus::Active->value)
                ->orderBy('name')
                ->get(['id', 'name']),
        ];
    }
}
