<x-default-layout>
@section('title', $payment->number)

@section('sub-title')
        <div class="text-muted small">
            {{ $payment->payment_date->format('d/m/Y') }}
            · {{ \Carbon\Carbon::createFromFormat('Y-m', $payment->billing_month ?? $payment->payment_date->format('Y-m'))->format('F Y') }}
            · {{ $payment->payment_method->label() }}
            · {{ $payment->currency_code }}
             <span class="badge badge-{{ $payment->status->badgeClass() }} ms-2">
                {{ $payment->status->label() }}
            </span>
        </div>
@endsection

@section('toolbar-button')
 @if($payment->status->value === 'posted')
            <a href="{{ route('procurement.vendor-payments.voucher', $payment) }}" target="_blank" class="btn btn-sm btn-outline-secondary">Print Voucher</a>
        @endif
        @can('submit', $payment)
            <form method="POST" action="{{ route('procurement.vendor-payments.submit', $payment) }}">
                @csrf @method('PATCH')
                <button class="btn btn-sm btn-primary">Submit</button>
            </form>
        @endcan
        @can('approve', $payment)
            <form method="POST" action="{{ route('procurement.vendor-payments.approve', $payment) }}">
                @csrf @method('PATCH')
                <button class="btn btn-sm btn-success">Approve</button>
            </form>
            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectPaymentModal">Reject</button>
        @endcan
        @can('post', $payment)
            <form method="POST" action="{{ route('procurement.vendor-payments.post', $payment) }}">
                @csrf @method('PATCH')
                <button class="btn btn-sm btn-primary"
                        onclick="return confirm('Post this payment to the general ledger? This action is irreversible.')">
                    Post to GL
                </button>
            </form>
        @endcan
        @can('reverse', $payment)
            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#reversePaymentModal">Reverse</button>
        @endcan
    @if($payment->status->label() !== 'Posted')
        @can('cancel', $payment)
            <form method="POST" action="{{ route('procurement.vendor-payments.cancel', $payment) }}">
                @csrf @method('PATCH')
                <button class="btn btn-outline-secondary"
                        onclick="return confirm('Cancel this payment? Allocations will be released.')">Cancel</button>
            </form>
        @endcan
        @can('update', $payment)
            <a href="{{ route('procurement.vendor-payments.edit', $payment) }}" class="btn btn-sm btn-outline-primary">Edit</a>
        @endcan
        @can('delete', $payment)
            <form method="POST" action="{{ route('procurement.vendor-payments.destroy', $payment) }}"
                  class="d-inline"
                  onsubmit="return confirm('Delete this draft payment?');">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger">Delete</button>
            </form>
        @endcan
    @endif
    <a href="{{ route('procurement.vendor-payments.index') }}" class="btn btn-sm btn-light">
        <i class="fas fa-arrow-left fa-sm"></i> Back </a>

@endsection

@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif



