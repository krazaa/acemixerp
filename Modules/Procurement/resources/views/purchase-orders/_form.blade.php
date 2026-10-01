@php($isEdit = $purchaseOrder->exists)
@php($existingLines = old('lines', $purchaseOrder->lines?->toArray() ?? []))
@if(empty($existingLines))
    @php($existingLines = [['item_id' => null, 'quantity' => '', 'unit_price' => '']])
@endif

{{-- Header --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header">
        <h3 class="card-title">Header</h3>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="vendor_id">Vendor <span class="text-danger">*</span></label>
                <select id="vendor_id" name="vendor_id"
                        class="form-select @error('vendor_id') is-invalid @enderror" required>
                    <option value="">— Select vendor —</option>
                    @foreach($vendors as $v)
                        <option value="{{ $v->id }}" @selected((int) old('vendor_id', $purchaseOrder->vendor_id) === $v->id)>
                            {{ $v->code }} — {{ $v->name }}
                        </option>
                    @endforeach
                </select>
                @error('vendor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-2">
                <label class="form-label" for="order_date">Order Date <span class="text-danger">*</span></label>
                <input id="order_date" name="order_date" type="date"
                       class="form-control @error('order_date') is-invalid @enderror"
                       value="{{ old('order_date', $purchaseOrder->order_date?->toDateString() ?? now()->toDateString()) }}"
                       required>
                @error('order_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-2">
                <label class="form-label" for="expected_date">Expected Date</label>
                <input id="expected_date" name="expected_date" type="date"
                       class="form-control"
                       value="{{ old('expected_date', $purchaseOrder->expected_date?->toDateString()) }}">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="currency_code">Currency <span class="text-danger">*</span></label>
                <input id="currency_code" name="currency_code" maxlength="3"
                       class="form-control @error('currency_code') is-invalid @enderror"
                       value="{{ old('currency_code', $purchaseOrder->currency_code) }}" required>
                @error('currency_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-2">
                <label class="form-label" for="payment_term_id">Payment Term</label>
                <select id="payment_term_id" name="payment_term_id" class="form-select">
                    <option value="">—</option>
                    @foreach($paymentTerms as $pt)
                        <option value="{{ $pt->id }}" @selected((int) old('payment_term_id', $purchaseOrder->payment_term_id) === $pt->id)>
                            {{ $pt->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="warehouse_id">Destination Warehouse</label>
                <select id="warehouse_id" name="warehouse_id" class="form-select" data-control="select2" data-placeholder="Select warehouse" data-allow-clear="true">
                    <option value="">—</option>
                    @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}" @selected((int) old('warehouse_id', $purchaseOrder->warehouse_id) === $wh->id)>
                            {{ $wh->code }} — {{ $wh->name }}
                        </option>
                    @endforeach
                </select>
                <div class="form-text">Required before goods can be received.</div>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="department_id">Department</label>
                <select id="department_id" name="department_id" class="form-select">
                    <option value="">—</option>
                    @foreach($departments as $d)
                        <option value="{{ $d->id }}" @selected((int) old('department_id', $purchaseOrder->department_id) === $d->id)>
                            {{ $d->code }} — {{ $d->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="cost_center_id">Cost Center</label>
                <select id="cost_center_id" name="cost_center_id" class="form-select">
                    <option value="">—</option>
                    @foreach($costCenters as $c)
                        <option value="{{ $c->id }}" @selected((int) old('cost_center_id', $purchaseOrder->cost_center_id) === $c->id)>
                            {{ $c->code }} — {{ $c->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="reference">Reference</label>
                <input id="reference" name="reference" class="form-control"
                       value="{{ old('reference', $purchaseOrder->reference) }}" maxlength="64">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="shipping_address">Shipping Address</label>
                <input id="shipping_address" name="shipping_address" class="form-control"
                       value="{{ old('shipping_address', $purchaseOrder->shipping_address) }}" maxlength="500">
            </div>
        </div>
    </div>
</div>

{{-- Lines --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span>Lines</span>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="poAddLine()">
            <i class="bi bi-plus-lg"></i> Add Line
        </button>
    </div>
    <div class="card-body" id="po-lines-container">
        @foreach($existingLines as $i => $line)
            @include('procurement::purchase-orders._line-row', [
                'index' => $i,
                'line'  => $line,
                'items' => $items,
                'units' => $units,
            ])
        @endforeach
    </div>
    <div class="card-footer bg-white">
        <div class="row text-end g-2">
            <div class="col-md-4">Subtotal: <strong id="po-subtotal">0.00</strong></div>
            <div class="col-md-4">Tax: <strong id="po-tax">0.00</strong></div>
            <div class="col-md-4">Total: <strong id="po-total">0.00</strong></div>
        </div>
    </div>
</div>

{{-- Terms & notes --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header">
        <h3 class="card-title">Terms &amp; Notes    </h3></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label" for="terms">Terms</label>
                <textarea id="terms" name="terms" rows="3" class="form-control" maxlength="5000">{{ old('terms', $purchaseOrder->terms) }}</textarea>
            </div>
            <div class="col-12">
                <label class="form-label" for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="2" class="form-control" maxlength="2000">{{ old('notes', $purchaseOrder->notes) }}</textarea>
            </div>
        </div>
    </div>
</div>
