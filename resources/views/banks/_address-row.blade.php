
@php($i = $index)
@php($a = is_array($address) ? $address : [])
<div class="address-row border rounded p-3 mb-2 bg-light-subtle">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <strong class="text-muted small">
            Address
            @if(is_numeric($i))<span class="badge text-bg-secondary">#{{ $i + 1 }}</span>@endif
        </strong>
        <button type="button" class="btn btn-sm btn-outline-danger"
                onclick="bankRemoveAddressRow(this)" title="Remove this address">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <input type="hidden" name="addresses[{{ $i }}][id]" value="{{ $a['id'] ?? '' }}">

    <div class="row g-2">
        <div class="col-md-3">

            <label class="form-label small mb-1">Type <span class="text-danger">*</span></label>
            <select name="addresses[{{ $i }}][type]" class="form-select form-select-sm" required>
                @foreach([' ','shipping','office','other'] as $t)
                    <option value="{{ $t }}" @selected(($a['type'] ?? 'billing') === $t)>
                        {{ ucfirst($t) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">Label</label>
            <input name="addresses[{{ $i }}][label]"
                   class="form-control form-control-sm" maxlength="64"
                   value="{{ $a['label'] ?? '' }}" placeholder="Head Office">
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <div class="form-check">
                <input type="hidden" name="addresses[{{ $i }}][is_primary]" value="0">
                <input class="form-check-input" type="checkbox"
                       id="bank-addr-primary-{{ $i }}"
                       name="addresses[{{ $i }}][is_primary]" value="1"
                       @checked($a['is_primary'] ?? false)>
                <label class="form-check-label small" for="bank-addr-primary-{{ $i }}">
                    Primary address
                </label>
            </div>
        </div>
    </div>

    <div class="row g-2 mt-1">
        <div class="col-md-4">
            <label class="form-label small mb-1">Contact Name</label>
            <input name="addresses[{{ $i }}][contact_name]"
                   class="form-control form-control-sm" maxlength="128"
                   value="{{ $a['contact_name'] ?? '' }}">
        </div>
        <div class="col-md-4">
            <label class="form-label small mb-1">Contact Email</label>
            <input name="addresses[{{ $i }}][contact_email]" type="email"
                   class="form-control form-control-sm" maxlength="255"
                   value="{{ $a['contact_email'] ?? '' }}">
        </div>
        <div class="col-md-4">
            <label class="form-label small mb-1">Contact Phone</label>
            <input name="addresses[{{ $i }}][contact_phone]"
                   class="form-control form-control-sm" maxlength="32"
                   value="{{ $a['contact_phone'] ?? '' }}">
        </div>
    </div>

    <div class="row g-2 mt-1">
        <div class="col-md-6">
            <label class="form-label small mb-1">Address Line 1 <span class="text-danger">*</span></label>
            <input name="addresses[{{ $i }}][address_line1]"
                   class="form-control form-control-sm" maxlength="255"
                   value="{{ $a['address_line1'] ?? '' }}" required>
        </div>
        <div class="col-md-6">
            <label class="form-label small mb-1">Address Line 2</label>
            <input name="addresses[{{ $i }}][address_line2]"
                   class="form-control form-control-sm" maxlength="255"
                   value="{{ $a['address_line2'] ?? '' }}">
        </div>
    </div>

    <div class="row g-2 mt-1">
        <div class="col-md-3">
            <label class="form-label small mb-1">City <span class="text-danger">*</span></label>
            <input name="addresses[{{ $i }}][city]"
                   class="form-control form-control-sm" maxlength="100"
                   value="{{ $a['city'] ?? '' }}" required>
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">State / Province</label>
            <input name="addresses[{{ $i }}][state]"
                   class="form-control form-control-sm" maxlength="100"
                   value="{{ $a['state'] ?? '' }}">
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">Postal Code</label>
            <input name="addresses[{{ $i }}][postal_code]"
                   class="form-control form-control-sm" maxlength="20"
                   value="{{ $a['postal_code'] ?? '' }}">
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">Country (ISO-2) <span class="text-danger">*</span></label>
            <input name="addresses[{{ $i }}][country]" maxlength="2"
                   class="form-control form-control-sm"
                   value="{{ $a['country'] ?? 'US' }}" required placeholder="US">
        </div>
    </div>
</div>
