<x-default-layout>
@section('title', $invoice->number)

@section('sub-title')
 <div class="text-muted small">
            {{ $invoice->invoice_date->format('Y-m-d') }}
            · Due {{ $invoice->due_date->format('Y-m-d') }}
            @if($invoice->daysOverdue() > 0)
                · <span class="text-danger fw-semibold">{{ $invoice->daysOverdue() }} days overdue</span>
            @endif
            · {{ $invoice->currency_code }}

     <span class="badge badge-{{ $invoice->status->badgeClass() }} ms-2">{{ $invoice->status->label() }}</span>
            @if($invoice->match_status !== 'pending')
                <span class="badge badge-{{ $invoice->match_status === 'matched' ? 'success' : 'danger' }} ms-1">
                    Match: {{ ucfirst($invoice->match_status) }}
                </span>
            @endif
            </div>
@endsection

@section('toolbar-button')
@if(in_array($invoice->status->value, ['posted','partially_paid','paid'], true))
    @can('create', \Modules\Sales\Models\SalesReturn::class)<a href="{{ route('sales.returns.create',['invoice_id'=>$invoice->id]) }}" class="btn btn-sm btn-light-primary">Return Request</a>@endcan
@endif
<a href="{{ route('sales.sales-invoices.print', $invoice) }}" target="_blank" class="btn btn-outline-secondary"><i class="bi bi-printer"></i> Print / PDF</a>
        <a href="{{ route('sales.sales-invoices.csv', $invoice) }}" class="btn btn-outline-success"><i class="bi bi-filetype-csv"></i> Export CSV</a>
        @if($invoice->status->canMatch())
        @can('match', $invoice)
            <form method="POST" action="{{ route('sales.sales-invoices.match', $invoice) }}">
                @csrf @method('PATCH')
                <button class="btn btn-outline-primary">
                    <i class="bi bi-list-check"></i> Run Three-Way Match
                </button>
            </form>
        @endcan
        @endif

        @if($invoice->status->canApprove())
        @can('approve', $invoice)
            @if($invoice->match_status === 'mismatch')
                @can('overrideMismatch', $invoice)
                    <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#overrideModal">
                        Approve (Override Mismatch)
                    </button>
                @endcan
            @else
                <form method="POST" action="{{ route('sales.sales-invoices.approve', $invoice) }}">
                    @csrf @method('PATCH')
                    <button class="btn btn-success">Approve</button>
                </form>
            @endif
            <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">Reject</button>
        @endcan
        @endif

        @if($invoice->status->canPost())
        @can('post', $invoice)
            <form method="POST" action="{{ route('sales.sales-invoices.post', $invoice) }}">
                @csrf @method('PATCH')
                <button class="btn btn-primary"
                        onclick="return confirm('Post this invoice to the general ledger? This action is irreversible.')">
                    Post to GL
                </button>
            </form>
        @endcan
        @endif

        @if($invoice->status->canReverse())
        @can('reverse', $invoice)
            <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#reverseModal">Reverse</button>
        @endcan
        @endif

        @if($invoice->status->canCancel())
        @can('cancel', $invoice)
            <form method="POST" action="{{ route('sales.sales-invoices.cancel', $invoice) }}">
                @csrf @method('PATCH')
                <button class="btn btn-outline-secondary" onclick="return confirm('Cancel this invoice?')">Cancel</button>
            </form>
        @endcan
        @endif

        @if($invoice->status->isEditable())
        @can('update', $invoice)
            <a href="{{ route('sales.sales-invoices.edit', $invoice) }}" class="btn btn-outline-primary">Edit</a>
        @endcan
        @endif

        @if($invoice->status === \Modules\Sales\Enums\SalesInvoiceStatus::Draft)
        @can('delete', $invoice)
            <form method="POST" action="{{ route('sales.sales-invoices.destroy', $invoice) }}"
                  class="d-inline"
                  onsubmit="return confirm('Delete this draft invoice?');">
                @csrf @method('DELETE')
                <button class="btn btn-outline-danger">Delete</button>
            </form>
        @endcan
        @endif
@endsection


@if($invoice->match_status === 'mismatch' && $invoice->match_notes)
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i> {{ $invoice->match_notes }}
    </div>
