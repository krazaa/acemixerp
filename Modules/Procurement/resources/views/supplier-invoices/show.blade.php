<x-default-layout>
@section('title', 'Supplier Invoice - '.$invoice->number)

@section('sub-title')


        <div class="text-muted small mt-2">
            Vendor Ref <strong>{{ $invoice->vendor_invoice_number }}</strong>
            · Dated {{ $invoice->invoice_date->format('Y-m-d') }}
            · Due {{ $invoice->due_date->format('Y-m-d') }}
            · {{ $invoice->currency_code }}

            </span>
            <span class="badge badge-{{ $invoice->status->badgeClass() }} ms-2">
            {{ $invoice->status->label() }}
            </span>
            @if($invoice->purchase_order_id)
                <span class="badge badge-light-{{ $invoice->match_status === 'matched' ? 'success' : ($invoice->match_status === 'mismatch' ? 'danger' : 'secondary') }} ms-1">
                    Match: {{ ucfirst($invoice->match_status) }}
                </span>
            @else
                <span class="badge badge-info ms-1">No PO / match not required</span>
            @endif
        </div>
@endsection

@section('toolbar-button')
 @php($postedPayments = $invoice->paymentAllocations->pluck('payment')->filter(fn ($payment) => $payment?->status?->value === 'posted'))
        @if($postedPayments->count() === 1)
            <a href="{{ route('procurement.vendor-payments.voucher', $postedPayments->first()) }}"
               target="_blank"
               rel="noopener"
               class="btn btn-sm btn-outline-dark">
                <i class="bi bi-printer"></i> Print Payment Challan
            </a>
        @endif

        @can('match', $invoice)
            <form method="POST" action="{{ route('procurement.supplier-invoices.match', $invoice) }}">
                @csrf @method('PATCH')
                <button class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-list-check"></i> Run Three-Way Match
                </button>
            </form>
        @endcan

        @can('approve', $invoice)
            @if($invoice->match_status === 'mismatch')
                @can('overrideMismatch', $invoice)
                    <button class="btn btn-sm btn-warning"
                            data-bs-toggle="modal" data-bs-target="#approveMismatchModal">
                        Approve (Override Mismatch)
                    </button>
                @endcan
            @else
                <form method="POST" action="{{ route('procurement.supplier-invoices.approve', $invoice) }}">
                    @csrf @method('PATCH')
                    <button class="btn btn-sm btn-success">Approve</button>
                </form>
            @endif
            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectInvoiceModal">
                Reject
            </button>
        @endcan

        @can('post', $invoice)
            <form method="POST" action="{{ route('procurement.supplier-invoices.post', $invoice) }}">
                @csrf @method('PATCH')
                <button class="btn btn-sm btn-primary"
                        onclick="return confirm('Post this invoice to the general ledger? This action is irreversible.')">
                    Post to GL
                </button>
            </form>
        @endcan

        @can('cancel', $invoice)
            <form method="POST" action="{{ route('procurement.supplier-invoices.cancel', $invoice) }}">
                @csrf @method('PATCH')
                <button class="btn btn-sm btn-outline-secondary"
                        onclick="return confirm('Cancel this invoice?')">Cancel</button>
            </form>
        @endcan

        @can('update', $invoice)
            <a href="{{ route('procurement.supplier-invoices.edit', $invoice) }}" class="btn btn-sm btn-outline-primary">Edit</a>
        @endcan

        @can('delete', $invoice)
            <form method="POST" action="{{ route('procurement.supplier-invoices.destroy', $invoice) }}"
                  class="d-inline"
                  onsubmit="return confirm('Delete this draft invoice?');">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger">Delete</button>
            </form>
        @endcan
@endsection


@if($invoice->match_notes && $invoice->match_status === 'mismatch')
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i>
        {{ $invoice->match_notes }}
        @if($invoice->matched_at)
            <span class="text-muted small">
                · Checked {{ $invoice->matched_at->format('d/m/Y H:i') }}
                @if($invoice->matcher) by {{ $invoice->matcher->name }}@endif
            </span>
        @endif
    </div>
