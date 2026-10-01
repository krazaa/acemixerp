@php($i = $index)
@php($l = is_array($line) ? $line : [])
@php($soLine = $l['sales_order_line'] ?? null)
<div class="line-row border rounded p-2 mb-2 bg-light-subtle">
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label small mb-1">Item</label>
            <div class="form-control form-control-sm bg-white" readonly>
                @if($soLine)
                    <code>{{ $soLine->item?->code }}</code> {{ $soLine->item?->name }}
                @else
                    <span class="text-muted">—</span>
                @endif
            </div>
            <input type="hidden" name="lines[{{ $i }}][sales_order_line_id]"
                   value="{{ $l['sales_order_line_id'] ?? ($soLine?->id ?? '') }}">
            <input type="hidden" name="lines[{{ $i }}][item_id]"
                   value="{{ $l['item_id'] ?? ($soLine?->item_id ?? '') }}">
            <input type="hidden" name="lines[{{ $i }}][unit_id]"
                   value="{{ $l['unit_id'] ?? ($soLine?->unit_id ?? '') }}">
        </div>
        <div class="col-md-2 text-end">
            <label class="form-label small mb-1">Ordered</label>
            <div>{{ number_format((float) ($soLine?->quantity ?? 0), 4) }}</div>
        </div>
        <div class="col-md-2 text-end">
            <label class="form-label small mb-1">Already Delivered</label>
            <div>{{ number_format((float) ($soLine?->delivered_quantity ?? 0), 4) }}</div>
        </div>
        <div class="col-md-1 text-end">
            <label class="form-label small mb-1">Open</label>
            <div>{{ number_format((float) ($soLine?->openQuantity() ?? 0), 4) }}</div>
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1">This Delivery <span class="text-danger">*</span></label>
            <input name="lines[{{ $i }}][quantity]" type="number" step="0.01" min="0.01"
                   class="form-control form-control-sm" value="{{ $l['quantity'] ?? '' }}" required>
            <input type="hidden" name="lines[{{ $i }}][unit_price]"
                   value="{{ $l['unit_price'] ?? ($soLine?->unit_price ?? 0) }}">
        </div>
    </div>
    <div class="row g-2 mt-1">
        <div class="col-12">
            <label class="form-label small mb-1">Notes</label>
            <input name="lines[{{ $i }}][notes]" class="form-control form-control-sm"
                   maxlength="500" value="{{ $l['notes'] ?? '' }}">
        </div>
    </div>
</div>
