@php($i = $index)
@php($l = is_array($line) ? $line : [])
<div class="line-row border rounded p-2 mb-2 bg-light-subtle">
    <div class="row g-2 align-items-end">
        @include('inventory::brands._select', ['index' => $i, 'selectedBrandId' => $l['brand_id'] ?? null])

        <div class="col-md-4">
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
        <div class="col-md-2">
            <label class="form-label small mb-1">Unit</label>
            <select name="lines[{{ $i }}][unit_id]" class="form-select form-select-sm">
                <option value="">—</option>
                @foreach($units as $u)
                    <option value="{{ $u->id }}" @selected((int) ($l['unit_id'] ?? 0) === $u->id)>
                        {{ $u->code }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1">Quantity <span class="text-danger">*</span></label>
            <input name="lines[{{ $i }}][quantity]" type="number" step="0.0001" min="0.0001"
                   class="form-control form-control-sm" value="{{ $l['quantity'] ?? '' }}" required>
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">Specification</label>
            <input name="lines[{{ $i }}][specification]"
                   class="form-control form-control-sm" maxlength="500"
                   value="{{ $l['specification'] ?? '' }}">
        </div>
        <div class="col-md-1 text-end">
            <button type="button" class="btn btn-sm btn-outline-danger"
                    onclick="rfqRemoveLine(this)" title="Remove this line">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>

    {{-- Hidden source link — populated when the RFQ originates from a PR --}}
    <input type="hidden"
           name="lines[{{ $i }}][purchase_requisition_line_id]"
           value="{{ $l['purchase_requisition_line_id'] ?? '' }}">
</div>
