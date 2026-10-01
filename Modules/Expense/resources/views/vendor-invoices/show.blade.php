<x-default-layout>

@section('title', $invoice->number)
@section('sub-title')
<div class="text-muted">{{ $invoice->vendor?->name }} · {{ \Carbon\Carbon::createFromFormat('Y-m', $invoice->billing_month)->format('F Y') }} · {{ str($invoice->status)->replace('_', ' ')->title() }}</div>
@endsection

@section('toolbar-button')
    <a href="{{ route('expense.vendor-invoices.pdf', $invoice) }}" class="btn btn-sm btn-light-primary"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> Download PDF</a>
     @if ($invoice->paymentAllocations->first()?->payment)
            <a href="{{ route('procurement.vendor-payments.voucher', $invoice->paymentAllocations->first()->payment) }}" target="_blank" class="btn btn-sm btn-outline-secondary">Print Payment Voucher</a>
        @endif
        @can('submit', $invoice)
        @if ($invoice->status === 'draft')
            <a href="{{ route('expense.vendor-invoices.edit', $invoice) }}" class="btn btn-sm btn-outline-primary">Edit</a>
            <form method="POST" action="{{ route('expense.vendor-invoices.submit', $invoice) }}">
                @csrf
                @method('PATCH')
                <button class="btn btn-sm btn-primary">Submit</button>
            </form>
        @endif
        @endcan
        @can('approve', $invoice)
        @if ($invoice->status === 'submitted')
            <form method="POST" action="{{ route('expense.vendor-invoices.approve', $invoice) }}">
                @csrf
                @method('PATCH')
                <button class="btn btn-sm btn-success">Operations Approve</button>
            </form>
        @endif
        @endcan
        @can('ownerApprove', $invoice)
        @if ($invoice->status === 'approved' && !$invoice->owner_approved_at)
            <form method="POST" action="{{ route('expense.vendor-invoices.owner-approve', $invoice) }}">
                @csrf @method('PATCH')
                <button class="btn btn-sm btn-success">Approve</button>
            </form>
        @endif
        @endcan
        @can('post', $invoice)
        @if ($invoice->status === 'approved' && $invoice->owner_approved_at)
            <form method="POST" action="{{ route('expense.vendor-invoices.post', $invoice) }}" class="d-flex gap-2">
                @csrf
                @method('PATCH')
                <select name="debit_account_id" class="form-select form-select-sm" required>
                    <option value="">Cargo/Freight account</option>
                    @foreach ($expenseAccounts as $account)
                        <option value="{{ $account->id }}" @selected($invoice->lines->first()?->debit_account_id === $account->id)>{{ $account->code }} — {{ $account->name }}</option>
                    @endforeach
                </select>
                <button class="btn btn-sm btn-primary text-nowrap">Post to GL</button>
            </form>
        @endif
        @endcan
        <a href="{{ route('expense.vendor-invoices.index') }}" class="btn btn-sm btn-outline-secondary">Back</a>
@endsection

@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
@if($invoice->status === 'rejected')
    <div class="alert alert-danger">{{ $invoice->rejection_reason }}</div>
@elseif($invoice->status === 'approved' && !$invoice->owner_approved_at)
    <div class="alert alert-info">Awaiting owner review</div>
@endif
@can('reject', $invoice)
@if($invoice->status === 'approved' && !$invoice->owner_approved_at)
<form method="POST" action="{{ route('expense.vendor-invoices.reject', $invoice) }}" class="d-flex flex-wrap gap-3 mb-5">
    @csrf @method('PATCH')
    <label for="owner-rejection" class="visually-hidden">Rejection reason</label>
    <input id="owner-rejection" name="rejection_reason" class="form-control flex-grow-1" placeholder="Rejection reason" required minlength="3" maxlength="2000">
    <button class="btn btn-sm btn-light-danger">Reject Invoice</button>
</form>
@endif
@endcan


