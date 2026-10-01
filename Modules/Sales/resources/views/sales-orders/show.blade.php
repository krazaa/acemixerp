<x-default-layout>
@section('title', $salesOrder->number)

@section('sub-title')
     <div class="text-muted small">
            Ordered {{ $salesOrder->order_date->format('d/m/Y') }}
            @if($salesOrder->expected_delivery_date)
                · Expected delivery {{ $salesOrder->expected_delivery_date->format('d/m/Y') }}
            @endif
            · {{ $salesOrder->currency_code }}
             <span class="badge text-bg-{{ $salesOrder->status->badgeClass() }} ms-2">
                {{ $salesOrder->status->label() }}
            </span>
        </div>
@endsection

@section('toolbar-button')
    @can('submit', $salesOrder)
        <form method="POST" action="{{ route('sales.sales-orders.submit', $salesOrder) }}">
            @csrf @method('PATCH')
            <button class="btn btn-sm btn-primary">Submit</button>
        </form>
    @endcan
    @can('approve', $salesOrder)
        <form method="POST" action="{{ route('sales.sales-orders.approve', $salesOrder) }}">
            @csrf @method('PATCH')
            <button class="btn btn-sm btn-success">Approve</button>
        </form>
        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectSoModal">Reject</button>
    @endcan
    @can('releaseHold', $salesOrder)
        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#releaseHoldModal">
            Release Credit Hold
        </button>
    @endcan
    @can('confirm', $salesOrder)
        <form method="POST" action="{{ route('sales.sales-orders.confirm', $salesOrder) }}">
            @csrf @method('PATCH')
            <button class="btn btn-sm btn-success"
                    onclick="return confirm('Confirm this order? It becomes immutable and ready for delivery.')">
                Confirm
            </button>
        </form>
    @endcan
    @can('close', $salesOrder)
        @if(in_array($salesOrder->status->value, ['delivered', 'partially_delivered']))
            <form method="POST" action="{{ route('sales.sales-orders.close', $salesOrder) }}">
                @csrf @method('PATCH')
                <button class="btn btn-sm btn-outline-dark" onclick="return confirm('Close this order?')">Close</button>
            </form>
        @endif
    @endcan
    @can('cancel', $salesOrder)
        <form method="POST" action="{{ route('sales.sales-orders.cancel', $salesOrder) }}">
            @csrf @method('PATCH')
            <button class="btn btn-sm btn-outline-secondary" onclick="return confirm('Cancel this order?')">Cancel</button>
        </form>
    @endcan
    @can('update', $salesOrder)
        <a href="{{ route('sales.sales-orders.edit', $salesOrder) }}" class="btn btn-sm btn-outline-primary">Edit</a>
    @endcan
    @can('delete', $salesOrder)
        <form method="POST" action="{{ route('sales.sales-orders.destroy', $salesOrder) }}" class="d-inline"
                onsubmit="return confirm('Delete this draft order?');">
            @csrf @method('DELETE')
            <button class="btn btn-sm btn-outline-danger">Delete</button>
        </form>
    @endcan
@endsection

@if($salesOrder->source)
    <div class="alert alert-info">
        Created from quotation
        <a href="{{ route('sales.quotations.show', $salesOrder->source) }}">
            {{ $salesOrder->source->number }}
        </a>
        on {{ $salesOrder->created_at?->format('Y-m-d') }}.
    </div>
@endif

@if($salesOrder->status->value === 'on_hold')
    <div class="alert alert-warning">
        <strong>Credit Hold</strong>
        <div>{{ $salesOrder->credit_hold_reason }}</div>
        @if($salesOrder->credit_limit_at_submission !== null)
            <div class="small mt-1">
                Credit limit: {{ number_format((float) $salesOrder->credit_limit_at_submission, 2) }}
                · Outstanding at submission: {{ number_format((float) $salesOrder->outstanding_ar_at_submission, 2) }}
                · This order: {{ number_format((float) $salesOrder->total, 2) }}
            </div>
        @endif
    </div>
@endif

