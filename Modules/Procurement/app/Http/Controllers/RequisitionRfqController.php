<?php

declare(strict_types=1);

namespace Modules\Procurement\Http\Controllers;

use App\Contracts\VendorManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Procurement\Actions\ConvertRequisitionToRfq;
use Modules\Procurement\Http\Requests\Rfqs\StoreRfqFromRequisitionRequest;
use Modules\Procurement\Models\PurchaseRequisition;

class RequisitionRfqController extends Controller
{
    public function create(PurchaseRequisition $purchaseRequisition, VendorManager $vendors): View
    {
        $this->authorize('convertToRfq', $purchaseRequisition);

        return view('procurement::purchase-requisitions.convert-to-rfq', [
            'requisition' => $purchaseRequisition->load(['lines.brand','lines.origin', 'lines.item', 'lines.unit']),
            'vendors' => $vendors->allSupplier(),
        ]);
    }

    public function store(StoreRfqFromRequisitionRequest $request, PurchaseRequisition $purchaseRequisition, ConvertRequisitionToRfq $convert): RedirectResponse
    {
        $rfq = $convert->execute($purchaseRequisition, $request->validated(), $request->user()->id);

        return redirect()->route('procurement.rfqs.show', $rfq)
            ->with('status', "Requisition {$purchaseRequisition->number} converted to draft RFQ {$rfq->number}.");
    }
}