<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card"><div class="card-body"><small>Billing Amount</small><div class="fs-4">{{ number_format((float) $invoice->subtotal, 2) }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><small>GST (Input Tax)</small><div class="fs-4">{{ number_format((float) $invoice->tax_total, 2) }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><small>Withholding Tax (WHT)</small><div class="fs-4">{{ number_format((float) $invoice->whttax_total, 2) }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><small>Payable</small><div class="fs-4">{{ number_format((float) $invoice->total, 2) }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-success">Paid Amount</small><div class="fs-4 text-success">{{ number_format((float) $invoice->paid_amount, 2) }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-danger">Outstanding</small><div class="fs-4 text-danger">{{ number_format((float) ($invoice->total - $invoice->paid_amount), 2) }}</div></div></div></div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Invoice Details</h3>
    </div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Vendor Invoice #</dt>
            <dd class="col-sm-3">{{ $invoice->vendor_invoice_number }}</dd>
            <dt class="col-sm-3">Invoice GL Entry</dt>
            <dd class="col-sm-3">
                @if ($invoice->journalEntry)
                 <a href="{{ route('journals.show', $invoice->journalEntry) }}">{{ $invoice->journalEntry->number }}</a>
                 @else — @endif
                </dd>

            <dt class="col-sm-3">Payment GL Entries</dt>
            <dd class="col-sm-3">
                @forelse ($invoice->paymentAllocations as $allocation)
                    @if ($allocation->payment?->journalEntry)
                        <a href="{{ route('journals.show', $allocation->payment->journalEntry) }}">{{ $allocation->payment->journalEntry->number }}</a>
                        <span class="text-muted">({{ number_format((float) $allocation->amount, 2) }})</span>
                        @unless ($loop->last)<br>@endunless
                    @endif
                @empty
                    —
                @endforelse
            </dd>
            <dt class="col-sm-3">Payment Vouchers</dt>
            <dd class="col-sm-3">
                @forelse ($invoice->paymentAllocations as $allocation)
                    @if ($allocation->payment)
                        <a href="{{ route('procurement.vendor-payments.voucher', $allocation->payment) }}" target="_blank">{{ $allocation->payment->number }}</a>
                        <span class="text-muted">({{ number_format((float) $allocation->amount, 2) }})</span>
                        @unless ($loop->last)<br>@endunless
                    @endif
                @empty
                    —
                @endforelse
            </dd>
            <dt class="col-sm-3">Details</dt>
            <dd class="col-sm-9 mt-5">{!!  $invoice->notes ?: '—' !!}</dd>
        </dl>
    </div>
</div>

@if (in_array($invoice->status, ['posted', 'partially_paid'], true))
    <div class="card mt-3">
        <div class="card-header">
            <h3 class="card-title"> Record Vendor Payment</h3>
        </div>
        <div class="card-body">
            <div class="alert {{ $invoice->vendor?->bank_account_number || $invoice->vendor?->bank_iban ? 'alert-info' : 'alert-warning' }}">
                <div class="fw-semibold mb-1">Vendor Bank Details</div>
                @if ($invoice->vendor?->bank_account_number || $invoice->vendor?->bank_iban)
                    <div>Account Title: {{ $invoice->vendor?->bank_name ?: 'Bank name not provided' }}</div>
                    <div>Branch:  {{ $invoice->vendor?->bank_branch ? ' · '.$invoice->vendor->bank_branch : '' }}</div>
                    <div>Account: {{ $invoice->vendor?->bank_account_number ?: '—' }}{{ $invoice->vendor?->bank_iban ? ' · IBAN: '.$invoice->vendor->bank_iban : '' }}</div>
                @else
                    <div>
                        No bank details are on file for this vendor.
                        @if ($invoice->vendor)
                            @can('update', $invoice->vendor)
                                <a href="{{ route('vendors.edit', $invoice->vendor) }}">Add vendor bank details</a> before making a bank transfer.
                            @endcan
                        @endif
                    </div>
                @endif
            </div>
            <div class="mb-3 text-muted">Outstanding: <strong>{{ number_format((float) ($invoice->total - $invoice->paid_amount), 2) }}</strong></div>
            <form method="POST" action="{{ route('expense.vendor-invoices.pay', $invoice) }}" class="row g-3">
                @csrf
                @method('PATCH')
                <div class="col-md-3"><label class="form-label">Amount *</label><input type="number" name="amount" value="{{ number_format((float) ($invoice->total - $invoice->paid_amount), 4, '.', '') }}" step="0.0001" min="0.0001" class="form-control" required></div>
                <div class="col-md-3"><label class="form-label">Method *</label><select name="payment_method" class="form-select" required><option value="bank_transfer">Bank Transfer</option><option value="cash">Cash</option><option value="cheque">Cheque</option></select></div>
                <div class="col-md-3"><label class="form-label">Bank Account</label><select name="bank_account_id" class="form-select"><option value="">Required except Cash</option>@foreach ($bankAccounts as $bankAccount)<option value="{{ $bankAccount->id }}">{{ $bankAccount->name }} · {{ $bankAccount->account_number }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label">Reference</label><input name="reference" class="form-control"></div>
                <div class="col-12 text-end"><button class="btn btn-success">Record Payment</button></div>
            </form>
        </div>
    </div>
@endif
</x-default-layout>