<div class="row g-3 mb-3">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header">
                <h3 class="card-title">Details</h3>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Customer</dt>
                    <dd class="col-sm-8">
                        <a href="{{ route('customers.show', $salesOrder->customer_id) }}">{{ $salesOrder->customer?->name }}</a>
                    </dd>
                    <dt class="col-sm-4">Salesperson</dt><dd class="col-sm-8">{{ $salesOrder->salesperson?->fullName() ?? '—' }}</dd>
                    {{-- <dt class="col-sm-4">Warehouse</dt><dd class="col-sm-8">{{ $salesOrder->warehouse?->name ?? '—' }}</dd> --}}
                    <dt class="col-sm-4">Payment Term</dt><dd class="col-sm-8">{{ $salesOrder->paymentTerm?->name ?? '—' }}</dd>
                    @if(!empty($salesOrder->reference))
                    <dt class="col-sm-4">Reference</dt><dd class="col-sm-8">{{ $salesOrder->reference ?: '—' }}</dd>
                    @endif
                </dl>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header">
                <h3 class="card-title">Totals</h3>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <span>Subtotal</span>
                    <span>{{ number_format((float) $salesOrder->subtotal, 2) }}</span>
                </div>
                @if(bccomp((string) $salesOrder->discount_total, '0', 2) > 0)
                    <div class="d-flex justify-content-between">
                        <span>Discount</span>
                        <span>− {{ number_format((float) $salesOrder->discount_total, 2) }}</span>
                    </div>
                @endif
                <div class="d-flex justify-content-between">
                    <span>GST</span>
                    <span>{{ number_format((float) $salesOrder->tax_total, 2) }}</span>
                </div>
                @if(bccomp((string) $salesOrder->wht_tax_total, '0', 2) > 0)
                    <div class="d-flex justify-content-between text-danger">
                        <span>WHT (withheld)</span>
                        <span>− {{ number_format((float) $salesOrder->wht_tax_total, 4) }}</span>
                    </div>
                @endif
                <hr class="my-1">
                <div class="d-flex justify-content-between fw-semibold fs-5">
                    <span>Total</span>
                    <span>{{ number_format((float) $salesOrder->total, 2) }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header">
         <h3 class="card-title">Lines</h3>
        <div class="card-toolbar">
            <span class="text-muted small">{{ $salesOrder->lines->count() }} line(s)</span>
        </div>
    </div>
    <div class="card-body">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
			    <tr class="fw-bold fs-6 text-gray-800">
                    <th>#</th>
                    <th>Item</th>
                    <th>Unit</th>
                    <th class="text-end">Qty</th>
                    <th class="text-end">Unit Price</th>
                    <th class="text-end">Disc %</th>
                    <th class="text-end">GST %</th>
                    <th class="text-end">WHT %</th>
                    <th class="text-end">Line Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($salesOrder->lines as $i => $line)
                    <tr>
                        <td class="text-muted">{{ $i + 1 }}</td>
                        <td>
                            <code>{{ $line->item?->code }}</code> {{ $line->item?->name }}
                            <div class="small text-muted">Brand: {{ $line->brand?->name ?? 'Not specified' }} | Origin: {{ $line->origin?->name ?? 'Not specified' }}</div>
                            @if($line->description)
                                <div class="text-muted small">{{ $line->description }}</div>
                            @endif
                        </td>
                        <td>{{ $line->unit?->code ?? '—' }}</td>
                        <td class="text-end">{{ number_format((float) $line->quantity, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $line->unit_price, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $line->discount_percent, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $line->tax_rate, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $line->wht_tax_rate, 2) }}</td>
                        <td class="text-end fw-semibold">{{ number_format((float) $line->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
</div>

{{-- Modals --}}
@can('reject', $salesOrder)
    <div class="modal fade" id="rejectSoModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('sales.sales-orders.reject', $salesOrder) }}" class="modal-content">
                @csrf @method('PATCH')
                <div class="modal-header"><h5 class="modal-title">Reject Sales Order</h5></div>
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

@can('releaseHold', $salesOrder)
    <div class="modal fade" id="releaseHoldModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('sales.sales-orders.release-hold', $salesOrder) }}" class="modal-content">
                @csrf @method('PATCH')
                <div class="modal-header"><h5 class="modal-title">Release Credit Hold</h5></div>
                <div class="modal-body">
                    <p>Releasing the hold means extending credit beyond the customer's limit for this specific order.</p>
                    <label class="form-label">Reason <span class="text-danger">*</span></label>
                    <textarea name="reason" class="form-control" rows="3" required minlength="3" maxlength="500"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Release Hold</button>
                </div>
            </form>
        </div>
    </div>
@endcan
</x-default-layout>
