@php($i = $index)
@php($l = is_array($line) ? $line : [])
<div class="line-row border rounded p-2 mb-2 bg-light-subtle">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small mb-1">Item <span class="text-danger">*</span></label>
            <select name="lines[{{ $i }}][item_id]" class="form-select form-select-sm" required>
                <option value="">— Select item —</option>
                @foreach($items as $it)
                    <option value="{{ $it->id }}" @selected((int) ($l['item_id'] ?? 0) === $it->id)>
                         {{ $it->name }}
                    </option>
                @endforeach
            </select>
        </div>
         @include('inventory::brands._select', ['index' => $i, 'selectedBrandId' => $l['brand_id'] ?? null])
         @include('inventory::origins._select', ['index' => $i, 'selectedOriginId' => $l['origin_id'] ?? null])
        <div class="col-md-1">
            <label class="form-label small mb-1">Unit</label>
            <select name="lines[{{ $i }}][unit_id]" class="form-select form-select-sm">
                <option value="">—</option>
                @foreach($units as $u)
                    <option value="{{ $u->id }}" @selected((int) ($l['unit_id'] ?? 0) === $u->id)> {{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-1">
            <label class="form-label small mb-1">Quantity <span class="text-danger">*</span></label>
            <input name="lines[{{ $i }}][quantity]" type="number" step="0.01" min="0.01"
                   class="form-control form-control-sm line-qty" value="{{ $l['quantity'] ?? '' }}" required>
        </div>
        <div class="col-md-1">
            <label class="form-label small mb-1">Est. Unit Price</label>
            <input name="lines[{{ $i }}][estimated_unit_price]" type="number" step="0.01" min="0"
                   class="form-control form-control-sm line-price"
                   value="{{ $l['estimated_unit_price'] ?? '' }}">
        </div>
        <div class="col-md-1">
            <label class="form-label small mb-1">Required</label>
            <input name="lines[{{ $i }}][required_date]" type="date"
                   class="form-control form-control-sm" value="{{ $l['required_date'] ?? '' }}">
        </div>
        <div class="col-md-1 text-end">
            <button type="button" class="btn btn-sm btn-outline-danger"
                    onclick="prRemoveLine(this)">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>
    <div class="row g-2 mt-1">
        <div class="col-12">
            <label class="form-label small mb-1">Specification</label>
            <input name="lines[{{ $i }}][specification]"
                   class="form-control form-control-sm"
                   value="{{ $l['specification'] ?? '' }}" maxlength="500">
        </div>
    </div>
</div>