@endif

@if($invoice->reverses || $invoice->reversedBy)
    <div class="alert alert-info">
        @if($invoice->reverses)
            Reversal of <a href="{{ route('sales.sales-invoices.show', $invoice->reverses) }}">{{ $invoice->reverses->number }}</a>
        @endif
        @if($invoice->reversedBy)
            Reversed by <a href="{{ route('sales.sales-invoices.show', $invoice->reversedBy) }}">{{ $invoice->reversedBy->number }}</a>
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
                        <a href="{{ route('customers.show', $invoice->customer_id) }}">{{ $invoice->customer?->name }}</a>
                    </dd>
                    @if($invoice->salesOrder)
                        <dt class="col-sm-4">Sales Order</dt>
                        <dd class="col-sm-8">
                            <a href="{{ route('sales.sales-orders.show', $invoice->sales_order_id) }}">
                                {{ $invoice->salesOrder->number }}
                            </a>
                        </dd>
                    @endif
                    {{-- <dt class="col-sm-4">Warehouse</dt><dd class="col-sm-8">{{ $invoice->warehouse?->name ?? '—' }}</dd> --}}
                    <dt class="col-sm-4">Payment Term</dt><dd class="col-sm-8">{{ $invoice->paymentTerm?->name ?? '—' }}</dd>
                    @if(!empty($invoice->reference))
                    <dt class="col-sm-4">Reference</dt><dd class="col-sm-8">{{ $invoice->reference ?: '—' }}</dd>
                    @endif
                    <dt class="col-sm-4">Journal Entry</dt>
                    <dd class="col-sm-8">
                        @if($invoice->journalEntry)
                            <a href="{{ route('journals.show', $invoice->journalEntry) }}">{{ $invoice->journalEntry->number }}</a>
                        @else — @endif
                    </dd>
                </dl>
                <br>
                <h3 class="text-center text-gray-600 fs-2">  {{ ucwords(\NumberFormatter::create('en', \NumberFormatter::SPELLOUT)->format($invoice->paid_amount)) }}
    Rupees Only</h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header">
                <h3 class="card-title">Totals</h3>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between"><span>Subtotal</span><span>{{ number_format((float) $invoice->subtotal, 2) }}</span></div>
                @if(bccomp((string) $invoice->discount_total, '0', 2) > 0)
                    <div class="d-flex justify-content-between"><span>Discount</span><span>− {{ number_format((float) $invoice->discount_total, 2) }}</span></div>
                @endif
                <div class="d-flex justify-content-between"><span>GST</span><span>{{ number_format((float) $invoice->tax_total, 2) }}</span></div>
                @if(bccomp((string) $invoice->wht_tax_total, '0', 2) > 0)
                    <div class="d-flex justify-content-between text-danger"><span>WHT (withheld)</span><span>− {{ number_format((float) $invoice->wht_tax_total, 2) }}</span></div>
                @endif
                <hr class="my-1">
                <div class="d-flex justify-content-between fw-semibold fs-5"><span>Total</span><span>{{ number_format((float) $invoice->total, 2) }}</span></div>
                @if(bccomp((string) $invoice->paid_amount, '0', 2) > 0)
                    <div class="d-flex justify-content-between text-success mt-2"><span>Paid</span><span>{{ number_format((float) $invoice->paid_amount, 2) }}</span></div>
                @endif
                <div class="d-flex justify-content-between"><span>Posted Credits</span><span>{{ number_format((float) $invoice->creditedTotal(), 2) }}</span></div>
                <div class="d-flex justify-content-between"><span>Outstanding</span><span>{{ number_format((float) $invoice->outstanding(), 2) }}</span></div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
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
                <tr class="fw-bold fs-6 text-gray-800">
                    <th>#</th><th>Item</th><th>Unit</th>
                    <th class="text-end">Qty</th>
                    <th class="text-end">Unit Price</th>
                    <th class="text-end">Disc %</th>
                    <th class="text-end">GST %</th>
                    <th class="text-end">WHT %</th>
                    <th class="text-end">Line Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->lines as $i => $line)
                    <tr>
                        <td class="text-muted">{{ $i + 1 }}</td>
                        <td>
                            <code>{{ $line->item?->code }}</code> {{ $line->item?->name }}
                            @if($line->description)<div class="text-muted small">{{ $line->description }}</div>@endif
                        </td>
                        <td>{{ $line->unit?->code ?? '—' }}</td>
                        <td class="text-end">{{ number_format((float) $line->quantity, 4) }}</td>
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

