<x-default-layout>
@section('title', $rfq->number)

@section('sub-title')
        <div>
            @if($rfq->awardedQuotation)
                <span class="badge badge-light-success ms-1">
                    Awarded to {{ $rfq->awardedQuotation->vendor?->name ?? 'Unavailable vendor' }}
                </span>
            @endif
            <div class="text-muted fs-7">
                Issued {{ $rfq->issue_date->format('d M Y') }}
                · Deadline {{ $rfq->due_date->format('d M Y') }}
                · {{ $rfq->currency_code }}

            <span class="badge badge-light-{{ $rfq->status->badgeClass() }} ms-2">
                {{ $rfq->status->label() }}
            </span>
            @if($rfq->awardedQuotation)
                <span class="badge badge-light-success ms-1">
                    Awarded to {{ $rfq->awardedQuotation->vendor?->name ?? 'Unavailable vendor' }}
                </span>
            @endif
            </div>
        </div>
@endsection

@section('toolbar-button')
    @if($rfq->status->value === 'awarded')
        @can('view', $rfq)
            <a href="{{ route('procurement.rfqs.print', $rfq) }}"
                class="btn btn-sm btn-light"
                target="_blank"
                rel="noopener">
                    <i class="bi bi-printer"></i> Print Award
            </a>
        @endcan
    @endif

        @can('issue', $rfq)
            <form method="POST" action="{{ route('procurement.rfqs.issue', $rfq) }}" class="d-inline">
                @csrf @method('PATCH')
                <button class="btn btn-sm btn-primary"
                        onclick="return confirm('Issue this RFQ to all invited vendors?')">
                    Issue to Vendors
                </button>
            </form>
        @endcan

        @if(in_array($rfq->status->value, ['issued', 'receiving', 'awarded', 'closed']))
            <a href="{{ route('procurement.rfqs.compare', $rfq) }}" class="btn btn-sm btn-light-primary">
                <i class="bi bi-columns-gap"></i> Compare Quotes
            </a>
        @endif

        @if(in_array($rfq->status->value, ['issued', 'receiving']))
            @can('view', $rfq)
                <a href="{{ route('procurement.quotations.create', $rfq) }}" class="btn btn-sm btn-light">
                    <i class="bi bi-plus-lg"></i> Record Quotation
                </a>
            @endcan
        @endif

        @can('close', $rfq)
            @if($rfq->status->value === 'awarded')
                <form method="POST" action="{{ route('procurement.rfqs.close', $rfq) }}" class="d-inline">
                    @csrf @method('PATCH')
                    <button class="btn btn-sm btn-light"
                            onclick="return confirm('Close this RFQ?')">
                        Close
                    </button>
                </form>
            @endif
        @endcan

        @can('cancel', $rfq)
            @if($rfq->status->canCancel())
                <button class="btn btn-sm btn-light-danger"
                        data-bs-toggle="modal"
                        data-bs-target="#cancelRfqModal">
                    Cancel
                </button>
            @endif
        @endcan

        @can('update', $rfq)
            <a href="{{ route('procurement.rfqs.edit', $rfq) }}" class="btn btn-sm btn-light-primary">Edit</a>
        @endcan

        @can('delete', $rfq)
            <form method="POST" action="{{ route('procurement.rfqs.destroy', $rfq) }}"
                  class="d-inline"
                  onsubmit="return confirm('Delete this draft RFQ?');">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-light-danger">Delete</button>
            </form>
        @endcan
        <a href="{{ route('procurement.rfqs.index') }}" class="btn btn-sm btn-light-secondary btn-sm">
            <i class="fas fa-arrow-left fa-sm"></i>  Back
        </a>
 @endsection


