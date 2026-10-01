<x-default-layout>
@section('title', $receipt->number)

@section('sub-title')

        <div class="text-muted small">
            {{ $receipt->receipt_date->format('Y-m-d') }}
            · {{ $receipt->payment_method->label() }}
            · {{ $receipt->currency_code }}
            <span class="badge badge-{{ $receipt->status->badgeClass() }} ms-2">{{ $receipt->status->label() }}</span>
        </div>
@endsection

@section('toolbar-button')

        @can('submit', $receipt)
            <form method="POST" action="{{ route('sales.customer-receipts.submit', $receipt) }}">
                @csrf @method('PATCH')
                <button class="btn btn-sm btn-primary">Submit</button>
            </form>
        @endcan
        @can('approve', $receipt)
            <form method="POST" action="{{ route('sales.customer-receipts.approve', $receipt) }}">
                @csrf @method('PATCH')
                <button class="btn btn-sm btn-success">Approve</button>
            </form>
            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectReceiptModal">Reject</button>
        @endcan
        @can('post', $receipt)
            <form method="POST" action="{{ route('sales.customer-receipts.post', $receipt) }}">
                @csrf @method('PATCH')
                <button class="btn btn-sm btn-primary"
                        onclick="return confirm('Post this receipt to the general ledger?')">
                    Post to GL
                </button>
            </form>
        @endcan
        @can('reverse', $receipt)
            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#reverseReceiptModal">Reverse</button>
        @endcan
        @can('cancel', $receipt)
            <form method="POST" action="{{ route('sales.customer-receipts.cancel', $receipt) }}">
                @csrf @method('PATCH')
                <button class="btn btn-sm btn-outline-secondary"
                        onclick="return confirm('Cancel this receipt? Allocations will be released.')">
                    Cancel
                </button>
            </form>
        @endcan
        @can('update', $receipt)
            <a href="{{ route('sales.customer-receipts.edit', $receipt) }}" class="btn btn-sm btn-outline-primary">Edit</a>
        @endcan
        @can('delete', $receipt)
            <form method="POST" action="{{ route('sales.customer-receipts.destroy', $receipt) }}"
                  class="d-inline"
                  onsubmit="return confirm('Delete this draft receipt?');">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger">Delete</button>
            </form>
        @endcan

@endsection


@if($receipt->reverses || $receipt->reversedBy)
    <div class="alert alert-info">
        @if($receipt->reverses)
            Reversal of
            <a href="{{ route('sales.customer-receipts.show', $receipt->reverses) }}">
                {{ $receipt->reverses->number }}
            </a>
        @endif
        @if($receipt->reversedBy)
            Reversed by
            <a href="{{ route('sales.customer-receipts.show', $receipt->reversedBy) }}">
                {{ $receipt->reversedBy->number }}
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
                    <dt class="col-sm-4">Customer</dt>
                    <dd class="col-sm-8">
                        <a href="{{ route('customers.show', $receipt->customer_id) }}">{{ $receipt->customer?->name }}</a>
                    </dd>
                    <dt class="col-sm-4">Bank / Source</dt>
                    <dd class="col-sm-8">{{ $receipt->bankAccount?->name ?? 'Cash on Hand' }}</dd>
                    <dt class="col-sm-4">Reference</dt>
                    <dd class="col-sm-8">{{ $receipt->reference ?: '—' }}</dd>
                    <dt class="col-sm-4">Journal Entry</dt>
                    <dd class="col-sm-8">
                        @if($receipt->journalEntry)
                            <a href="{{ route('journals.show', $receipt->journalEntry) }}">{{ $receipt->journalEntry->number }}</a>
                        @else — @endif
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
                <div class="d-flex justify-content-between"><span>Receipt Amount</span><span>{{ number_format((float) $receipt->amount, 4) }}</span></div>
                <div class="d-flex justify-content-between"><span>Allocated</span><span>{{ number_format((float) $receipt->allocated_amount, 4) }}</span></div>
                <hr class="my-1">
                <div class="d-flex justify-content-between fw-semibold fs-5">
                    <span>Unallocated</span>
                    <span>{{ number_format((float) $receipt->unallocatedAmount(), 4) }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header">
            <h3 class="card-title">Applied to Invoices</h3>
               <div class="card-toolbar">
        <span class="text-muted small">{{ $receipt->allocations->count() }} invoice(s)</span>
        </div>
    </div>
    <div class="card-body">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr class="fw-bold fs-6 text-gray-800">
                    <th>Invoice</th><th>Invoice Date</th><th>Due Date</th>
                    <th class="text-end">Invoice Total</th>
                    <th class="text-end">Applied</th>
                    <th>Applied By</th>
                </tr>
            </thead>
            <tbody>
                @forelse($receipt->allocations as $alloc)
                    @php($inv = $alloc->allocatable)
                    <tr>
                        <td>
                            @if($inv)
                                <a href="{{ route('sales.sales-invoices.show', $inv) }}">
                                    <code>{{ $inv->number }}</code>
                                </a>
                            @else — @endif
                        </td>
                        <td>{{ $inv?->invoice_date?->format('Y-m-d') ?? '—' }}</td>
                        <td>{{ $inv?->due_date?->format('Y-m-d') ?? '—' }}</td>
                        <td class="text-end">{{ $inv ? number_format((float) $inv->total, 4) : '—' }}</td>
                        <td class="text-end fw-semibold">{{ number_format((float) $alloc->amount, 4) }}</td>
                        <td class="text-muted small">{{ $alloc->allocator?->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No allocations — this is an unallocated receipt.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    </div>
</div>

@if($receipt->notes)
    <div class="card border-0 shadow-sm mb-3">
          <div class="card-header">
              <h3 class="card-title">Notes</h3>
        </div>
        <div class="card-body" style="white-space: pre-wrap;">
            {{ $receipt->notes }}
        </div>

    </div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-header">
        <h3 class="card-title">Audit</h3></div>
    <div class="card-body small text-muted">
        <div>Created: {{ $receipt->created_at?->format('Y-m-d H:i') }}
            @if($receipt->creator) · {{ $receipt->creator->name }}@endif</div>
        @if($receipt->submitted_at)
            <div>Submitted: {{ $receipt->submitted_at->format('Y-m-d H:i') }}
                @if($receipt->submitter) · {{ $receipt->submitter->name }}@endif</div>
        @endif
        @if($receipt->approved_at)
            <div>Approved: {{ $receipt->approved_at->format('Y-m-d H:i') }}
                @if($receipt->approver) · {{ $receipt->approver->name }}@endif</div>
        @endif
        @if($receipt->posted_at)
            <div>Posted: {{ $receipt->posted_at->format('Y-m-d H:i') }}
                @if($receipt->poster) · {{ $receipt->poster->name }}@endif</div>
        @endif
    </div>
</div>

{{-- Modals --}}
@can('reject', $receipt)
    <div class="modal fade" id="rejectReceiptModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('sales.customer-receipts.reject', $receipt) }}" class="modal-content">
                @csrf @method('PATCH')
                <div class="modal-header"><h5 class="modal-title">Reject Receipt</h5></div>
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

@can('reverse', $receipt)
    <div class="modal fade" id="reverseReceiptModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('sales.customer-receipts.reverse', $receipt) }}" class="modal-content">
                @csrf @method('PATCH')
                <div class="modal-header"><h5 class="modal-title text-warning">Reverse Receipt</h5></div>
                <div class="modal-body">
                    <p>Reversing creates a mirror receipt and reverses the GL journal entry. Invoice allocations are released.</p>
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
