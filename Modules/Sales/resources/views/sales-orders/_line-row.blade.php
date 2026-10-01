@php($i = $index)
@php($l = is_array($line) ? $line : [])
<div class="line-row border rounded p-2 mb-2 bg-light-subtle">
    <div class="row g-2 align-items-end">
        <div class="col-md-2">
            <label class="form-label small mb-1">Item <span class="text-danger">*</span></label>
            <select name="lines[{{ $i }}][item_id]" class="form-select form-select-sm" required>
                <option value="">— Select item —</option>
                @foreach($items as $it)
                    <option value="{{ $it->id }}" @selected((int) ($l['item_id'] ?? 0) === $it->id)>
                        {{ $it->code }} — {{ $it->name }}
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
                    <option value="{{ $u->id }}" @selected((int) ($l['unit_id'] ?? 0) === $u->id)>{{ $u->code }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-1">
            <label class="form-label small mb-1">Qty <span class="text-danger">*</span></label>
            <input name="lines[{{ $i }}][quantity]" type="number" step="0.0001" min="0.0001"
                   class="form-control form-control-sm so-qty" value="{{ $l['quantity'] ?? '' }}" required>
        </div>
        <div class="col-md-1">
            <label class="form-label small mb-1">Unit Price <span class="text-danger">*</span></label>
            <input name="lines[{{ $i }}][unit_price]" type="number" step="0.0001" min="0"
                   class="form-control form-control-sm so-price" value="{{ $l['unit_price'] ?? '' }}" required>
        </div>
        <div class="col-md-1">
            <label class="form-label small mb-1">Disc %</label>
            <input name="lines[{{ $i }}][discount_percent]" type="number" step="0.000001" min="0" max="100"
                   class="form-control form-control-sm so-disc" value="{{ $l['discount_percent'] ?? '' }}">
        </div>
        <div class="col-md-1">
            <label class="form-label small mb-1">GST %</label>
            <input name="lines[{{ $i }}][tax_rate]" type="number" step="0.000001" min="0" max="100"
                   class="form-control form-control-sm so-tax" value="{{ $l['tax_rate'] ?? '' }}">
        </div>
        <div class="col-md-1">
            <label class="form-label small mb-1">WHT %</label>
            <input name="lines[{{ $i }}][wht_tax_rate]" type="number" step="0.000001" min="0" max="100"
                   class="form-control form-control-sm so-wht" value="{{ $l['wht_tax_rate'] ?? '' }}">
        </div>
        <div class="col-md-1 text-end">
            <button type="button" class="btn btn-sm btn-light-danger" onclick="soRemoveLine(this)">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>
    <div class="row g-2 mt-1">
        <div class="col-md-8">
            <label class="form-label small mb-1">Description</label>
            <input name="lines[{{ $i }}][description]" class="form-control form-control-sm"
                   maxlength="500" value="{{ $l['description'] ?? '' }}">
        </div>
    </div>
    <input type="hidden" name="lines[{{ $i }}][sales_order_line_id]" value="{{ $l['id'] ?? $l['sales_order_line_id'] ?? '' }}">
</div>
