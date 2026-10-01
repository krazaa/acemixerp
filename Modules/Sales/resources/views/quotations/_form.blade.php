@php($isEdit = $quotation->exists)
@php($existingLines = old('lines', $quotation->lines?->toArray() ?? []))
@if(empty($existingLines))
    @php($existingLines = [['item_id' => null, 'quantity' => '', 'unit_price' => '']])
@endif

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="customer_id">Customer <span class="text-danger">*</span></label>
                <select id="customer_id" name="customer_id" class="form-select form-select-sm" data-control="select2" required>
                    <option disabled>— Select customer —</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}" @selected((int) old('customer_id', $quotation->customer_id) === $c->id)>
                            {{ $c->code }} — {{ $c->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="quotation_date">Quotation Date <span class="text-danger">*</span></label>
                <input id="quotation_date" name="quotation_date" type="date" class="form-control form-control-sm"
                       value="{{ old('quotation_date', $quotation->quotation_date?->toDateString() ?? now()->toDateString()) }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="valid_until">Valid Until</label>
                <input id="valid_until" name="valid_until" type="date" class="form-control form-control-sm"
                       value="{{ old('valid_until', $quotation->valid_until?->toDateString()) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="salesperson_id">Salesperson</label>
                <select id="salesperson_id" name="salesperson_id" class="form-select form-select-sm" data-control="select2">
                    <option disabled>—</option>
                    @foreach($salespersons as $sp)<option value="{{ $sp->id }}" @selected((int) old('salesperson_id', $quotation->salesperson_id) === $sp->id)>{{ $sp->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="warehouse_id">Warehouse</label>
                <select id="warehouse_id" name="warehouse_id" class="form-select form-select-sm">
                    <option disabled>—</option>
                    @foreach($warehouses as $wh)<option value="{{ $wh->id }}" @selected((int) old('warehouse_id', $quotation->warehouse_id) === $wh->id)>{{ $wh->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="department_id">Department</label>
                <select id="department_id" name="department_id" class="form-select form-select-sm">
                    <option disabled>—</option>
                    @foreach($departments as $d)<option value="{{ $d->id }}" @selected((int) old('department_id', $quotation->department_id) === $d->id)>{{ $d->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="payment_term_id">Payment Term</label>
                <select id="payment_term_id" name="payment_term_id" class="form-select form-select-sm">
                    <option disabled>—</option>
                    @foreach($paymentTerms as $pt)<option value="{{ $pt->id }}" @selected((int) old('payment_term_id', $quotation->payment_term_id) === $pt->id)>{{ $pt->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="currency_code">Currency <span class="text-danger">*</span></label>
                <input id="currency_code" name="currency_code" maxlength="3" class="form-control form-select-sm"
                       value="{{ old('currency_code', $quotation->currency_code) }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="reference">Reference</label>
                <input id="reference" name="reference" class="form-control form-select-sm"
                       value="{{ old('reference', $quotation->reference) }}" maxlength="64">
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span>Lines</span>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="soAddLine()">
            <i class="bi bi-plus-lg"></i> Add Line
        </button>
    </div>
    <div class="card-body" id="so-lines-container">
        @foreach($existingLines as $i => $line)
            @include('sales::quotations._line-row', ['index' => $i, 'line' => $line, 'items' => $items, 'units' => $units])
        @endforeach
    </div>
    <div class="card-footer bg-white d-flex justify-content-end gap-4">
        <div>Subtotal: <strong id="so-subtotal">0.00</strong></div>
        <div>GST: <strong id="so-tax">0.00</strong></div>
        <div>WHT: <strong id="so-whttax">0.00</strong></div>
        <div>Total: <strong id="so-total">0.00</strong></div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header">
                <h3 class="card-title">Terms &amp; Notes</h3>
            </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-12"><label class="form-label" for="terms">Terms</label>
                <textarea id="terms" name="terms" rows="3" class="form-control" maxlength="5000">{{ old('terms', $quotation->terms) }}</textarea>
            </div>
            <div class="col-12"><label class="form-label" for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="2" class="form-control" maxlength="2000">{{ old('notes', $quotation->notes) }}</textarea>
            </div>
        </div>
    </div>
</div>
