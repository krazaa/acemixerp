@php($i = $index)
@php($a = is_array($address) ? $address : [])
<div class="address-row border rounded p-3 mb-2">
    <div class="d-flex justify-content-between mb-2">
        <strong>Address #<span class="address-num">{{ is_numeric($i) ? $i + 1 : '' }}</span></strong>
        <button type="button" class="btn btn-sm btn-outline-danger"
                onclick="removeAddressRow(this)">Remove</button>
    </div>
    <input type="hidden" name="addresses[{{ $i }}][id]" value="{{ $a['id'] ?? '' }}">

    <div class="row g-2">
        <div class="col-md-3">
            <label class="form-label small">Type</label>
            <select name="addresses[{{ $i }}][type]" class="form-select form-select-sm">
                @foreach(['billing','shipping','office','other'] as $t)
                    <option value="{{ $t }}" @selected(($a['type'] ?? 'billing') === $t)>{{ ucfirst($t) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small">Label</label>
            <input name="addresses[{{ $i }}][label]" class="form-control form-control-sm"
                   value="{{ $a['label'] ?? '' }}" placeholder="Head Office">
        </div>
        <div class="col-md-3">
            <div class="form-check mt-4">
                <input type="hidden" name="addresses[{{ $i }}][is_primary]" value="0">
                <input class="form-check-input" type="checkbox"
                       name="addresses[{{ $i }}][is_primary]" value="1"
                       @checked($a['is_primary'] ?? false)>
                <label class="form-check-label small">Primary</label>
            </div>
        </div>
    </div>

    <div class="row g-2 mt-1">
        <div class="col-md-4">
            <label class="form-label small">Contact Name</label>
            <input name="addresses[{{ $i }}][contact_name]" class="form-control form-control-sm"
                   value="{{ $a['contact_name'] ?? '' }}">
        </div>
        <div class="col-md-4">
            <label class="form-label small">Contact Email</label>
            <input name="addresses[{{ $i }}][contact_email]" type="email" class="form-control form-control-sm"
                   value="{{ $a['contact_email'] ?? '' }}">
        </div>
        <div class="col-md-4">
            <label class="form-label small">Contact Phone</label>
            <input name="addresses[{{ $i }}][contact_phone]" class="form-control form-control-sm"
                   value="{{ $a['contact_phone'] ?? '' }}">
        </div>
    </div>

    <div class="row g-2 mt-1">
        <div class="col-md-6">
            <label class="form-label small">Line 1 <span class="text-danger">*</span></label>
            <input name="addresses[{{ $i }}][address_line1]" class="form-control form-control-sm"
                   value="{{ $a['address_line1'] ?? '' }}" required>
        </div>
        <div class="col-md-6">
            <label class="form-label small">Line 2</label>
            <input name="addresses[{{ $i }}][address_line2]" class="form-control form-control-sm"
                   value="{{ $a['address_line2'] ?? '' }}">
        </div>
    </div>

    <div class="row g-2 mt-1">
        <div class="col-md-3">
            <label class="form-label small">City <span class="text-danger">*</span></label>
            <input name="addresses[{{ $i }}][city]" class="form-control form-control-sm"
                   value="{{ $a['city'] ?? '' }}" required>
        </div>


    </div>
</div>
