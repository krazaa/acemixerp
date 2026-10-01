@php($i = $index)
@php($l = is_array($line) ? $line : [])
@php($poLine = $l['purchase_order_line'] ?? null)
<div class="grn-line border border-dashed rounded p-5 mb-5">
    <div class="row g-4 align-items-end">
        @include('inventory::brands._select', ['index' => $i, 'selectedBrandId' => $l['brand_id'] ?? ($poLine?->brand_id ?? null), 'lockedBrandId' => $poLine?->brand_id])
        @include('inventory::origins._select', ['index' => $i, 'selectedOriginId' => $l['origin_id'] ?? ($poLine?->origin_id ?? null), 'lockedOriginId' => $poLine?->origin_id])
        <div class="col-md-3">
            <label class="form-label small mb-1">Item</label>
            <div class="form-control form-control-sm form-control-solid" readonly>
                <code>{{ $poLine?->item?->code }}</code> {{ $poLine?->item?->name }}
            </div>
            <input type="hidden" name="lines[{{ $i }}][purchase_order_line_id]" value="{{ $l['purchase_order_line_id'] ?? '' }}">
            <input type="hidden" name="lines[{{ $i }}][item_id]" value="{{ $l['item_id'] ?? '' }}">
        </div>
        <div class="col-md-1 text-end">
            <label class="form-label small mb-1">Ordered</label>
            <div>{{ number_format((float) ($poLine?->quantity ?? 0), 4) }}</div>
        </div>
        <div class="col-md-1 text-end">
            <label class="form-label small mb-1">Already Received</label>
            <div>{{ number_format((float) ($poLine?->received_quantity ?? 0), 4) }}</div>
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1">Received <span class="text-danger">*</span></label>
            <input name="lines[{{ $i }}][received_quantity]" type="number" step="0.0001" min="0"
                   class="form-control form-control-sm grn-received"
                   value="{{ $l['received_quantity'] ?? '' }}" required>
        </div>
        <div class="col-md-1 text-end">
            <button type="button" class="btn btn-sm btn-light-danger"
                    onclick="grnRemoveLine(this)" title="Remove this line" aria-label="Remove this receipt line">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>
    <div class="row g-4 mt-1">
        <div class="col-md-3">
            <label class="form-label small mb-1">Accepted <span class="text-danger">*</span></label>
            <input name="lines[{{ $i }}][accepted_quantity]" type="number" step="0.0001" min="0"
                   class="form-control form-control-sm grn-accepted"
                   value="{{ $l['accepted_quantity'] ?? '' }}" required>
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1">Rejected</label>
            <input name="lines[{{ $i }}][rejected_quantity]" type="number" step="0.0001" min="0"
                   class="form-control form-control-sm grn-rejected"
                   value="{{ $l['rejected_quantity'] ?? '0' }}">
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1">Batch Number</label>
            <input name="lines[{{ $i }}][batch_number]" class="form-control form-control-sm" maxlength="128"
                   value="{{ $l['batch_number'] ?? '' }}">
        </div>
         <div class="col-md-2">
            <label class="form-label small mb-1" for="manufacturing-date-{{ $i }}">Manufacturing Date</label>
            <input id="manufacturing-date-{{ $i }}" name="lines[{{ $i }}][manufacturing_date]"
                   class="form-control form-control-sm @error('lines.'.$i.'.manufacturing_date') is-invalid @enderror"
                   type="date" value="{{ $l['manufacturing_date'] ?? '' }}">
            @error('lines.'.$i.'.manufacturing_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1">Expiry Date</label>
            <input name="lines[{{ $i }}][expiry_date]" class="form-control form-control-sm" type="date"
                   value="{{ $l['expiry_date'] ?? '' }}">
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">Rejection Reason</label>
            <input name="lines[{{ $i }}][rejection_reason]"
                   class="form-control form-control-sm" maxlength="500"
                   value="{{ $l['rejection_reason'] ?? '' }}">
        </div>
        {{-- <div class="col-md-3">
            <label class="form-label small mb-1">Notes</label>
            <input name="lines[{{ $i }}][notes]"
                   class="form-control form-control-sm" maxlength="500"
                   value="{{ $l['notes'] ?? '' }}">
        </div> --}}
    </div>
</div>
