@php($isEdit = $receipt->exists)

@php($existingAllocations = old('allocations', $isEdit
    ? $receipt->allocations->map(fn ($a) => [
        'sales_invoice_id' => $a->allocatable_id,
        'amount'           => (string) $a->amount,
        'invoice'          => $a->allocatable,
    ])->all()
    : []
))

{{-- Header --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header">
        <h3 class="card-title">Receipt Header</h3>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="customer_id">Customer <span class="text-danger">*</span></label>
                <select id="customer_id" name="customer_id"
                        class="form-select @error('customer_id') is-invalid @enderror"
                        required
                        onchange="receiptLoadOutstanding(this.value)">
                    <option value="">— Select customer —</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}"
                            @selected((int) old('customer_id', $receipt->customer_id) === $c->id)>
                            {{ $c->code }} — {{ $c->name }}
                        </option>
                    @endforeach
                </select>
                @error('customer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-2">
                <label class="form-label" for="receipt_date">Receipt Date <span class="text-danger">*</span></label>
                <input id="receipt_date" name="receipt_date" type="date" class="form-control"
                       value="{{ old('receipt_date', $receipt->receipt_date?->toDateString() ?? now()->toDateString()) }}"
                       required>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="currency_code">Currency <span class="text-danger">*</span></label>
                <input id="currency_code" name="currency_code" maxlength="3"
                       class="form-control @error('currency_code') is-invalid @enderror"
                       value="{{ old('currency_code', $receipt->currency_code) }}" required>
                @error('currency_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-2">
                <label class="form-label" for="payment_method">Method <span class="text-danger">*</span></label>
                <select id="payment_method" name="payment_method" class="form-select" required>
                    @foreach(\App\Enums\PaymentMethod::cases() as $m)
                        <option value="{{ $m->value }}"
                            @selected(old('payment_method', $receipt->payment_method?->value) === $m->value)>
                            {{ $m->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="bank_account_id">Bank Account</label>
                <select id="bank_account_id" name="bank_account_id" class="form-select">
                    <option value="">— Cash (Cash on Hand) —</option>
                    @foreach($bankAccounts as $ba)
                        <option value="{{ $ba->id }}"
                            @selected((int) old('bank_account_id', $receipt->bank_account_id) === $ba->id)>
                            {{ $ba->code }} — {{ $ba->name }}
                        </option>
                    @endforeach
                </select>
                <div class="form-text">Leave blank to receive into cash.</div>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="reference">Reference</label>
                <input id="reference" name="reference" class="form-control"
                       value="{{ old('reference', $receipt->reference) }}" maxlength="128"
                       placeholder="Cheque #, transaction ID, etc.">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="amount">Amount <span class="text-danger">*</span></label>
                <input id="amount" name="amount" type="number" step="0.0001" min="0.0001"
                       class="form-control @error('amount') is-invalid @enderror"
                       value="{{ old('amount', $receipt->amount) }}" required>
                @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">Auto-derived from allocations if left at zero.</div>
            </div>
            <div class="col-12">
                <label class="form-label" for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="2" class="form-control" maxlength="2000">{{ old('notes', $receipt->notes) }}</textarea>
            </div>
        </div>
    </div>
</div>

{{-- Allocations --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span>Apply to Invoices</span>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="receiptAddAllocation()">
                <i class="bi bi-plus-lg"></i> Add Invoice
            </button>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="receiptAutoAllocate()">
                <i class="bi bi-magic"></i> Auto Allocate by Due Date
            </button>
        </div>
    </div>
    <div class="card-body" id="receipt-allocations-container">
        @forelse($existingAllocations as $i => $a)
            @include('sales::customer-receipts._allocation-row', ['index' => $i, 'allocation' => $a])
        @empty
            <div class="text-muted" id="receipt-no-allocations">
                No allocations yet. Pick a customer to load their open invoices.
            </div>
        @endforelse
    </div>
    <div class="card-footer bg-white d-flex justify-content-end gap-4">
        <div>Allocated: <strong id="allocated-total">0.00</strong></div>
        <div>Unallocated: <strong id="unallocated-total">0.00</strong></div>
    </div>
</div>
