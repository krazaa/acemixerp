<?php

declare(strict_types=1);

namespace Modules\Procurement\Http\Controllers;

use App\Contracts\VendorManager;
use App\Contracts\WarehouseManager;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Modules\Inventory\Models\Brand;
use Modules\Inventory\Models\Origin;
use Modules\Procurement\Contracts\GoodsReceiptManager;
use Modules\Procurement\Contracts\PurchaseOrderManager;
use Modules\Procurement\Data\GoodsReceiptData;
use Modules\Procurement\Enums\GoodsReceiptStatus;
use Modules\Procurement\Http\Requests\GoodsReceipts\StoreGoodsReceiptRequest;
use Modules\Procurement\Http\Requests\GoodsReceipts\UpdateGoodsReceiptRequest;
use Modules\Procurement\Models\GoodsReceipt;
use Modules\Procurement\Models\PurchaseOrder;

class GoodsReceiptController extends Controller
{
    public function __construct(
        private readonly GoodsReceiptManager $goodsReceipts,
        private readonly PurchaseOrderManager $purchaseOrders,
        private readonly VendorManager $vendors,
        private readonly WarehouseManager $warehouses,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', GoodsReceipt::class);

        return view('procurement::goods-receipts.index', [
            'goodsReceipts' => $this->goodsReceipts->paginate(
                $request->only(['search', 'status', 'vendor_id', 'from', 'to'])
            ),
            'vendors' => $this->vendors->allActive(),
        ]);
    }

    /**
     * Create a GRN against a specific PO. Route: goods-receipts/create?purchase_order_id=…
     */
    public function create(Request $request): View
    {
        $this->authorize('create', GoodsReceipt::class);

        $poId = $request->integer('purchase_order_id');
        if (! $poId) {
            return view('procurement::goods-receipts.create', [
                'brands' => Brand::query()->orderBy('name')->get(),
                'origins' => Origin::query()->orderBy('name')->get(),
                'purchaseOrders' => PurchaseOrder::query()
                    ->open()
                    ->with('vendor:id,code,name')
                    ->orderByDesc('order_date')
                    ->get(['id', 'number', 'vendor_id', 'warehouse_id', 'expected_date']),
                'purchaseOrder' => null,
                'goodsReceipt' => new GoodsReceipt([
                    'received_date' => now()->toDateString(),
                    'status' => GoodsReceiptStatus::Draft,
                ]),
            ]);
        }

        $po = PurchaseOrder::query()
            ->with(['lines.brand', 'lines.item', 'lines.unit', 'vendor', 'warehouse'])
            ->findOrFail($poId);

        // Pre-populate GRN lines with open quantities
        $lines = $po->lines->map(fn ($line) => [
            'purchase_order_line_id' => $line->id,
            'item_id' => $line->item_id,
            'brand_id' => $line->brand_id,
            'origin_id' => $line->origin_id,
            'received_quantity' => $line->openQuantity(),
            'accepted_quantity' => $line->openQuantity(),
            'rejected_quantity' => '0.0000',
            'rejection_reason' => null,
            'notes' => null,
        ])->all();

        return view('procurement::goods-receipts.create', [
            'brands' => Brand::query()->orderBy('name')->get(),
            'origins' => Origin::query()->orderBy('name')->get(),
            'purchaseOrder' => $po,
            'purchaseOrders' => collect(),
            'goodsReceipt' => new GoodsReceipt([
                'purchase_order_id' => $po->id,
                'received_date' => now()->toDateString(),
                'status' => GoodsReceiptStatus::Draft,
            ]),
            'prefilledLines' => $lines,
        ]);
    }

    public function store(StoreGoodsReceiptRequest $request): RedirectResponse
    {
        $grn = $this->goodsReceipts->createDraft(
            GoodsReceiptData::fromRequest($request),
            $request->user()->id,
        );

        return redirect()
            ->route('procurement.goods-receipts.show', $grn)
            ->with('status', "Goods receipt {$grn->number} created as draft.");
    }

    public function show(GoodsReceipt $goodsReceipt): View
    {
        $this->authorize('view', $goodsReceipt);

        return view('procurement::goods-receipts.show', [
            'goodsReceipt' => $goodsReceipt->load([
                'lines.brand', 'lines.item',
                'lines.purchaseOrderLine',
                'purchaseOrder',
                'vendor',
                'warehouse',
                'poster',
                'creator',
                'updater',
            ]),
        ]);
    }

    public function edit(GoodsReceipt $goodsReceipt): View
    {
        $this->authorize('update', $goodsReceipt);

        return view('procurement::goods-receipts.edit', [
            'brands' => Brand::query()->orderBy('name')->get(),
            'origins' => Origin::query()->orderBy('name')->get(),
            'goodsReceipt' => $goodsReceipt->load([
                'lines.brand', 'lines.item',
                'lines.purchaseOrderLine',
                'purchaseOrder.lines.item',
                'purchaseOrder.vendor',
            ]),
        ]);
    }

    public function update(UpdateGoodsReceiptRequest $request, GoodsReceipt $goodsReceipt): RedirectResponse
    {
        $this->goodsReceipts->update(
            $goodsReceipt,
            GoodsReceiptData::fromRequest($request),
            $request->user()->id,
        );

        return redirect()
            ->route('procurement.goods-receipts.show', $goodsReceipt)
            ->with('status', 'Goods receipt updated.');
    }

    public function destroy(GoodsReceipt $goodsReceipt): RedirectResponse
    {
        $this->authorize('delete', $goodsReceipt);

        $this->goodsReceipts->delete($goodsReceipt);

        return redirect()
            ->route('procurement.goods-receipts.index')
            ->with('status', 'Goods receipt deleted.');
    }

    public function post(GoodsReceipt $goodsReceipt): RedirectResponse
    {
        $this->authorize('post', $goodsReceipt);

        $this->goodsReceipts->post($goodsReceipt, auth()->id());

        return back()->with(
            'status',
            "Goods receipt {$goodsReceipt->number} posted. Accepted stock added to inventory.",
        );
    }

    public function cancel(Request $request, GoodsReceipt $goodsReceipt): RedirectResponse
    {
        $this->authorize('cancel', $goodsReceipt);

        $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        $this->goodsReceipts->cancel(
            $goodsReceipt,
            auth()->id(),
            $request->input('reason'),
        );

        return back()->with('status', 'Goods receipt cancelled.');
    }

    public function print(GoodsReceipt $goodsReceipt)
    {
        $goodsReceipt->load([
            'purchaseOrder',
            'vendor',
            'warehouse',
            'lines.brand', 'lines.item',
            'poster',
            'creator',
        ]);

        $company = Organization::findOrFail(
            Organization::SINGLETON_ID
        );

        $logoDataUri = null;

        if (
            $company->logo_path &&
            Storage::disk('local')->exists($company->logo_path)
        ) {
            $mimeType = Storage::disk('local')
                ->mimeType($company->logo_path)
                ?: 'image/png';

            $logoDataUri =
                'data:'.$mimeType.';base64,'.
                base64_encode(
                    Storage::disk('local')->get($company->logo_path)
                );
        }

        return view('procurement::goods-receipts.print', [
            'goodsReceipt' => $goodsReceipt,
            'company' => $company,
            'logoDataUri' => $logoDataUri,
        ]);
    }
}
