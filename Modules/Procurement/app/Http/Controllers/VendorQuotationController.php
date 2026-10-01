<?php

namespace Modules\Procurement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Procurement\Actions\AwardRfqLines;
use Modules\Procurement\Contracts\VendorQuotationManager;
use Modules\Procurement\Data\VendorQuotationData;
use Modules\Procurement\Http\Requests\Rfqs\AwardRfqLinesRequest;
use Modules\Procurement\Http\Requests\VendorQuotations\StoreVendorQuotationRequest;
use Modules\Procurement\Models\RequestForQuotation;
use Modules\Procurement\Models\VendorQuotation;

class VendorQuotationController extends Controller
{
    public function __construct(private readonly VendorQuotationManager $quotations) {}

    public function create(RequestForQuotation $rfq): View
    {
        $this->authorize('view', $rfq);

        return view('procurement::quotations.create', [
            'rfq' => $rfq->load(['lines.brand', 'lines.item', 'lines.unit', 'vendors.vendor']),
            'quotation' => new VendorQuotation([
                'quoted_at' => now()->toDateString(),
                'currency_code' => $rfq->currency_code,
            ]),
        ]);
    }

    public function store(StoreVendorQuotationRequest $request, RequestForQuotation $rfq): RedirectResponse
    {
        $quotation = $this->quotations->record(
            $rfq,
            VendorQuotationData::fromRequest($request),
            $request->user()->id,
        );

        return redirect()
            ->route('procurement.rfqs.show', $rfq)
            ->with('status', "Quotation from {$quotation->vendor->name} recorded.");
    }

    public function submit(VendorQuotation $quotation): RedirectResponse
    {
        $this->authorize('view', $quotation->rfq);

        $this->quotations->submit($quotation, auth()->id());

        return redirect()
            ->route('procurement.rfqs.show', $quotation->request_for_quotation_id)
            ->with('status', 'Quotation submitted.');
    }

    public function awardLines(AwardRfqLinesRequest $request, RequestForQuotation $rfq, AwardRfqLines $award): RedirectResponse
    {
        $award->execute($rfq, $request->validated('selections'), $request->user()->id);

        return redirect()->route('procurement.rfqs.compare', $rfq)
            ->with('status', 'Each product has been awarded to its selected vendor.');
    }

    public function award(RequestForQuotation $rfq, VendorQuotation $quotation): RedirectResponse
    {
        $this->authorize('award', $rfq);

        $this->quotations->award($rfq, $quotation, auth()->id());

        return redirect()
            ->route('procurement.rfqs.show', $rfq)
            ->with('status', 'RFQ awarded.');
    }
}
