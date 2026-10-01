<x-default-layout>
@section('title', $quotation->number)
@section('sub-title')
 <div>
        <div class="text-muted small">
            Dated {{ $quotation->quotation_date->format('Y-m-d') }}
            @if($quotation->valid_until) · Valid until {{ $quotation->valid_until->format('Y-m-d') }}@endif
            · {{ $quotation->currency_code }}
    <span class="badge badge-{{ $quotation->status->badgeClass() }} ms-2">{{ $quotation->status->label() }}</span>
    </div>
</div>
@endsection

@section('toolbar-button')
 @can('send', $quotation)
            <form method="POST" action="{{ route('sales.quotations.send', $quotation) }}">
                @csrf @method('PATCH')
                <button class="btn btn-sm btn-primary">Mark as Sent</button>
            </form>
        @endcan
        @can('accept', $quotation)
            <form method="POST" action="{{ route('sales.quotations.accept', $quotation) }}">
                @csrf @method('PATCH')
                <button class="btn btn-sm btn-success">Accept</button>
            </form>
            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectQtnModal">Reject</button>
        @endcan
        @can('convert', $quotation)
            <form method="POST" action="{{ route('sales.quotations.convert', $quotation) }}">
                @csrf
                <button class="btn btn-sm btn-success" onclick="return confirm('Create a Sales Order from this quotation?')">
                    Convert to Sales Order
                </button>
            </form>
        @endcan
        @can('cancel', $quotation)
            <form method="POST" action="{{ route('sales.quotations.cancel', $quotation) }}">
                @csrf @method('PATCH')
                <button class="btn btn-sm btn-outline-secondary" onclick="return confirm('Cancel this quotation?')">Cancel</button>
            </form>
        @endcan
        @can('update', $quotation)
            <a href="{{ route('sales.quotations.edit', $quotation) }}" class="btn btn-sm btn-outline-primary">Edit</a>
        @endcan
        @can('delete', $quotation)
            <form method="POST" action="{{ route('sales.quotations.destroy', $quotation) }}" class="d-inline"
                  onsubmit="return confirm('Delete this draft quotation?');">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger">Delete</button>
            </form>
        @endcan

@endsection

@php
    // ── WHT is stored on lines, not the header. Aggregate here. ─────
    $subtotal = (string) $quotation->subtotal;
    $discount = (string) $quotation->discount_total;
    $tax      = (string) $quotation->tax_total;

    // If the header has a wht_tax_total column, use it. Otherwise sum the lines.
    $wht = isset($quotation->wht_tax_total) && $quotation->wht_tax_total !== null
        ? (string) $quotation->wht_tax_total
        : (string) $quotation->lines->sum('line_wht_tax');

    // Compute the total from first principles so it always matches the lines.
    $computedTotal = bcsub(bcadd($subtotal, $tax, 4), $wht, 4);
@endphp

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
                        <a href="{{ route('customers.show', $quotation->customer_id) }}">{{ $quotation->customer?->name }}</a>
                    </dd>
                    <dt class="col-sm-4">Salesperson</dt><dd class="col-sm-8">{{ $quotation->salesperson?->name ?? '—' }}</dd>
                    <dt class="col-sm-4">Warehouse</dt><dd class="col-sm-8">{{ $quotation->warehouse?->name ?? '—' }}</dd>
                    <dt class="col-sm-4">Payment Term</dt><dd class="col-sm-8">{{ $quotation->paymentTerm?->name ?? '—' }}</dd>
                    <dt class="col-sm-4">Reference</dt><dd class="col-sm-8">{{ $quotation->reference ?: '—' }}</dd>

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
                    <span>{{ number_format((float) $subtotal, 4) }}</span>
                </div>
                @if(bccomp($discount, '0', 4) > 0)
                    <div class="d-flex justify-content-between">
                        <span>Discount</span>
                        <span>− {{ number_format((float) $discount, 4) }}</span>
                    </div>
                @endif
                <div class="d-flex justify-content-between">
                    <span>GST</span>
                    <span>{{ number_format((float) $tax, 4) }}</span>
                </div>
                @if(bccomp($wht, '0', 4) > 0)
                    <div class="d-flex justify-content-between">
                        <span>WHT </span>
                        <span> {{ number_format((float) $wht, 4) }}</span>
                    </div>

                @endif
                <hr class="my-1">
                <div class="d-flex justify-content-between fw-semibold fs-5">
                    <span>Total</span>
                    <span>{{ number_format((float) $computedTotal, 4) }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header">
            <h3 class="card-title">Lines</h3>
            <div class="card-toolbar">
        <span class="text-muted small">{{ $quotation->lines->count() }} line(s)</span>
        </div>
    </div>
    <div class="card-body">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
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
                @foreach($quotation->lines as $i => $line)
                    <tr>
                        <td class="text-muted">{{ $i + 1 }}</td>
                        <td>
                            <code>{{ $line->item?->code }}</code> {{ $line->item?->name }}
                            @if($line->description)
                                <div class="text-muted small">{{ $line->description }}</div>
                            @endif
                        </td>
                        <td>{{ $line->unit?->name }}</td>
                        <td class="text-end">{{ number_format((float) $line->quantity, 4) }}</td>
                        <td class="text-end">{{ number_format((float) $line->unit_price, 4) }}</td>
                        <td class="text-end">{{ number_format((float) $line->discount_percent, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $line->tax_rate, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $line->wht_tax_rate, 2) }}</td>
                        <td class="text-end fw-semibold">{{ number_format((float) $line->line_total, 4) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <td colspan="8" class="text-end fw-semibold">Totals</td>

                    <td class="text-end fw-semibold">{{ number_format((float) $computedTotal, 4) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
    </div>
</div>

@can('reject', $quotation)
    <div class="modal fade" id="rejectQtnModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('sales.quotations.reject', $quotation) }}" class="modal-content">
                @csrf @method('PATCH')
                <div class="modal-header"><h5 class="modal-title">Reject Quotation</h5></div>
                <div class="modal-body">
                    <label class="form-label">Reason</label>
                    <textarea name="reason" rows="3" class="form-control" maxlength="500"></textarea>
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