@endif

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header">
                    <h3 class="card-title">Details</h3>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Vendor</dt>
                    <dd class="col-sm-8">
                        <a href="{{ route('vendors.show', $invoice->vendor_id) }}">
                            {{ $invoice->vendor?->name }}
                        </a>
                    </dd>
                    <dt class="col-sm-4">Purchase Order</dt>
                    <dd class="col-sm-8">
                        @if($invoice->purchase_order_id)
                            <a href="{{ route('procurement.purchase-orders.show', $invoice->purchase_order_id) }}">
                                {{ $invoice->purchaseOrder?->number }}
                            </a>
                        @else
                            <span class="text-muted">Standalone vendor invoice</span>
                        @endif
                    </dd>
                    <dt class="col-sm-4">Vendor Ref</dt>
                    <dd class="col-sm-8"><code>{{ $invoice->vendor_invoice_number }}</code></dd>
                    <dt class="col-sm-4">Journal Entry</dt>
                    <dd class="col-sm-8">
                        @if($invoice->journalEntry)
                            <a href="{{ route('journals.show', $invoice->journalEntry) }}">
                                {{ $invoice->journalEntry->number }}
                            </a>
                        @else
                            —
                        @endif
                    </dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header">
                <h3 class="card-title">Totals</h3>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between"><span>Subtotal</span><span>{{ number_format((float) $invoice->subtotal, 2) }}</span></div>
                <div class="d-flex justify-content-between"><span>Input Tax</span><span>{{ number_format((float) $invoice->tax_total, 2) }}</span></div>
                <div class="d-flex justify-content-between text-danger"><span>Withholding Tax</span><span>− {{ number_format((float) $invoice->wht_tax_total, 2) }}</span></div>
                <hr class="my-1">
                <div class="d-flex justify-content-between fw-semibold fs-5"><span>Total</span><span>{{ number_format((float) $invoice->total, 2) }}</span></div>
                @if(bccomp((string) $invoice->paid_amount, '0', 2) > 0)
                    <div class="d-flex justify-content-between text-success mt-2"><span>Paid</span><span>{{ number_format((float) $invoice->paid_amount, 2) }}</span></div>
                    <div class="d-flex justify-content-between"><span>Outstanding</span><span>{{ number_format((float) $invoice->outstanding(), 2) }}</span></div>
                @endif
            </div>
        </div>
    </div>
</div>

