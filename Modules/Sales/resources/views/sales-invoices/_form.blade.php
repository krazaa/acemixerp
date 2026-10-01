@php($existingLines = old('lines', $invoice->lines?->toArray() ?? []))

@if(! $invoice->exists && $salesOrder && empty($existingLines))
    @php($existingLines = $salesOrder->lines
        ->filter(fn ($l) => bccomp((string) $l->delivered_quantity, (string) $l->invoiced_quantity, 4) > 0)
        ->map(function ($l) {
            return [
                'item_id'             => $l->item_id,
                'unit_id'             => $l->unit_id,
                'quantity'            => bcsub((string) $l->delivered_quantity, (string) $l->invoiced_quantity, 4),
                'unit_price'          => (string) $l->unit_price,
                'discount_percent'    => (string) $l->discount_percent,
                'tax_rate'            => (string) $l->tax_rate,
                'wht_tax_rate'        => (string) ($l->wht_tax_rate ?? '0'),
                'description'         => $l->description,
                'sales_order_line_id' => $l->id,
            ];
        })->values()->all())
@endif

@if(empty($existingLines))
    @php($existingLines = [['item_id' => null, 'quantity' => '', 'unit_price' => '']])
@endif

{{-- Header --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header">
        <h3 class="card-title">Invoice Header</h3>
    </div>
    <div class="card-body">
        <div class="row g-3">
            @if($salesOrder)
                <div class="col-md-6">
                    <label class="form-label">Sales Order</label>
                    <div class="form-control bg-light" readonly>
                        <code>{{ $salesOrder->number }}</code> — {{ $salesOrder->customer?->name }}
                    </div>
                    <input type="hidden" name="sales_order_id" value="{{ $salesOrder->id }}">
                </div>
            @else
                <div class="col-md-6">
                    <label class="form-label" for="sales_order_id">Sales Order (optional)</label>
                    <select id="sales_order_id" name="sales_order_id" class="form-select">
                        <option value="">— No SO (standalone invoice) —</option>
                        @foreach($openOrders as $o)
                            <option value="{{ $o->id }}" @selected((int) old('sales_order_id') === $o->id)>
                                {{ $o->number }} — {{ $o->customer?->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="col-md-6">
                <label class="form-label" for="customer_id">Customer <span class="text-danger">*</span></label>
                <select id="customer_id" name="customer_id" class="form-select" data-control="select2" required>
                    <option disabled>— Select customer —</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}" @selected((int) old('customer_id', $invoice->customer_id) === $c->id)>
                            {{ $c->code }} — {{ $c->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label" for="invoice_date">Invoice Date <span class="text-danger">*</span></label>
                <input id="invoice_date" name="invoice_date" type="date" class="form-control"
                       value="{{ old('invoice_date', $invoice->invoice_date?->toDateString() ?? now()->toDateString()) }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="due_date">Due Date <span class="text-danger">*</span></label>
                <input id="due_date" name="due_date" type="date" class="form-control"
                       value="{{ old('due_date', $invoice->due_date?->toDateString() ?? now()->addDays(30)->toDateString()) }}" required>
            </div>

            <div class="col-md-3">
                <label class="form-label" for="warehouse_id">Warehouse</label>
                <select id="warehouse_id" name="warehouse_id" class="form-select">
                    <option value="">—</option>
                    @foreach($warehouses as $w)
                        <option value="{{ $w->id }}" @selected((int) old('warehouse_id', $invoice->warehouse_id) === $w->id)>{{ $w->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="payment_term_id">Payment Term</label>
                <select id="payment_term_id" name="payment_term_id" class="form-select">
                    <option value="">—</option>
                    @foreach($paymentTerms as $pt)
                        <option value="{{ $pt->id }}" @selected((int) old('payment_term_id', $invoice->payment_term_id) === $pt->id)>{{ $pt->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="currency_code">Currency <span class="text-danger">*</span></label>
                <input id="currency_code" name="currency_code" maxlength="3" class="form-control"
                       value="{{ old('currency_code', $invoice->currency_code) }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="reference">Reference</label>
                <input id="reference" name="reference" class="form-control"
                       value="{{ old('reference', $invoice->reference) }}" maxlength="64">
            </div>
              <div class="col-md-6">
                <label class="form-label" for="employee_id">Salesperson <span class="text-danger">*</span></label>
                <select id="employee_id" name="employee_id" class="form-select" data-control="select2" required>
                    <option disabled>— Select customer —</option>
                    @foreach($salespersons as $sp)
                        <option value="{{ $sp->id }}" @selected((int) old('employee_id', $invoice->employee_id) === $sp->id)>
                            {{ $sp->number }} — {{ $sp->full_name ?? '' }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</div>

{{-- Lines --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header">
        <h3 class="card-title">Lines</h3>

        <div class="card-toolbar">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="siAddLine()">
            <i class="bi bi-plus-lg"></i> Add Line
            </button>
        </div>
    </div>
    <div class="card-body" id="si-lines-container">
        @foreach($existingLines as $i => $line)
            @include('sales::sales-invoices._line-row', [
                'index' => $i, 'line' => $line, 'items' => $items, 'units' => $units,
            ])
        @endforeach
    </div>
    <div class="card-footer bg-white">
        <div class="row text-end g-2">
            <div class="col-md-3">Subtotal: <strong id="si-subtotal">0.0000</strong></div>
            <div class="col-md-3">GST: <strong id="si-tax">0.0000</strong></div>
            <div class="col-md-3 text-danger">WHT: <strong id="si-wht">0.0000</strong></div>
            <div class="col-md-3">Total: <strong id="si-total">0.0000</strong></div>
        </div>
    </div>
</div>

{{-- Notes --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header">
        <h3 class="card-title">Terms &amp; Notes</h3>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label" for="terms">Terms</label>
                <textarea id="terms" name="terms" rows="3" class="form-control" maxlength="5000">{{ old('terms', $invoice->terms) }}</textarea>
            </div>
            <div class="col-12">
                <label class="form-label" for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="2" class="form-control" maxlength="2000">{{ old('notes', $invoice->notes) }}</textarea>
            </div>
        </div>
    </div>
</div>
