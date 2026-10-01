<x-default-layout>

@section('title', $requisition->number)

@section('sub-title')
    <span class="badge badge-{{ $requisition->status->badgeClass() }} me-2">
        {{ $requisition->status->label() }}
    </span>
     Requested {{ $requisition->requested_date->format('d/m/Y') }}
            @if($requisition->required_date) · Needed by {{ $requisition->required_date->format('d/m/Y') }}@endif
@endsection


  @section('toolbar-button')
        <div class="d-flex justify-content-between align-items-start mb-3">

    <div class="d-flex gap-2 flex-wrap">
        @can('submit', $requisition)
            <form method="POST" action="{{ route('procurement.purchase-requisitions.submit', $requisition) }}">
                @csrf @method('PATCH')
                <button class="btn btn-sm btn-primary">Submit</button>
            </form>
        @endcan
        @can('review', $requisition)
            <form method="POST" action="{{ route('procurement.purchase-requisitions.start-review', $requisition) }}">
                @csrf @method('PATCH')
                <button class="btn btn-sm btn-warning">Start Review</button>
            </form>
        @endcan
        @can('approve', $requisition)
            <form method="POST" action="{{ route('procurement.purchase-requisitions.approve', $requisition) }}">
                @csrf @method('PATCH')
                <button class="btn btn-sm btn-success">Approve</button>
            </form>
            <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#prRejectModal">Reject</button>
        @endcan
        @can('cancel', $requisition)
            <form method="POST" action="{{ route('procurement.purchase-requisitions.cancel', $requisition) }}">
                @csrf @method('PATCH')
                <button class="btn btn-sm btn-secondary"
                        onclick="return confirm('Cancel this requisition?')">Cancel</button>
            </form>
        @endcan
        @can('close', $requisition)
            @if(in_array($requisition->status->value, ['approved', 'converted']))
                <form method="POST" action="{{ route('procurement.purchase-requisitions.close', $requisition) }}">
                    @csrf @method('PATCH')
                    <button class="btn btn-sm btn-dark">Close</button>
                </form>
            @endif
        @endcan
        @if($requisition->status->canConvert())
            @can('convertToRfq', $requisition)
                <a href="{{ route('procurement.purchase-requisitions.convert-to-rfq', $requisition) }}" class="btn btn-sm btn-primary">Convert to RFQ</a>
            @endcan
        @endif
        @foreach($requisition->rfqs as $linkedRfq)
            @can('rfq.view', $linkedRfq)
                <a href="{{ route('procurement.rfqs.show', $linkedRfq) }}" class="btn btn-sm btn-primary">View RFQ {{ $linkedRfq->number }}</a>
            @endcan
        @endforeach
        @can('update', $requisition)
            <a href="{{ route('procurement.purchase-requisitions.edit', $requisition) }}" class="btn btn-sm btn-primary">Edit</a>
        @endcan
    </div>
</div>
    @endsection

<div class="row g-3 mb-3">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="fw-semibold mb-1">{{ $requisition->purpose }}</div>
                @if($requisition->notes)
                    <div class="text-muted small" style="white-space:pre-wrap;">{{ $requisition->notes }}</div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body small">
                <div class="d-flex justify-content-between"><span>Requester</span><span>{{ $requisition->requester?->name ?? '—' }}</span></div>
                <div class="d-flex justify-content-between"><span>Department</span><span>{{ $requisition->department?->name ?? '—' }}</span></div>
                <div class="d-flex justify-content-between"><span>Warehouse</span><span>{{ $requisition->warehouse?->name ?? '-' }}</span></div>
                <div class="d-flex justify-content-between"><span>Cost Center</span><span>{{ $requisition->costCenter?->name ?? '—' }}</span></div>
                @if($requisition->submitted_at)
                    <hr>
                    <div class="d-flex justify-content-between"><span>Submitted</span><span>{{ $requisition->submitter?->name }} · {{ $requisition->submitted_at->format('d/m/Y H:i') }}</span></div>
                @endif
                @if($requisition->approved_at)
                    <div class="d-flex justify-content-between"><span>Approved</span><span>{{ $requisition->approver?->name }} · {{ $requisition->approved_at->format('d/m/Y H:i') }}</span></div>
                @endif
                @if($requisition->rejected_by)
                    <div class="d-flex justify-content-between text-danger"><span>Rejected</span><span>{{ $requisition->rejecter?->name }}</span></div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">
    <div class="card-title">Lines</div>
    </div>
    <div class="card-body">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Item</th>
                    <th>Specification</th>
                    <th class="text-end">Qty</th>
                    <th>Unit</th>
                    <th class="text-end">Est. Unit Price</th>
                    <th class="text-end">Line Total</th>
                    <th>Required</th>
                </tr>
            </thead>
            <tbody>
                @foreach($requisition->lines as $i => $line)
                    <tr>
                        <td class="text-muted">{{ $i + 1 }}</td>
                        <td>{{ $line->item?->name }}

                            @if(filled($line->brand?->name))
                                <span class="d-block small text-muted">Brand: {{ $line->brand?->name ?? "—" }}</span>
                            @endif
                            @if(filled($line->origin?->name))
                            <span class="d-block small text-muted">Origin: {{ $line->origin?->name ?? "—" }}</span>
                            @endif
                        </td>
                        <td class="text-muted small">{{ $line->specification }}</td>
                        <td class="text-end">{{ number_format((float) $line->quantity, 2) }}</td>
                        <td>{{ $line->unit?->code ?? '—' }}</td>
                        <td class="text-end">{{ number_format((float) $line->estimated_unit_price, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $line->estimated_line_total, 2) }}</td>
                        <td>{{ $line->required_date?->format('d/m/Y') ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6" class="text-end">Estimated Total</td>
                    <td class="text-end">{{ number_format((float) $requisition->total_estimated, 2) }}</td>
                    <td></td>
                </tr>
                 <tr>
                     @php
                        $formatter = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);
                    @endphp
                    <td colspan="7" class="text-center fw-bold">
                        {{ ucfirst($formatter->format((float) $requisition->total_estimated)) }} rupees only.
                    </td>


                </tr>
            </tfoot>
        </table>
        </div>
    </div>
    </div>
</div>

@can('reject', $requisition)
    <div class="modal fade" id="prRejectModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('procurement.purchase-requisitions.reject', $requisition) }}" class="modal-content">
                @csrf @method('PATCH')
                <div class="modal-header"><h5 class="modal-title">Reject Requisition</h5></div>
                <div class="modal-body">
                    <label class="form-label">Reason <span class="text-danger">*</span></label>
                    <textarea name="reason" class="form-control" rows="3" required maxlength="500"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject</button>
                </div>
            </form>
        </div>
    </div>
@endcan
</x-default-layout>