@if($invoice->paymentAllocations->isNotEmpty())
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
            <span>Payments / Challans</span>
            <span class="text-muted small">{{ $invoice->paymentAllocations->count() }} payment(s)</span>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Payment</th>
                        <th>Date</th>
                        <th>Method</th>
                        <th class="text-end">Amount Applied</th>
                        <th class="text-end">Challan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->paymentAllocations as $allocation)
                        @php($payment = $allocation->payment)
                        <tr>
                            <td>
                                @if($payment)
                                    <a href="{{ route('procurement.vendor-payments.show', $payment) }}"><code>{{ $payment->number }}</code></a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $payment?->payment_date?->format('Y-m-d') ?? '—' }}</td>
                            <td>{{ $payment?->payment_method?->label() ?? '—' }}</td>
                            <td class="text-end fw-semibold">{{ number_format((float) $allocation->amount, 2) }}</td>
                            <td class="text-end">
                                @if($payment?->status?->value === 'posted')
                                    <a href="{{ route('procurement.vendor-payments.voucher', $payment) }}"
                                       target="_blank"
                                       rel="noopener"
                                       class="btn btn-sm btn-outline-dark">
                                        <i class="bi bi-printer"></i> Print Challan
                                    </a>
                                @else
                                    <span class="text-muted small">Available after posting</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-header">
        <h3 class="card-title">Lines</h3>
            <div class="card-toolbar">
        <span class="text-muted small">{{ $invoice->lines->count() }} line(s)</span>
        </div>
    </div>
    <div class="card-body">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Item</th>
                    <th class="text-end">Invoiced Qty</th>
                    <th class="text-end">Unit Price</th>
                    <th class="text-end">Tax %</th>
                    <th class="text-end">WHT %</th>
                    <th class="text-end">WHT</th>
                    <th class="text-end">Line Total</th>
                    <th>Debit Account</th>
                    <th>Match</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->lines as $i => $line)
                    <tr class="fw-bold fs-6 text-gray-800">
                        <td class="text-muted">{{ $i + 1 }}</td>
                        <td>
                            <code>{{ $line->item?->code }}</code>
                            {{ $line->item?->name }}

                            @if(filled($line->brand?->name))
                            <span class="d-block small text-muted">Brand: {{ $line->brand?->name ?? "—" }}</span>
                            @endif

                            @if(filled($line->origin?->name))
                            <span class="d-block small text-muted">Brand: {{ $line->origin?->name ?? "—" }}</span>
                            @endif

                            @if($line->description)
                                <div class="text-muted small">{{ $line->description }}</div>
                            @endif
                        </td>
                        <td class="text-end">{{ number_format((float) $line->quantity, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $line->unit_price, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $line->tax_rate, 2) }}%</td>
                        <td class="text-end">{{ number_format((float) $line->wht_rate, 2) }}%</td>
                        <td class="text-end text-danger">{{ number_format((float) $line->line_wht_tax, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $line->line_total_wht_tax, 2) }}</td>
                        <td class="small">
                            @if($line->debitAccount)
                                <code>{{ $line->debitAccount->code }}</code>
                                {{ $line->debitAccount->name }}
                            @else
                                <span class="text-muted">Resolved at posting</span>
                            @endif
                        </td>
                        <td>
                            @if($line->match_result === 'ok')
                                <span class="badge badge-success">Matched</span>
                            @elseif($line->match_result)
                                <span class="badge badge-danger">{{ $line->match_note ?? $line->match_result }}</span>
                            @else
                                <span class="badge badge-secondary">Pending</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="fw-semibold">
                <tr class="fw-bold fs-6 text-gray-800">
                    <td colspan="7" class="text-end">Total payable</td>
                    <td class="text-end">{{ number_format((float) $invoice->total, 2) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
        </div>
    </div>
</div>

@if($invoice->notes)
    <div class="card border-0 shadow-sm mt-3">
        <div class="card-header">
            <h3 class="card-title">Notes</h3>
        </div>
        <div class="card-body" style="white-space: pre-wrap;">{{ $invoice->notes }}</div>
    </div>
@endif

<div class="card border-0 shadow-sm mt-3">
    <div class="card-header">
        <h3 class="card-title">Audit</h3>
    </div>
    <div class="card-body small text-muted">
        <div>Created: {{ $invoice->created_at?->format('d/m/Y H:i') }}
            @if($invoice->creator) · {{ $invoice->creator->name }}@endif</div>
        @if($invoice->matched_at)
            <div>Matched: {{ $invoice->matched_at->format('d/m/Y H:i') }}
                @if($invoice->matcher) · {{ $invoice->matcher->name }}@endif</div>
        @endif
        @if($invoice->approved_at)
            <div>Approved: {{ $invoice->approved_at->format('d/m/Y H:i') }}
                @if($invoice->approver) · {{ $invoice->approver->name }}@endif</div>
        @endif
        @if($invoice->posted_at)
            <div>Posted: {{ $invoice->posted_at->format('d/m/Y H:i') }}
                @if($invoice->poster) · {{ $invoice->poster->name }}@endif</div>
        @endif
    </div>
</div>

{{-- Reject modal --}}
@can('reject', $invoice)
    <div class="modal fade" id="rejectInvoiceModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('procurement.supplier-invoices.reject', $invoice) }}" class="modal-content">
                @csrf @method('PATCH')
                <div class="modal-header"><h5 class="modal-title">Reject Supplier Invoice</h5></div>
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

{{-- Approve override modal --}}
@can('approve', $invoice)
    @if($invoice->match_status === 'mismatch')
        @can('overrideMismatch', $invoice)
            <div class="modal fade" id="approveMismatchModal" tabindex="-1">
                <div class="modal-dialog">
                    <form method="POST" action="{{ route('procurement.supplier-invoices.approve', $invoice) }}"
                          class="modal-content">
                        @csrf @method('PATCH')
                        <input type="hidden" name="override_mismatch" value="1">
                        <div class="modal-header">
                            <h5 class="modal-title text-warning">Override Three-Way Match Failure</h5>
                        </div>
                        <div class="modal-body">
                            <p>
                                One or more invoice lines failed the three-way match against the PO and GRN.
                                Approving with override will post this invoice to the general ledger and settle
                                the discrepancy outside the standard flow.
                            </p>
                            <p class="text-muted small mb-0">
                                This action is logged and attributed to your user account. It should only be
                                used when the mismatch has been investigated and explicitly approved.
                            </p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-warning">Approve with Override</button>
                        </div>
                    </form>
                </div>
            </div>
        @endcan
    @endif
@endcan
</x-default-layout>
