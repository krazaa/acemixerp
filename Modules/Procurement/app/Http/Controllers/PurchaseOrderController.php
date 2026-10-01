<?php

declare(strict_types=1);

namespace Modules\Procurement\Http\Controllers;

use App\Contracts\CostCenterManager;
use App\Contracts\DepartmentManager;
use App\Contracts\PaymentTermManager;
use App\Contracts\VendorManager;
use App\Contracts\WarehouseManager;
use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Organization;
use App\Models\Unit;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Inventory\Models\Brand;
use Modules\Inventory\Models\Origin;
use Modules\Procurement\Contracts\PurchaseOrderManager;
use Modules\Procurement\Data\PurchaseOrderData;
use Modules\Procurement\Enums\PurchaseOrderStatus;
use Modules\Procurement\Http\Requests\PurchaseOrders\StorePurchaseOrderRequest;
use Modules\Procurement\Http\Requests\PurchaseOrders\UpdatePurchaseOrderRequest;
use Modules\Procurement\Models\PurchaseOrder;

class PurchaseOrderController extends Controller
{
    public function __construct(
        private readonly PurchaseOrderManager $purchaseOrders,
        private readonly VendorManager $vendors,
        private readonly WarehouseManager $warehouses,
        private readonly DepartmentManager $departments,
        private readonly CostCenterManager $costCenters,
        private readonly PaymentTermManager $paymentTerms,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', PurchaseOrder::class);

        return view('procurement::purchase-orders.index', [
            'purchaseOrders' => $this->purchaseOrders->paginate(
                $request->only(['search', 'status', 'vendor_id', 'warehouse_id', 'from', 'to'])
            ),
            'vendors' => $this->vendors->allActive(),
            'warehouses' => $this->warehouses->allActive(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', PurchaseOrder::class);

        return view('procurement::purchase-orders.create', $this->formDependencies() + [
            'purchaseOrder' => new PurchaseOrder([
                'order_date' => now()->toDateString(),
                'currency_code' => Organization::current()->currency_code,
                'status' => PurchaseOrderStatus::Draft,
            ]),
        ]);
    }

    public function store(StorePurchaseOrderRequest $request): RedirectResponse
    {
        $po = $this->purchaseOrders->create(
            PurchaseOrderData::fromRequest($request),
            $request->user()->id,
        );

        return redirect()
            ->route('procurement.purchase-orders.show', $po)
            ->with('status', "Purchase order {$po->number} created as draft.");
    }

    public function show(PurchaseOrder $purchaseOrder): View
    {
        $this->authorize('view', $purchaseOrder);

        return view('procurement::purchase-orders.show', [
            'purchaseOrder' => $purchaseOrder->load([
                'lines.brand', 'lines.item', 'lines.unit',
                'vendor', 'department', 'costCenter', 'warehouse', 'paymentTerm',
                'submitter', 'approver', 'issuer', 'creator', 'updater',
                'goodsReceipts' => fn ($q) => $q->orderByDesc('received_date'),
                'source',
            ]),
        ]);
    }

    public function edit(PurchaseOrder $purchaseOrder): View
    {
        $this->authorize('update', $purchaseOrder);

        return view('procurement::purchase-orders.edit', $this->formDependencies() + [
            'purchaseOrder' => $purchaseOrder->load('lines'),
        ]);
    }

    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->purchaseOrders->update(
            $purchaseOrder,
            PurchaseOrderData::fromRequest($request),
            $request->user()->id,
        );

        return redirect()
            ->route('procurement.purchase-orders.show', $purchaseOrder)
            ->with('status', 'Purchase order updated.');
    }

    public function destroy(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('delete', $purchaseOrder);

        $this->purchaseOrders->delete($purchaseOrder);

        return redirect()
            ->route('procurement.purchase-orders.index')
            ->with('status', 'Purchase order deleted.');
    }

    public function submit(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('submit', $purchaseOrder);

        $this->purchaseOrders->submit($purchaseOrder, auth()->id());

        return back()->with('status', 'Purchase order submitted for approval.');
    }

    public function approve(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('approve', $purchaseOrder);

        $this->purchaseOrders->approve($purchaseOrder, auth()->id());

        return back()->with('status', 'Purchase order approved.');
    }

    public function reject(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('reject', $purchaseOrder);

        $request->validate(['reason' => ['required', 'string', 'min:3', 'max:500']]);

        $this->purchaseOrders->reject(
            $purchaseOrder,
            auth()->id(),
            $request->string('reason')->toString(),
        );

        return back()->with('status', 'Purchase order rejected.');
    }

    public function issue(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('issue', $purchaseOrder);

        $this->purchaseOrders->issue($purchaseOrder, auth()->id());

        return back()->with('status', "Purchase order {$purchaseOrder->number} issued to vendor.");
    }

    public function cancel(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('cancel', $purchaseOrder);

        $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        $this->purchaseOrders->cancel(
            $purchaseOrder,
            auth()->id(),
            $request->input('reason'),
        );

        return back()->with('status', 'Purchase order cancelled.');
    }

    public function close(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('close', $purchaseOrder);

        $this->purchaseOrders->close($purchaseOrder, auth()->id());

        return back()->with('status', 'Purchase order closed.');
    }

    // public function print($id)
    // {
    //     $purchaseOrder = PurchaseOrder::query()
    //         ->with([
    //             'vendor',
    //             'lines.brand', 'lines.item',
    //             'lines.item.unit',
    //             'purchaseRequisition',
    //             'rfq',
    //             'createdBy',
    //             'approvedBy',
    //         ])
    //         ->findOrFail($id);

    //     return view('procurement::purchase-orders.print', compact('purchaseOrder'));
    // }

    public function print(PurchaseOrder $purchaseOrder, Request $request): View|Response
    {
        $this->authorize('view', $purchaseOrder);

        $purchaseOrder->load([
            'vendor.addresses', 'creator', 'paymentTerm',
            'lines.brand', 'lines.origin', 'lines.item',
            'lines.item.unit',
            'purchaseRequisition',
        ]);

        $company = Organization::findOrFail(Organization::SINGLETON_ID);

        $logoDataUri = null;

        if (
            $company->logo_path &&
            Storage::disk('local')->exists($company->logo_path)
        ) {
            $mimeType = Storage::disk('local')->mimeType($company->logo_path)
                ?: 'image/png';

            $logoDataUri = 'data:'.$mimeType.';base64,'.
                base64_encode(
                    Storage::disk('local')->get($company->logo_path)
                );
        }

        $pdf = $request->boolean('download');
        $data = compact('purchaseOrder', 'company', 'logoDataUri', 'pdf');

        if ($pdf) {
            return Pdf::loadView('procurement::purchase-orders.print', $data)
                ->setPaper('a4')->setOption([
                    'isRemoteEnabled' => false,
                    'isPhpEnabled' => false,
                    'isJavascriptEnabled' => false,
                ])->download(Str::slug($purchaseOrder->number).'.pdf');
        }

        return view('procurement::purchase-orders.print', $data);
    }

    /** @return array<string, mixed> */
    private function formDependencies(): array
    {
        return [
            'brands' => Brand::query()->orderBy('name')->get(),
            'origins' => Origin::query()->orderBy('name')->get(),
            'vendors' => $this->vendors->allActive(),
            'warehouses' => $this->warehouses->allActive(),
            'departments' => $this->departments->allActive(),
            'costCenters' => $this->costCenters->allActive(),
            'paymentTerms' => $this->paymentTerms->allActive(),
            'items' => Item::query()
                ->where('is_purchasable', true)
                ->where('status', 'active')
                ->where('item_type', 'stock')
                ->orderBy('name')
                ->get(['id', 'code', 'name']),
            'units' => Unit::query()
                ->where('status', 'active')
                ->orderBy('code')
                ->get(['id', 'code', 'name']),
        ];
    }
}