@if(session('status'))
    <div class="alert alert-success mb-6" role="status">{{ session('status') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger mb-6" role="alert">
        <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<div class="row g-8 mb-6">
    <div class="col-6 col-xl-3">
        <div class="card h-100"><div class="card-body p-5">
            <div class="text-muted fs-7 mb-2">Response deadline</div>
            <div class="fs-8 fw-bold text-gray-900">{{ $rfq->due_date->format('d M Y') }}</div>
        </div></div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card h-100"><div class="card-body p-5">
            <div class="text-muted fs-7 mb-2">Requested items</div>
            <div class="fs-4 fw-bold text-gray-900">{{ $rfq->lines->count() }}</div>
        </div></div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card h-100"><div class="card-body p-5">
            <div class="text-muted fs-7 mb-2">Invited vendors</div>
            <div class="fs-4 fw-bold text-gray-900">{{ $rfq->vendors->count() }}</div>
        </div></div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card h-100"><div class="card-body p-5">
            <div class="text-muted fs-7 mb-2">Quotations recorded</div>
            <div class="fs-4 fw-bold text-gray-900">{{ $rfq->quotations->count() }}</div>
        </div></div>
    </div>
</div>

{{-- Summary --}}
<div class="row g-6 mb-6">
    <div class="col-md-6">
        <div class="card card-flush h-100">
            <div class="card-header align-items-center fw-bold fs-5">Summary</div>
            <div class="card-body">
                <div class="fw-semibold mb-2">{{ $rfq->purpose }}</div>
                <dl class="row mb-0 small">
                    <dt class="col-sm-4">Department</dt>
                    <dd class="col-sm-8">{{ $rfq->department?->name ?? '—' }}</dd>
                    <dt class="col-sm-4">Cost Center</dt>
                    <dd class="col-sm-8">{{ $rfq->costCenter?->name ?? '—' }}</dd>
                    <dt class="col-sm-4">Created By</dt>
                    <dd class="col-sm-8">{{ $rfq->creator?->name ?? '—' }} · {{ $rfq->created_at?->format('Y-m-d H:i') }}</dd>
                    @if($rfq->issued_at)
                        <dt class="col-sm-4">Issued</dt>
                        <dd class="col-sm-8">
                            {{ $rfq->issuer?->name }} · {{ $rfq->issued_at->format('Y-m-d H:i') }}
                        </dd>
                    @endif
                    @if($rfq->awarded_at)
                        <dt class="col-sm-4">Awarded</dt>
                        <dd class="col-sm-8">
                            {{ $rfq->awarder?->name }} · {{ $rfq->awarded_at->format('Y-m-d H:i') }}
                        </dd>
                    @endif
                </dl>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card card-flush h-100">
            <div class="card-header align-items-center fw-bold fs-5">Linked Requisitions</div>
            <div class="card-body">
                @forelse($rfq->sourceRequisitions as $pr)
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                        <div>
                            <code>{{ $pr->number }}</code>
                            <div class="text-muted fs-7">{{ \Illuminate\Support\Str::limit($pr->purpose, 60) }}</div>
                        </div>
                        <a href="{{ route('procurement.purchase-requisitions.show', $pr) }}"
                           class="btn btn-sm btn-light">View</a>
                    </div>
                @empty
                    <div class="text-muted">No linked requisitions — this RFQ was created standalone.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- Lines --}}
<div class="card card-flush mb-6">
    <div class="card-header align-items-center fw-bold fs-5 d-flex justify-content-between">
        <span>Requested items</span>
        <span class="text-muted fs-7">{{ $rfq->lines->count() }} line(s)</span>
    </div>
    <div class="card-body pt-0"><div class="table-responsive">
        <table class="table table-row-dashed align-middle gy-5 mb-0">
            <thead class="text-muted fs-7">
                <tr>
                    <th>#</th>
                    <th>Item</th>
                    <th>Specification</th>
                    <th class="text-end">Quantity</th>
                    <th>Unit</th>

                </tr>
            </thead>
            <tbody>
                @forelse($rfq->lines as $i => $line)
                    <tr>
                        <td class="text-muted">{{ $i + 1 }}</td>
                        <td>

                            {{ $line->item?->name }}



                            @if(filled($line->brand?->name))
                                <span class="d-block small text-muted">Brand: {{ $line->brand?->name ?? "—" }}</span>
                            @endif
                            @if(filled($line->origin?->name))
                                <span class="d-block small text-muted">Origin: {{ $line->origin?->name ?? "—" }}</span>
                            @endif
                        </td>
                        <td class="text-muted fs-7">{{ $line->specification ?: '—' }}</td>
                        <td class="text-end">{{ rtrim(rtrim(number_format((float) $line->quantity, 4, '.', ','), '0'), '.') }}</td>
                        <td>{{ $line->unit?->code ?? '—' }}</td>

                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-8">No lines.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div></div>
</div>

{{-- Vendors --}}
<div class="card card-flush mb-6">
    <div class="card-header align-items-center fw-bold fs-5 d-flex justify-content-between">
        <span>Invited Vendors</span>
        @if($rfq->status->canReceive())
            @can('view', $rfq)
                <a href="{{ route('procurement.rfqs.print-request', $rfq) }}" class="btn btn-sm btn-light-primary" target="_blank" rel="noopener">
                    <i class="bi bi-printer" aria-hidden="true"></i> Print RFQ for Vendors
                </a>
            @endcan
        @endif
        <span class="text-muted fs-7">{{ $rfq->vendors->count() }} vendor(s)</span>
    </div>
    <div class="card-body pt-0"><div class="table-responsive">
        <table class="table table-row-dashed align-middle gy-5 mb-0">
            <thead class="text-muted fs-7">
                <tr>
                    <th>Vendor</th>
                    <th>Status</th>
                    <th>Invited</th>
                    <th>Submitted</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rfq->vendors as $rv)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $rv->vendor?->name }}</div>

                        </td>
                        <td>
                            <span class="badge badge-light-{{ $rv->status->badgeClass() }}">
                                {{ $rv->status->label() }}
                            </span>
                        </td>
                        <td class="small text-muted">
                            {{ $rv->invited_at?->format('Y-m-d H:i') ?? '—' }}
                        </td>
                        <td class="small text-muted">
                            {{ $rv->submitted_at?->format('Y-m-d H:i') ?? '—' }}
                        </td>
                        <td class="text-end">
                            @if($rv->vendor)
                                @if($rfq->status->canReceive())
                                    @can('view', $rfq)
                                        <a href="{{ route('procurement.rfqs.print-request', ['rfq' => $rfq, 'vendor' => $rv->vendor]) }}"
                                           class="btn btn-sm btn-light-primary" target="_blank" rel="noopener"
                                           aria-label="Print RFQ for {{ $rv->vendor->name }}">
                                            <i class="bi bi-printer" aria-hidden="true"></i> Print RFQ
                                        </a>
                                    @endcan
                                @endif
                                <a href="{{ route('vendors.show', $rv->vendor) }}"
                                   class="btn btn-sm btn-light">View Vendor</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-8">No vendors invited.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div></div>
