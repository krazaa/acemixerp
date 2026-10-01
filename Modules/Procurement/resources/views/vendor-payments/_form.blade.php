@php($isEdit = $payment->exists)

@php($existingAllocations = old('allocations', $isEdit
    ? $payment->allocations->map(fn ($a) => [
        'supplier_invoice_id' => $a->allocatable_id,
        'amount'              => (string) $a->amount,
        'invoice'             => $a->allocatable,
    ])->all()
    : []
))

{{-- If we came from an invoice show page with ?supplier_invoice_id=…, seed one row --}}
@if(! $isEdit && empty($existingAllocations) && isset($prefillInvoice) && $prefillInvoice)
    @php($existingAllocations = [[
        'supplier_invoice_id' => $prefillInvoice->id,
        'amount'              => $prefillInvoice->outstanding(),
        'invoice'             => $prefillInvoice,
    ]])
@endif

{{-- Header --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">Payment Header</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="vendor_id">Vendor <span class="text-danger">*</span></label>
                <select id="vendor_id" name="vendor_id"
                        class="form-select @error('vendor_id') is-invalid @enderror" required
                        onchange="paymentLoadOutstanding(this.value)">
                    <option value="">— Select vendor —</option>
                    @foreach($vendors as $v)
                        <option value="{{ $v->id }}"
                            @selected((int) old('vendor_id', $payment->vendor_id) === $v->id)>
                            {{ $v->code }} — {{ $v->name }}
                        </option>
                    @endforeach
                </select>
                @error('vendor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-2">
                <label class="form-label" for="payment_date">Payment Date <span class="text-danger">*</span></label>
                <input id="payment_date" name="payment_date" type="date"
                       class="form-control @error('payment_date') is-invalid @enderror"
                       value="{{ old('payment_date', $payment->payment_date?->toDateString() ?? now()->toDateString()) }}"
                       required>
                @error('payment_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-2">
                <label class="form-label" for="billing_month">Billing Month</label>
                <input id="billing_month"
                       name="billing_month"
                       type="month"
                       class="form-control @error('billing_month') is-invalid @enderror"
                       value="{{ old('billing_month', $payment->billing_month ?? $payment->payment_date?->format('Y-m') ?? now()->format('Y-m')) }}">
                @error('billing_month')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-2">
                <label class="form-label" for="currency_code">Currency <span class="text-danger">*</span></label>
                <input id="currency_code" name="currency_code" maxlength="3"
                       class="form-control @error('currency_code') is-invalid @enderror"
                       value="{{ old('currency_code', $payment->currency_code) }}" required>
                @error('currency_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-2">
                <label class="form-label" for="payment_method">Method <span class="text-danger">*</span></label>
                <select id="payment_method" name="payment_method" class="form-select" required>
                    @foreach(\App\Enums\PaymentMethod::cases() as $m)
                        <option value="{{ $m->value }}"
                            @selected(old('payment_method', $payment->payment_method?->value) === $m->value)>
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
                            @selected((int) old('bank_account_id', $payment->bank_account_id) === $ba->id)>
                            {{ $ba->code }} — {{ $ba->name }}
                        </option>
                    @endforeach
                </select>
                <div class="form-text">Leave blank to pay from cash.</div>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="reference">Reference</label>
                <input id="reference" name="reference"
                       class="form-control @error('reference') is-invalid @enderror"
                       value="{{ old('reference', $payment->reference) }}" maxlength="128"
                       placeholder="Cheque #, transaction ID, etc.">
                @error('reference')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="amount">Payment Amount <span class="text-danger">*</span></label>
                <input id="amount" name="amount" type="number" step="0.0001" min="0.0001"
                       class="form-control @error('amount') is-invalid @enderror"
                       value="{{ old('amount', $payment->amount) }}" required>
                @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">Auto-derived from allocations if left at zero.</div>
            </div>
            <div class="col-12">
                <label class="form-label" for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="2"
                          class="form-control @error('notes') is-invalid @enderror"
                          maxlength="2000">{{ old('notes', $payment->notes) }}</textarea>
                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>

{{-- Allocations --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span>Invoice Allocations</span>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="paymentAddAllocation()">
                <i class="bi bi-plus-lg"></i> Add Invoice
            </button>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="paymentAutoAllocate()">
                <i class="bi bi-magic"></i> Auto Allocate by Due Date
            </button>
        </div>
    </div>
    <div class="card-body" id="payment-allocations-container">
        @forelse($existingAllocations as $i => $a)
            @include('procurement::vendor-payments._allocation-row', ['index' => $i, 'allocation' => $a])
        @empty
            <div class="text-muted" id="payment-no-allocations">
                No allocations yet. Pick a vendor to load their open invoices, or add one manually.
            </div>
        @endforelse
    </div>
    <div class="card-footer bg-white d-flex justify-content-end gap-4">
        <div>Allocated: <strong id="allocated-total">0.0000</strong></div>
        <div>Unallocated: <strong id="unallocated-total">0.0000</strong></div>
    </div>
</div>
