@php($i = $index)
@php($l = is_array($line) ? $line : [])
@php($poLine = $l['purchase_order_line'] ?? null)
<div class="line-row border rounded p-2 mb-2 bg-light-subtle">
    <div class="row g-2 align-items-end">
        @include('inventory::brands._select', ['index' => $i, 'selectedBrandId' => $l['brand_id'] ?? ($poLine?->brand_id ?? null), 'lockedBrandId' => $poLine?->brand_id])
        <div class="col-md-3">
            <label class="form-label small mb-1">PO Line</label>
            <div class="form-control form-control-sm bg-white" readonly>
                @if($poLine)
                    <code>{{ $poLine->item?->code }}</code> {{ $poLine->item?->name }}
                @else
                    <span class="text-muted">— unknown item —</span>
                @endif
            </div>
            <input type="hidden"
                   name="lines[{{ $i }}][purchase_order_line_id]"
                   value="{{ $l['purchase_order_line_id'] ?? '' }}">
            <input type="hidden"
                   name="lines[{{ $i }}][item_id]"
                   value="{{ $l['item_id'] ?? ($poLine?->item_id ?? '') }}">
        </div>

        <div class="col-md-1">
            <label class="form-label small mb-1">Accepted</label>
            <div class="form-control form-control-sm bg-white text-end" readonly>
                {{ number_format((float) ($poLine?->received_quantity ?? 0), 0) }}
            </div>
        </div>

        <div class="col-md-1">
            <label class="form-label small mb-1">
                Invoiced Qty <span class="text-danger">*</span>
            </label>
            <input name="lines[{{ $i }}][quantity]"
                   type="number" step="0.0001" min="0.0001"
                   class="form-control form-control-sm si-qty"
                   value="{{ $l['quantity'] ?? '' }}" required>
        </div>

        <div class="col-md-2">
            <label class="form-label small mb-1">
                Unit Price <span class="text-danger">*</span>
                @if($poLine)
                <div class="form-text small">PO: {{ number_format((float) $poLine->unit_price, 2) }}</div>
            @endif
            </label>
            <input name="lines[{{ $i }}][unit_price]"
                   type="number" step="0.0001" min="0"
                   class="form-control form-control-sm si-price"
                   value="{{ $l['unit_price'] ?? '' }}" required>

        </div>

        <div class="col-md-1">
            <label class="form-label small mb-1">Tax %</label>
            <input name="lines[{{ $i }}][tax_rate]"
                   type="number" step="0.01" min="0" max="100"
                   class="form-control form-control-sm si-tax"
                   value="{{ $l['tax_rate'] ?? ($poLine?->tax_rate ?? 0) }}">
        </div>

        <div class="col-md-1">
            <label class="form-label small mb-1">WHT %</label>
            <input name="lines[{{ $i }}][wht_rate]"
                   type="number" step="0.01" min="0" max="100"
                   class="form-control form-control-sm si-wht"
                   value="{{ $l['wht_rate'] ?? 0 }}">
        </div>

        <div class="col-md-1 text-end">
            <button type="button" class="btn btn-sm btn-outline-danger"
                    onclick="siRemoveLine(this)"
                    title="Remove this line">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>

    <div class="row g-2 mt-1">
        <div class="col-md-9">
            <label class="form-label small mb-1">Description</label>
            <input name="lines[{{ $i }}][description]"
                   class="form-control form-control-sm" maxlength="500"
                   value="{{ $l['description'] ?? '' }}">
        </div>
        <div class="col-md-3 text-end">
            <label class="form-label small mb-1">Line Total</label>
            <div class="fw-semibold si-line-total">0.00</div>
        </div>
    </div>
</div>
