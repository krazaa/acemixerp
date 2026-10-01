@php($i = $index)
@php($l = is_array($line) ? $line : [])
<div class="line-row border rounded p-2 mb-2 bg-light-subtle">
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label small mb-1">Ingredient <span class="text-danger">*</span></label>
            <select name="lines[{{ $i }}][component_id]" class="form-select form-select-sm  bom-ingredient-select" data-control="select2" required>
                <option value="">— Select ingredient —</option>
                @foreach($components as $c)
                    <option value="{{ $c->id }}" @selected((int) ($l['component_id'] ?? 0) === $c->id)>
                         {{ $c->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label small mb-1">Stock Batch Number</label>
            <select name="lines[{{ $i }}][batch_id]" class="form-select form-select-sm bom-batch-select" data-selected-batch="{{ $l['batch_id'] ?? '' }}" disabled>
                <option value="">Select ingredient first</option>
            </select>
        </div>
        <div class="col-md-1">
            <label class="form-label small mb-1">Unit</label>
            <select name="lines[{{ $i }}][unit_id]" class="form-select form-select-sm">
                <option value="">—</option>
                @foreach($units as $u)
                    <option value="{{ $u->id }}" @selected((int) ($l['unit_id'] ?? 0) === $u->id)>{{ $u->code }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-1">
            <label class="form-label small mb-1">Quantity <span class="text-danger">*</span></label>
            <input name="lines[{{ $i }}][quantity]" type="number" step="0.0001" min="0.0001"
                   class="form-control form-control-sm" value="{{ $l['quantity'] ?? '' }}" required>
        </div>
        <div class="col-md-1">
            <label class="form-label small mb-1">Scrap %</label>
            <input name="lines[{{ $i }}][scrap_percent]" type="number" step="0.000001" min="0" max="100"
                   class="form-control form-control-sm" value="{{ $l['scrap_percent'] ?? '' }}">
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="bomRemoveLine(this)">
                <i class="bi bi-x-lg"></i>
            </button>
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