@if($payment->reverses || $payment->reversedBy)
    <div class="alert alert-info">
        @if($payment->reverses)
            Reversal of
            <a href="{{ route('procurement.vendor-payments.show', $payment->reverses) }}">
                {{ $payment->reverses->number }}
            </a>
        @endif
        @if($payment->reversedBy)
            Reversed by
            <a href="{{ route('procurement.vendor-payments.show', $payment->reversedBy) }}">
                {{ $payment->reversedBy->number }}
            </a>
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
                        <a href="{{ route('vendors.show', $payment->vendor_id) }}">
                            {{ $payment->vendor?->name }}
                        </a>
                    </dd>
                    <dt class="col-sm-4">Bank / Source</dt>
                    <dd class="col-sm-8">{{ $payment->bankAccount?->name ?? 'Cash on Hand' }}</dd>
                    <dt class="col-sm-4">Reference</dt>
                    <dd class="col-sm-8">{{ $payment->reference ?: '—' }}</dd>
                    <dt class="col-sm-4">Billing Month</dt>
                    <dd class="col-sm-8">{{ \Carbon\Carbon::createFromFormat('Y-m', $payment->billing_month ?? $payment->payment_date->format('Y-m'))->format('F Y') }}</dd>
                    <dt class="col-sm-4">Journal Entry</dt>
                    <dd class="col-sm-8">
                        @if($payment->journalEntry)
                            <a href="{{ route('journals.show', $payment->journalEntry) }}">
                                {{ $payment->journalEntry->number }}
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
                <h3 class="card-title">Amount</h3>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between"><span>Payment Amount</span><span>{{ number_format((float) $payment->amount, 2) }}</span></div>
                <div class="d-flex justify-content-between"><span>Allocated</span><span>{{ number_format((float) $payment->allocated_amount, 2) }}</span></div>
                <hr class="my-1">
                <div class="d-flex justify-content-between fw-semibold fs-5">
                    <span>Unallocated</span>
                    <span>{{ number_format((float) $payment->unallocatedAmount(), 2) }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header">
        <h3 class="card-title">Allocations</h3>
            <div class="card-toolbar">
        <span class="text-muted small">{{ $payment->allocations->count() }} invoice(s)</span>
        </div>
    </div>
    <div class="card-body">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Invoice Date</th>
                    <th>Due Date</th>
                    <th class="text-end">Invoice Total</th>
                    <th class="text-end">Allocated</th>
                    <th>Allocated By</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payment->allocations as $allocation)
                    @php($invoice = $allocation->allocatable)
                    <tr>
                        <td>
                            @if($invoice)
                                <a href="{{ route('procurement.supplier-invoices.show', $invoice) }}">
                                    <code>{{ $invoice->number }}</code>
                                </a>
                                <div class="text-muted small">Vendor ref: {{ $invoice->vendor_invoice_number }}</div>
                            @else — @endif
                        </td>
                        <td>{{ $invoice?->invoice_date?->format('d/m/Y') ?? '—' }}</td>
                        <td>{{ $invoice?->due_date?->format('d/m/Y') ?? '—' }}</td>
                        <td class="text-end">{{ $invoice ? number_format((float) $invoice->total, 2) : '—' }}</td>
                        <td class="text-end fw-semibold">{{ number_format((float) $allocation->amount, 2) }}</td>
                        <td class="small text-muted">{{ $allocation->allocator?->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No allocations — this payment is a full advance.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>

@if($payment->notes)
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header">
            <h3 class="card-title">Notes</h3>
        </div>
        <div class="card-body" style="white-space: pre-wrap;">{{ $payment->notes }}</div>
    </div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-header">
        <h3 class="card-title">Audit</h3>
    </div>
    <div class="card-body small text-muted">
        <div>Created: {{ $payment->created_at?->format('Y-m-d H:i') }}
            @if($payment->creator) · {{ $payment->creator->name }}@endif</div>
        @if($payment->submitted_at)
            <div>Submitted: {{ $payment->submitted_at->format('Y-m-d H:i') }}
                @if($payment->submitter) · {{ $payment->submitter->name }}@endif</div>
        @endif
        @if($payment->approved_at)
            <div>Approved: {{ $payment->approved_at->format('Y-m-d H:i') }}
                @if($payment->approver) · {{ $payment->approver->name }}@endif</div>
        @endif
        @if($payment->posted_at)
            <div>Posted: {{ $payment->posted_at->format('Y-m-d H:i') }}
                @if($payment->poster) · {{ $payment->poster->name }}@endif</div>
        @endif
    </div>
</div>

{{-- Reject modal --}}
@can('reject', $payment)
    <div class="modal fade" id="rejectPaymentModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('procurement.vendor-payments.reject', $payment) }}" class="modal-content">
                @csrf @method('PATCH')
                <div class="modal-header"><h5 class="modal-title">Reject Vendor Payment</h5></div>
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

{{-- Reverse modal --}}
@can('reverse', $payment)
    <div class="modal fade" id="reversePaymentModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('procurement.vendor-payments.reverse', $payment) }}" class="modal-content">
                @csrf @method('PATCH')
                <div class="modal-header"><h5 class="modal-title text-warning">Reverse Payment</h5></div>
                <div class="modal-body">
                    <p>
                        Reversing creates a mirror payment and reverses the GL journal entry.
                        All invoice allocations against this payment are released; the affected
                        invoices return to <strong>Posted</strong> status.
                    </p>
                    <p class="text-muted small">
                        The reversal document starts in <strong>Draft</strong>. Submit, approve, and
                        post it to complete the reversal end-to-end.
                    </p>
                    <label class="form-label">Reason <span class="text-danger">*</span></label>
                    <textarea name="reason" class="form-control" rows="3" required minlength="3" maxlength="500"></textarea>
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
