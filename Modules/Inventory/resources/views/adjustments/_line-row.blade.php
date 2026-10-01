@php($line = is_array($line) ? $line : $line->toArray())
<div class="adjustment-line border rounded p-3 mb-2 bg-light-subtle">
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label small mb-1">Item <span class="text-danger">*</span></label>
            <select name="lines[{{ $index }}][item_id]" class="form-select form-select-sm" data-control="select2" required>
                <option value="">— Select item —</option>
                @foreach($items as $item)
                    <option value="{{ $item->id }}" @selected((int) ($line['item_id'] ?? 0) === $item->id)>
                       {{ $item->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1">Quantity <span class="text-danger">*</span></label>
            <input name="lines[{{ $index }}][quantity]" type="number" step="0.0001" class="form-control form-control-sm"
                   value="{{ $line['quantity'] ?? '' }}" required>
            <div class="form-text">+ add / − remove</div>
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1">Unit Cost</label>
            <input name="lines[{{ $index }}][unit_cost]" type="number" min="0" step="0.0001" class="form-control form-control-sm"
                   value="{{ $line['unit_cost'] ?? '' }}">
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">Line Notes</label>
            <input name="lines[{{ $index }}][notes]" maxlength="500" class="form-control form-control-sm"
                   value="{{ $line['notes'] ?? '' }}">
        </div>
        <div class="col-md-1 text-end">
            <button type="button" class="btn btn-sm btn-outline-danger remove-adjustment-line" aria-label="Remove line">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>
</div>
