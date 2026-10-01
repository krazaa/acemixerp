<?php

namespace Modules\Sales\Http\Controllers;

use App\Contracts\CustomerManager;
use App\Contracts\DepartmentManager;
use App\Contracts\PaymentTermManager;
use App\Contracts\WarehouseManager;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Organization;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Hr\Models\Employee;
use Modules\Inventory\Models\Brand;
use Modules\Inventory\Models\Origin;
use Modules\Sales\Contracts\SalesOrderManager;
use Modules\Sales\Data\SalesOrderData;
use Modules\Sales\Enums\SalesOrderStatus;
use Modules\Sales\Http\Requests\SalesOrders\StoreSalesOrderRequest;
use Modules\Sales\Http\Requests\SalesOrders\UpdateSalesOrderRequest;
use Modules\Sales\Models\SalesOrder;

class SalesOrderController extends Controller
{
    public function __construct(
        private readonly SalesOrderManager $salesOrders,
        private readonly CustomerManager $customers,
        private readonly DepartmentManager $departments,
        private readonly WarehouseManager $warehouses,
        private readonly PaymentTermManager $paymentTerms,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', SalesOrder::class);

        return view('sales::sales-orders.index', [
            'orders' => $this->salesOrders->paginate(
                $request->only(['search', 'status', 'customer_id', 'from', 'to'])
            ),
            'customers' => $this->customers->allActive(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', SalesOrder::class);

        $customerId = $request->integer('customer_id');
        $customer = $customerId ? Customer::query()->active()->findOrFail($customerId) : null;

        return view('sales::sales-orders.create', $this->formDependencies() + [
            'salesOrder' => new SalesOrder([
                'order_date' => now()->toDateString(),
                'customer_id' => $customer?->id,
                'currency_code' => $customer?->currency_code ?? Organization::current()->currency_code,
                'payment_term_id' => $customer?->payment_term_id,
                'status' => SalesOrderStatus::Draft,
            ]),
        ]);
    }

    public function store(StoreSalesOrderRequest $request): RedirectResponse
    {
        $order = $this->salesOrders->create(
            SalesOrderData::fromRequest($request),
            $request->user()->id,
        );

        return redirect()->route('sales.sales-orders.show', $order)
            ->with('status', "Sales order {$order->number} created as draft.");
    }

    public function show(SalesOrder $salesOrder): View
    {
        $this->authorize('view', $salesOrder);

        return view('sales::sales-orders.show', [
            'salesOrder' => $salesOrder->load([
                'lines.item', 'lines.unit', 'lines.brand', 'lines.origin',
                'customer', 'department', 'warehouse', 'paymentTerm',
                'salesperson', 'submitter', 'approver', 'confirmer', 'holdReleaser',
                'creator', 'updater', 'source',
            ]),
        ]);
    }

    public function edit(SalesOrder $salesOrder): View
    {
        $this->authorize('update', $salesOrder);

        return view('sales::sales-orders.edit', $this->formDependencies() + [
            'salesOrder' => $salesOrder->load('lines'),

        ]);
    }

    public function update(UpdateSalesOrderRequest $request, SalesOrder $salesOrder): RedirectResponse
    {
        $this->salesOrders->update(
            $salesOrder,
            SalesOrderData::fromRequest($request),
            $request->user()->id,
        );

        return redirect()->route('sales.sales-orders.show', $salesOrder)
            ->with('status', 'Sales order updated.');
    }

    public function destroy(SalesOrder $salesOrder): RedirectResponse
    {
        $this->authorize('delete', $salesOrder);
        $this->salesOrders->delete($salesOrder);

        return redirect()->route('sales.sales-orders.index')
            ->with('status', 'Sales order deleted.');
    }

    public function submit(SalesOrder $salesOrder): RedirectResponse
    {
        $this->authorize('submit', $salesOrder);

        $order = $this->salesOrders->submit($salesOrder, auth()->id());

        $msg = $order->status->value === 'on_hold'
            ? "Sales order {$order->number} is on credit hold and requires manager release."
            : 'Sales order submitted for approval.';

        return back()->with('status', $msg);
    }

    public function approve(SalesOrder $salesOrder): RedirectResponse
    {
        $this->authorize('approve', $salesOrder);
        $this->salesOrders->approve($salesOrder, auth()->id());

        return back()->with('status', 'Sales order approved.');
    }

    public function reject(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        $this->authorize('reject', $salesOrder);
        $request->validate(['reason' => ['required', 'string', 'min:3', 'max:500']]);
        $this->salesOrders->reject($salesOrder, auth()->id(), $request->string('reason')->toString());

        return back()->with('status', 'Sales order rejected.');
    }

    public function releaseHold(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        $this->authorize('releaseHold', $salesOrder);
        $request->validate(['reason' => ['required', 'string', 'min:3', 'max:500']]);
        $this->salesOrders->releaseHold($salesOrder, auth()->id(), $request->string('reason')->toString());

        return back()->with('status', 'Credit hold released. Order is now submitted for approval.');
    }

    public function confirm(SalesOrder $salesOrder): RedirectResponse
    {
        $this->authorize('confirm', $salesOrder);
        $this->salesOrders->confirm($salesOrder, auth()->id());

        return back()->with('status', 'Sales order confirmed. It is now committed and ready for delivery.');
    }

    public function cancel(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        $this->authorize('cancel', $salesOrder);
        $request->validate(['reason' => ['nullable', 'string', 'max:500']]);
        $this->salesOrders->cancel($salesOrder, auth()->id(), $request->input('reason'));

        return back()->with('status', 'Sales order cancelled.');
    }

    public function close(SalesOrder $salesOrder): RedirectResponse
    {
        $this->authorize('close', $salesOrder);
        $this->salesOrders->close($salesOrder, auth()->id());

        return back()->with('status', 'Sales order closed.');
    }

    /** @return array<string, mixed> */
    private function formDependencies(): array
    {
        return [
            'brands' => Brand::query()->orderBy('name')->get(),
            'origins' => Origin::query()->orderBy('name')->get(),
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
            'salespersons' => Employee::query()
                ->where('status', UserStatus::Active->value)
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name']),
        ];
    }
}
