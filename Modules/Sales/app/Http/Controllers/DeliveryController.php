<?php

namespace Modules\Sales\Http\Controllers;

use App\Contracts\CustomerManager;
use App\Contracts\VendorManager;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Sales\Contracts\DeliveryManager;
use Modules\Sales\Data\DeliveryData;
use Modules\Sales\Enums\DeliveryStatus;
use Modules\Sales\Http\Requests\Deliveries\DispatchDeliveryRequest;
use Modules\Sales\Http\Requests\Deliveries\StoreDeliveryRequest;
use Modules\Sales\Http\Requests\Deliveries\UpdateDeliveryRequest;
use Modules\Sales\Models\Delivery;
use Modules\Sales\Models\SalesOrder;

class DeliveryController extends Controller
{
    public function __construct(
        private readonly DeliveryManager $deliveries,
        private readonly CustomerManager $customers,
        private readonly VendorManager $vendors,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Delivery::class);

        return view('sales::deliveries.index', [
            'deliveries' => $this->deliveries->paginate(
                $request->only(['search', 'status', 'customer_id', 'from', 'to'])
            ),
            'customers' => $this->customers->allActive(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Delivery::class);

        $soId = $request->integer('sales_order_id');
        $order = $soId
            ? SalesOrder::query()->with(['lines.item', 'lines.unit', 'customer'])->findOrFail($soId)
            : null;

        return view('sales::deliveries.create', [
            'delivery' => new Delivery([
                'delivery_date' => now()->toDateString(),
                'shipping_address' => $order?->customer?->shippingAddress()?->oneLine(),
                'status' => DeliveryStatus::Draft,
            ]),
            'salesOrder' => $order,
            'openOrders' => SalesOrder::query()->open()->with('customer:id,name')->orderByDesc('order_date')->get(['id', 'number', 'customer_id', 'warehouse_id']),
            'transportVendors' => $this->vendors->allTransportProviders(),
        ]);
    }

    public function store(StoreDeliveryRequest $request): RedirectResponse
    {
        $delivery = $this->deliveries->create(
            DeliveryData::fromRequest($request),
            $request->user()->id,
        );

        return redirect()->route('sales.deliveries.show', $delivery)
            ->with('status', "Delivery {$delivery->number} created as draft.");
    }

    public function show(Delivery $delivery): View
    {
        $this->authorize('view', $delivery);

        return view('sales::deliveries.show', [
            'delivery' => $delivery->load([
                'lines.item', 'lines.unit', 'lines.salesOrderLine',
                'salesOrder', 'customer', 'warehouse',
                'picker', 'dispatcher', 'deliverer',
                'creator', 'updater',
            ]),
        ]);
    }

    public function edit(Delivery $delivery): View
    {
        $this->authorize('update', $delivery);

        return view('sales::deliveries.edit', [
            'delivery' => $delivery->load(['lines.item', 'salesOrder.lines.item']),
            'transportVendors' => $this->vendors->allTransportProviders(),
        ]);
    }

    public function update(UpdateDeliveryRequest $request, Delivery $delivery): RedirectResponse
    {
        $this->deliveries->update($delivery, DeliveryData::fromRequest($request), $request->user()->id);

        return redirect()->route('sales.deliveries.show', $delivery)
            ->with('status', 'Delivery updated.');
    }

    public function destroy(Delivery $delivery): RedirectResponse
    {
        $this->authorize('delete', $delivery);
        $this->deliveries->delete($delivery);

        return redirect()->route('sales.deliveries.index')->with('status', 'Delivery deleted.');
    }

    public function pick(Delivery $delivery): RedirectResponse
    {
        $this->authorize('pick', $delivery);
        $this->deliveries->pick($delivery, auth()->id());

        return back()->with('status', 'Delivery picked.');
    }

    // public function dispatch(DispatchDeliveryRequest $request, Delivery $delivery): RedirectResponse
    // {
    //     $this->deliveries->dispatch(
    //         $delivery,
    //         DispatchDeliveryData::fromValidated($request->validated()),
    //         $request->user()->id,
    //     );

    //     return back()->with('status', 'Delivery dispatched. Stock movements recorded.');
    // }

    public function dispatch(DispatchDeliveryRequest $request, Delivery $delivery): RedirectResponse
    {
        $this->deliveries->dispatch(
            $delivery,
            auth()->id(),
            [
                'vehicle_number' => $request->string('vehicle_number')->toString(),
                'driver_name' => $request->string('driver_name')->toString(),
                'driver_contact' => $request->string('driver_contact')->toString(),
                'driver_cnic' => $request->input('driver_cnic'),
                'bilty_number' => $request->string('bilty_number')->toString(),
            ],
        );

        return back()->with('status', 'Delivery dispatched. Stock movements recorded.');
    }

    public function markDelivered(Delivery $delivery): RedirectResponse
    {
        $this->authorize('markDelivered', $delivery);
        $this->deliveries->markDelivered($delivery, auth()->id());

        return back()->with('status', 'Delivery marked as delivered.');
    }

    public function close(Delivery $delivery): RedirectResponse
    {
        $this->authorize('close', $delivery);
        $this->deliveries->close($delivery, auth()->id());

        return back()->with('status', 'Delivery closed.');
    }

    public function cancel(Request $request, Delivery $delivery): RedirectResponse
    {
        $this->authorize('cancel', $delivery);
        $request->validate(['reason' => ['nullable', 'string', 'max:500']]);
        $this->deliveries->cancel($delivery, auth()->id(), $request->input('reason'));

        return back()->with('status', 'Delivery cancelled.');
    }

    public function printNote(Delivery $delivery): View
    {
        $this->authorize('view', $delivery);

        return view('sales::deliveries.print-note', [
            'delivery' => $delivery->load([
                'lines.item', 'lines.unit', 'lines.salesOrderLine',
                'salesOrder', 'customer', 'warehouse', 'vendor',
                'dispatcher', 'deliverer',
            ]),
            'organization' => Organization::current(),
        ]);
    }
}
