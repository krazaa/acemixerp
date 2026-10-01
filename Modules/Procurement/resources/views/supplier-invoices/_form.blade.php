@php($existingLines = old(
    'lines',
    $invoice->exists
        ? ($invoice->lines?->toArray() ?? [])
        : ($prefilledLines ?? [])
))

{{-- ─── Header ─────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">
                    Purchase Order <span class="text-danger">*</span>
                </label>
                @if($purchaseOrder)
                    <div class="form-control bg-light" readonly>
                        <code>{{ $purchaseOrder->number }}</code>
                        — {{ $purchaseOrder->vendor?->name }}
                    </div>
                    <input type="hidden" name="purchase_order_id" value="{{ $purchaseOrder->id }}">
                    <input type="hidden" name="vendor_id" value="{{ $purchaseOrder->vendor_id }}">
                    <div class="form-text">
                        Currency {{ $purchaseOrder->currency_code }} · Order {{ $purchaseOrder->order_date->format('Y-m-d') }}
                    </div>
                @else
                    <select name="purchase_order_id"
                            class="form-select @error('purchase_order_id') is-invalid @enderror"
                            required>
                        <option value="">— Select PO —</option>
                        @foreach($purchaseOrders as $po)
                            <option value="{{ $po->id }}"
                                @selected((int) old('purchase_order_id') === $po->id)>
                                {{ $po->number }} — {{ $po->vendor?->name }} ({{ $po->currency_code }})
                            </option>
                        @endforeach
                    </select>
                    @error('purchase_order_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @endif
            </div>
            <div class="col-md-3">
                <label class="form-label" for="vendor_invoice_number">
                    Vendor Invoice # <span class="text-danger">*</span>
                </label>
                <input id="vendor_invoice_number" name="vendor_invoice_number"
                       class="form-control @error('vendor_invoice_number') is-invalid @enderror"
                       value="{{ old('vendor_invoice_number', $invoice->vendor_invoice_number) }}"
                       required maxlength="64">
                @error('vendor_invoice_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label" for="currency_code">
                    Currency <span class="text-danger">*</span>
                </label>
                <input id="currency_code" name="currency_code" maxlength="3"
                       class="form-control @error('currency_code') is-invalid @enderror"
                       value="{{ old('currency_code', $invoice->currency_code) }}"
                       required placeholder="USD">
                @error('currency_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label" for="invoice_date">
                    Invoice Date <span class="text-danger">*</span>
                </label>
                <input id="invoice_date" name="invoice_date" type="date"
                       class="form-control @error('invoice_date') is-invalid @enderror"
                       value="{{ old('invoice_date', $invoice->invoice_date?->toDateString() ?? now()->toDateString()) }}"
                       required>
                @error('invoice_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label" for="due_date">
                    Due Date <span class="text-danger">*</span>
                </label>
                <input id="due_date" name="due_date" type="date"
                       class="form-control @error('due_date') is-invalid @enderror"
                       value="{{ old('due_date', $invoice->due_date?->toDateString() ?? now()->addDays(30)->toDateString()) }}"
                       required>
                @error('due_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label class="form-label" for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="2"
                          class="form-control @error('notes') is-invalid @enderror"
                          maxlength="2000">{{ old('notes', $invoice->notes) }}</textarea>
                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>

{{-- ─── Lines ─────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span>Lines</span>
        @if(! empty($existingLines))
            <span class="text-muted small">{{ count($existingLines) }} line(s) to invoice</span>
        @elseif(! $purchaseOrder)
            <span class="text-muted small">Pick a PO first</span>
        @endif
    </div>
    <div class="card-body" id="si-lines-container">
        @forelse($existingLines as $i => $l)
            @php($poLineId = (int) ($l['purchase_order_line_id'] ?? 0))
            @php($poLine = $purchaseOrder?->lines?->firstWhere('id', $poLineId)
                          ?? $invoice->purchaseOrder?->lines?->firstWhere('id', $poLineId))
            @php($l['purchase_order_line'] = $poLine)
            @include('procurement::supplier-invoices._line-row', [
                'index' => $i,
                'line'  => $l,
            ])
        @empty
            <div class="text-muted">
                @if($purchaseOrder)
                    This purchase order has no invoicable lines right now.
                    Either nothing has been received against it yet,
                    or every received line is already fully invoiced.
                    <a href="{{ route('procurement.goods-receipts.create', ['purchase_order_id' => $purchaseOrder->id]) }}"
                       class="ms-1">Record a goods receipt</a>
                    against this PO to make its lines available.
                @else
                    Select a purchase order above to load its invoice lines.
                @endif
            </div>
        @endforelse
    </div>

    @if(! empty($existingLines))
        <div class="card-footer bg-white d-flex justify-content-end gap-4">
            <div>Subtotal: <strong id="si-subtotal">0.00</strong></div>
            <div>Tax: <strong id="si-tax">0.00</strong></div>
            <div>WHT: <strong id="si-wht">0.00</strong></div>
            <div>Total: <strong id="si-total">0.00</strong></div>
        </div>
    @endif
</div>
