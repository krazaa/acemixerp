@extends('layouts.app')

@section('title', 'New Standalone Vendor Invoice')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 m-0">New Vendor Invoice</h1>
            <div class="text-muted small">Record a vendor expense invoice without a purchase order. It posts to Accounts Payable after approval.</div>
        </div>
        <a href="{{ route('procurement.supplier-invoices.index') }}" class="btn btn-outline-secondary btn-sm">Back to list</a>
    </div>

    <form method="POST" action="{{ $invoice->exists ? route('procurement.supplier-invoices.update', $invoice) : route('procurement.supplier-invoices.store') }}">
        @csrf
        @if($invoice->exists) @method('PUT') @endif
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Header</div>
            <div class="card-body"><div class="row g-3">
                <div class="col-md-4"><label class="form-label">Vendor *</label><select name="vendor_id" class="form-select @error('vendor_id') is-invalid @enderror" required><option value="">Select vendor</option>@foreach($vendors as $vendor)<option value="{{ $vendor->id }}" @selected((int) old('vendor_id', $invoice->vendor_id) === $vendor->id)>{{ $vendor->code }} — {{ $vendor->name }}</option>@endforeach</select>@error('vendor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-4"><label class="form-label">Vendor Invoice # *</label><input name="vendor_invoice_number" value="{{ old('vendor_invoice_number', $invoice->vendor_invoice_number) }}" maxlength="64" class="form-control @error('vendor_invoice_number') is-invalid @enderror" required>@error('vendor_invoice_number')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-2"><label class="form-label">Invoice Date *</label><input type="date" name="invoice_date" value="{{ old('invoice_date', $invoice->invoice_date?->toDateString()) }}" class="form-control" required></div>
                <div class="col-md-2"><label class="form-label">Due Date *</label><input type="date" name="due_date" value="{{ old('due_date', $invoice->due_date?->toDateString()) }}" class="form-control" required></div>
                <div class="col-md-2"><label class="form-label">Currency *</label><input name="currency_code" value="{{ old('currency_code', $invoice->currency_code) }}" maxlength="3" class="form-control" required></div>
                <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" rows="2" class="form-control">{{ old('notes') }}</textarea></div>
            </div></div>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Expense Line</div>
            <div class="card-body"><div class="row g-3">
                @php($line = $invoice->lines->first())
                @include('inventory::brands._select', ['index' => 0, 'selectedBrandId' => old('lines.0.brand_id', $invoice->lines->first()?->brand_id)])
                <div class="col-md-4"><label class="form-label">Expense Account *</label><select name="lines[0][expense_account_id]" class="form-select @error('lines.0.expense_account_id') is-invalid @enderror" required><option value="">Select expense account</option>@foreach($expenseAccounts as $account)<option value="{{ $account->id }}" @selected((int) old('lines.0.expense_account_id', $line?->expense_account_id) === $account->id)>{{ $account->code }} — {{ $account->name }}</option>@endforeach</select>@error('lines.0.expense_account_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-2"><label class="form-label">Quantity *</label><input type="number" name="lines[0][quantity]" value="{{ old('lines.0.quantity', $line?->quantity ?? 1) }}" step="0.0001" min="0.0001" class="form-control" required></div>
                <div class="col-md-2"><label class="form-label">Unit Price *</label><input type="number" name="lines[0][unit_price]" value="{{ old('lines.0.unit_price', $line?->unit_price) }}" step="0.0001" min="0" class="form-control" required></div>
                <div class="col-md-2"><label class="form-label">Tax %</label><input type="number" name="lines[0][tax_rate]" value="{{ old('lines.0.tax_rate', $line?->tax_rate ?? 0) }}" step="0.01" min="0" max="100" class="form-control"></div>
                <div class="col-md-2"><label class="form-label">WHT %</label><input type="number" name="lines[0][wht_rate]" value="{{ old('lines.0.wht_rate', $line?->wht_rate ?? 0) }}" step="0.01" min="0" max="100" class="form-control"></div>
                <div class="col-md-12"><label class="form-label">Description</label><input name="lines[0][description]" value="{{ old('lines.0.description', $line?->description) }}" maxlength="500" class="form-control"></div>
            </div></div>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-3"><a href="{{ route('procurement.supplier-invoices.index') }}" class="btn btn-outline-secondary">Cancel</a><button class="btn btn-primary">Save Draft</button></div>
    </form>
@endsection