@if($invoice->allocations->isNotEmpty())
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header">
            <h3 class="card-title">Receipts Applied</h3>
        </div>
        <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr class="fw-bold fs-6 text-gray-800">
                        <th>Receipt</th><th>Date</th><th class="text-end">Amount</th><th>Applied By</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->allocations as $alloc)
                        @php($receipt = $alloc->payment)
                        <tr>
                            <td>
                                @if($receipt)
                                    <a href="{{ route('sales.customer-receipts.show', $receipt) }}">
                                        <code>{{ $receipt->number }}</code>
                                    </a>
                                @else — @endif
                            </td>
                            <td>{{ $receipt?->receipt_date?->format('d/m/Y') ?? '—' }}</td>
                            <td class="text-end">{{ number_format((float) $alloc->amount, 2) }}</td>
                            <td class="text-muted small">{{ $alloc->allocator?->name ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </div>
    </div>
@endif

@can('returns.view')
@if($invoice->returns->isNotEmpty())
<section class="bg-body p-6 mb-5">

    <h2 class="fs-4">Sales Returns</h2>
    <div class="table-responsive"><table class="table table-row-dashed"><thead><tr><th>Return</th><th>Status</th><th>Credit Note</th><th>Credit Status</th></tr></thead><tbody>
    @foreach($invoice->returns as $salesReturn)
        <tr><td><a href="{{ route('sales.returns.show',$salesReturn) }}">{{ $salesReturn->number }}</a></td><td>{{ ucfirst($salesReturn->status) }}</td><td>{{ $salesReturn->creditNote?->number ?? '-' }}</td><td>{{ ucfirst($salesReturn->creditNote?->status ?? '-') }}</td></tr>
    @endforeach
    </tbody></table></div>
</section>
@endif
@endcan

{{-- Reject modal --}}
@can('reject', $invoice)
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('sales.sales-invoices.reject', $invoice) }}" class="modal-content">
                @csrf @method('PATCH')
                <div class="modal-header"><h5 class="modal-title">Reject Invoice</h5></div>
                <div class="modal-body">
                    <label class="form-label">Reason <span class="text-danger">*</span></label>
                    <textarea name="reason" rows="3" class="form-control" required maxlength="500"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject</button>
                </div>
            </form>
        </div>
    </div>
@endcan

{{-- Override modal --}}
@can('approve', $invoice)
    @if($invoice->match_status === 'mismatch')
        @can('overrideMismatch', $invoice)
            <div class="modal fade" id="overrideModal" tabindex="-1">
                <div class="modal-dialog">
                    <form method="POST" action="{{ route('sales.sales-invoices.approve', $invoice) }}" class="modal-content">
                        @csrf @method('PATCH')
                        <input type="hidden" name="override_mismatch" value="1">
                        <div class="modal-header"><h5 class="modal-title text-warning">Override Match Failure</h5></div>
                        <div class="modal-body">
                            <p>One or more invoice lines failed the three-way match against the Sales Order and Delivery. Approving with override will post this invoice to the general ledger and settle the discrepancy outside the standard flow.</p>
                            <p class="text-muted small mb-0">This action is logged against your user account.</p>
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

{{-- Reverse modal --}}
@can('reverse', $invoice)
    <div class="modal fade" id="reverseModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('sales.sales-invoices.reverse', $invoice) }}" class="modal-content">
                @csrf @method('PATCH')
                <div class="modal-header"><h5 class="modal-title text-warning">Reverse Invoice</h5></div>
                <div class="modal-body">
                    <p>This creates a mirror invoice that reverses the GL entry and releases the sales order line invoiced quantities.</p>
                    <label class="form-label">Reason <span class="text-danger">*</span></label>
                    <textarea name="reason" rows="3" class="form-control" required minlength="3" maxlength="500"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Create Reversal</button>
                </div>
            </form>
        </div>
    </div>
@endcan
</x-default-layout>