</div>

{{-- Quotations --}}
<div class="card">
    <div class="card-header">
            <h3 class="card-title">Vendor Quotations</h3>
        @if(in_array($rfq->status->value, ['issued', 'receiving']))
            @can('view', $rfq)
                <a href="{{ route('procurement.quotations.create', $rfq) }}"
                   class="btn btn-sm btn-light">
                    <i class="bi bi-plus-lg"></i> Record Quotation
                </a>
            @endcan
        @endif
    </div>
    <div class="card-body">
        <div class="table-responsive">
        <table class="table table-row-dashed mb-0">
            <thead>
                <tr class="fw-bold fs-6 text-gray-800">
                    <th>Vendor</th>
                    <th>Reference</th>
                    <th>Quoted</th>
                    <th>Valid Until</th>
                    <th class="text-end text-nowrap">Subtotal</th>
                    <th class="text-end">GST</th>
                    <th class="text-end">WHT</th>
                    <th class="text-end text-nowrap">Total / currency</th>
                    <th>Lead</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rfq->quotations as $q)
                    <tr class="{{ $q->isAwarded() ? 'table-success' : '' }}">
                        <td>
                            <div class="fw-semibold">{{ $q->vendor?->name }}</div>
                        </td>
                        <td>{{ $q->reference ?: '—' }}</td>
                        <td>{{ $q->quoted_at->format('d M Y') }}</td>
                        <td>{{ $q->valid_until?->format('d M Y') ?? '—' }}</td>
                        <td class="text-end">{{ number_format((float) $q->subtotal, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $q->tax_total, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $q->wht_tax_total, 2) }}</td>
                        <td class="text-end fw-semibold">{{ number_format((float) $q->total, 2) }}<span class="d-block text-muted fs-8 fw-normal">{{ $q->currency_code }}</span></td>
                        <td class="small">{{ $q->lead_time_days ? $q->lead_time_days . ' d' : '—' }}</td>
                        <td>
                            <span class="badge badge-light-{{ $q->status->badgeClass() }}">
                                {{ $q->status->label() }}
                            </span>
                        </td>
                        <td class="text-end">
                            @if($q->status->value === 'draft')
                                <form method="POST"
                                      action="{{ route('procurement.quotations.submit', $q) }}"
                                      class="d-inline">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-sm btn-light-primary">Submit</button>
                                </form>
                            @endif
                            @if($q->status->value === 'submitted' && $rfq->status->canAward())
                                @can('award', $rfq)
                                    <a href="{{ route('procurement.rfqs.compare', $rfq) }}" class="btn btn-sm btn-success">Award Products</a>
                                @endcan
                            @endif
                            @if($q->isAwarded())
                                <span class="badge badge-light-success">Products awarded</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center text-muted py-8">
                            No quotations recorded yet.
                            @if(in_array($rfq->status->value, ['issued', 'receiving']))
                                @can('view', $rfq)
                                    <a href="{{ route('procurement.quotations.create', $rfq) }}">Record the first one</a>.
                                @endcan
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div></div>
</div>

@if($rfq->status->value === 'awarded')
    <div class="alert alert-success">
        Products have been awarded. <a href="{{ route('procurement.rfqs.compare', $rfq) }}">View the selected vendor for each product.</a>
    </div>
@endif

{{-- Terms --}}
@if($rfq->terms)
    <div class="card card-flush mt-5">
        <div class="card-header pt-0">
            <h3 class="card-title">Terms &amp; Conditions</h3>
        </div>
        <div class="card-body pt-1" style="white-space: pre-wrap;">{{ $rfq->terms }}</div>
    </div>
@endif
@include('procurement::rfqs.award-orders')
{{-- Cancel modal --}}
@can('cancel', $rfq)
    @if($rfq->status->canCancel())
        <div class="modal fade" id="cancelRfqModal" tabindex="-1" aria-labelledby="cancel-rfq-title" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('procurement.rfqs.cancel', $rfq) }}" class="modal-content">
                    @csrf @method('PATCH')
                    <div class="modal-header">
                        <h5 class="modal-title" id="cancel-rfq-title">Cancel {{ $rfq->number }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2">
                            Cancelling will prevent further quotations and awards.
                            Vendors already invited should be notified through your usual channel.
                        </p>
                        <label class="form-label small" for="cancel-reason">Reason</label>
                        <textarea id="cancel-reason" name="reason" rows="3" class="form-control" maxlength="500"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Keep RFQ</button>
                        <button type="submit" class="btn btn-danger">Cancel RFQ</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endcan
</x-default-layout>
