<x-default-layout>
@section('title', "Compare — {$rfq->number}")

@section('sub-title')
     <div class="text-muted">{{ $rfq->purpose }}</div>
@endsection

@section('toolbar-button')
        <a href="{{ route('procurement.rfqs.show', $rfq) }}" class="btn btn-sm btn-light-secondary btn-sm">
            <i class="fas fa-arrow-left fa-sm"></i>  Back
        </a>
 @endsection

@if(session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@if(empty($comparison['vendors']))
    <div class="alert alert-info">No submitted quotations to compare yet.</div>
@else
    <div class="notice bg-light-primary border border-primary border-dashed rounded p-6 mb-6"><div class="fw-bold text-gray-900 mb-2">Compare unit rates, then choose a vendor for each product.</div><p class="text-gray-700 mb-0">Select one vendor for each product. Green cells show the lowest eligible unit rate before tax in {{ $rfq->currency_code }}. Tied rates are all highlighted. Quotes must match the requested quantity and currency and must not be expired.</p></div>
    @if($rfq->status->canAward())
        @can('award', $rfq)
            <form id="line-award-form" method="POST" action="{{ route('procurement.rfqs.award-lines', $rfq) }}">
                @csrf @method('PATCH')
            </form>
        @endcan
    @endif
    <div class="card card-flush mb-6">
        <div class="card-header align-items-center"><h2 class="card-title fw-bold">Product comparison</h2><div class="d-flex gap-2"><span class="badge badge-light">{{ count($comparison['lines']) }} products</span><span class="badge badge-light-primary">{{ count($comparison['vendors']) }} vendors</span></div></div>
        <div class="card-body">
        <div class="table-responsive">
        <table class="table table-row-dashed align-top gy-5 mb-0">
            <thead class="text-muted fs-7">
                <tr>
                    <th scope="col" class="min-w-200px">Product</th>
                    <th scope="col" class="text-end min-w-100px">Requested quantity</th>
                        @foreach($comparison['vendors'] as $vendor)
                    <th scope="col" class="min-w-250px text-gray-900 fw-bold">{{ $vendor['name'] }}</th>
                        @endforeach
                    <th scope="col" class="min-w-150px">Awarded vendor</th>
                </tr>
            </thead>
            <tbody>
                @foreach($comparison['lines'] as $row)
                    <tr>
                        <td><div class="fw-bold text-gray-900 mb-2">{{ $row['line']->item?->name }}</div><div class="text-muted fs-7 mb-2">{{ $row['line']->item?->code }}</div>
                            <div class="small text-muted">Brand: {{ $row['line']->brand?->name ?? '—' }}</div>
                            <div class="small text-muted">Origin: {{ $row['line']->origin?->name ?? '—' }}</div>
                            @if($rfq->status->canAward() && $row['best_unit_price'] === null)<div class="text-danger small">No eligible quote. Record a matching quotation before awarding.</div>@endif
                        </td>
                        <td class="text-end">{{ number_format((float) $row['line']->quantity, 4) }} {{ $row['line']->unit?->code }}</td>
                        @foreach($comparison['vendors'] as $vendor)
                            @php($cell = $row['cells'][$vendor['id']] ?? null)
                            @php($eligible = $cell && $cell['unit_price'] !== null && $cell['ineligible_reason'] === null)
                            @php($lowest = $eligible && $row['best_unit_price'] !== null && bccomp($cell['unit_price'], $row['best_unit_price'], 4) === 0)
                            <td class="{{ $lowest ? 'bg-light-success' : '' }}">
                                @if($cell && $cell['unit_price'] !== null)
                                    <div class="fs-4 fw-bold text-gray-900 mb-1">{{ number_format((float) $cell['unit_price'], 4) }} <span class="fs-7 text-muted fw-normal">{{ $cell['currency_code'] }}</span></div>
                                    @if($lowest)<span class="badge badge-success">Lowest rate</span>@endif
                                    <div class="fs-7 text-muted mb-3">Unit price @if($row['line']->unit?->code) / {{ $row['line']->unit->code }} @endif · before tax</div>
                                    <div class="fs-7 text-gray-700 mb-1">GST amount: {{ number_format((float) $cell['tax_amount'], 2) }} <span class="text-muted">({{ number_format((float) $cell['tax_rate'], 2) }}%)</span></div>
                                    <div class="fs-7 text-gray-700 mb-1">WHT ({{ number_format((float) $cell['wht_tax_rate'], 2) }}%): {{ number_format((float) $cell['wht_tax_amount'], 2) }}</div>
                                    <div class="fs-7 text-muted mt-3">Quoted quantity: {{ number_format((float) $cell['quantity'], 4) }} </div>
                                    <div class="small text-muted">Line subtotal: {{ number_format((float) $cell['line_total'], 2) }}</div>
                                    @if($cell['lead_time_days'] !== null)<div class="small">Lead time: {{ $cell['lead_time_days'] }} days</div>@endif

                                    @if($rfq->status->canAward())
                                        @if($eligible)
                                            @can('award', $rfq)
                                                <div class="form-check form-check-custom form-check-solid mt-4 border-top pt-4">
                                                    <input form="line-award-form" class="form-check-input" type="radio" name="selections[{{ $row['line']->id }}]" id="award-{{ $cell['quotation_line_id'] }}" value="{{ $cell['quotation_line_id'] }}" @checked((string) old('selections.'.$row['line']->id) === (string) $cell['quotation_line_id']) required>
                                                    <label class="form-check-label fs-7 text-gray-800" for="award-{{ $cell['quotation_line_id'] }}">Award this product to {{ $vendor['name'] }}</label>
                                                </div>
                                            @endcan
                                        @else
                                            <span class="badge badge-light-warning text-wrap">{{ $cell['ineligible_reason'] }}</span>
                                        @endif
                                    @endif
                                @else
                                    <span class="text-muted">Not quoted</span>
                                @endif
                            </td>
                        @endforeach
                        <td>{{ $row['line']->awardedQuotationLine?->quotation?->vendor?->name ?? ($rfq->awardedQuotation?->vendor?->name ?? '—') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div></div>
    @if($rfq->status->canAward())
        @can('award', $rfq)
            <div class="card mb-6">
                <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4">
                    <div>
                        <div class="fw-bold mb-1">Ready to confirm your selections?</div>
                        <div class="text-muted fs-7">Choose a vendor for every product before confirming. Awarded selections are final.</div>
                    </div>
                    <button type="submit" form="line-award-form" class="btn btn-primary flex-shrink-0">Confirm Product Awards</button>
                </div>
            </div>
        @endcan
    @endif
@endif
@if($rfq->quotations->isNotEmpty())
    <div class="card card-flush mb-6">
        <div class="card-header">
            <h2 class="card-title  mb-0">Full Quotation Totals</h2>
        </div>
        <div class="card-body pt-0 text-muted fs-7">
            These totals cover each vendor’s full quotation. Purchase orders include only the products awarded to that vendor.
        </div>
        <div class="card-body">
            <div class="table-responsive">
            <table class="table table-row-dashed align-middle gy-5 mb-0">
            <thead class="text-muted fs-7"><tr>
                <th>Vendor</th>
                <th>Reference</th>
                <th>Valid Until</th>
                <th>Currency</th>
                <th class="text-end">Subtotal</th>
                <th class="text-end">GST</th>
                <th class="text-end">WHT</th>
                <th class="text-end">Total</th>
                <th>Status</th>
            </tr>
        </thead>
            <tbody>
                @foreach($rfq->quotations as $quotation)
                <tr>
                    <td>{{ $quotation->vendor?->name ?? 'Unavailable vendor' }}</td>
                    <td>{{ $quotation->reference ?? '—' }}</td>
                    <td>{{ $quotation->valid_until?->format('d/m/Y') ?? '—' }}</td>
                    <td>{{ $quotation->currency_code }}</td>
                    <td class="text-end">{{ number_format((float) $quotation->subtotal, 2) }}</td>
                    <td class="text-end">{{ number_format((float) $quotation->tax_total, 2) }}</td>
                    <td class="text-end">{{ number_format((float) $quotation->wht_tax_total, 2) }}</td>
                    <td class="text-end">{{ number_format((float) $quotation->total, 2) }}</td>
                    <td>{{ $quotation->isAwarded() ? 'Products awarded' : $quotation->status->label() }}</td>
                </tr>
            @endforeach
        </tbody>
        </table>
    </div>
</div>
</div>
@endif
@include('procurement::rfqs.award-orders')
</x-default-layout>
