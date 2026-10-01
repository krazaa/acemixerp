@php($isEdit = $customer->exists)

{{-- Identity --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="name">Trading Name <span class="text-danger">*</span></label>
                <input id="name" name="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $customer->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="legal_name">Legal Name</label>
                <input id="legal_name" name="legal_name" class="form-control"
                       value="{{ old('legal_name', $customer->legal_name) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="legal_name">Contact Person Name</label>
                <input id="c_person" name="c_person" class="form-control"
                       value="{{ old('c_person', $customer->c_person) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="tax_number">Tax Number</label>
                <input id="tax_number" name="tax_number" class="form-control"
                       value="{{ old('tax_number', $customer->tax_number) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="registration_number">Registration #</label>
                <input id="registration_number" name="registration_number" class="form-control"
                       value="{{ old('registration_number', $customer->registration_number) }}">
            </div>
        </div>
    </div>
</div>

{{-- Contact --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="email">Email</label>
                <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror"
                       value="{{ old('email', $customer->email) }}">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="phone">Phone</label>
                <input id="phone" name="phone" class="form-control"
                       value="{{ old('phone', $customer->phone) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="cp_phone">Contact person phone</label>
                <input id="cp_phone" name="cp_phone" type="text" class="form-control"
                       value="{{ old('cp_phone', $customer->cp_phone) }}">
            </div>
        </div>
    </div>
</div>

{{-- Financial --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label" for="credit_limit">Credit Limit</label>
                <input id="credit_limit" name="credit_limit" type="number" step="0.0001" min="0"
                       class="form-control"
                       value="{{ old('credit_limit', $customer->credit_limit ?? 0) }}">
                <div class="form-text">0 = unlimited</div>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="credit_days">Credit Days</label>
                <input id="credit_days" name="credit_days" type="number" min="0" max="365"
                       class="form-control"
                       value="{{ old('credit_days', $customer->credit_days ?? 0) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="status">Status <span class="text-danger">*</span></label>
                <select id="status" name="status" class="form-select" required>
                    @foreach(\App\Enums\CustomerStatus::cases() as $s)
                        <option value="{{ $s->value }}"
                            @selected(old('status', $customer->status?->value) === $s->value)>
                            {{ $s->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                <div class="form-check form-switch">
                    <input type="hidden" name="is_tax_exempt" value="0">
                    <input id="is_tax_exempt" name="is_tax_exempt" type="checkbox" value="1"
                           class="form-check-input"
                           @checked(old('is_tax_exempt', $customer->is_tax_exempt))>
                    <label class="form-check-label" for="is_tax_exempt">Tax Exempt</label>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Addresses --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span>Addresses</span>
        <button type="button" class="btn btn-sm btn-outline-secondary"
                onclick="addAddressRow()">Add Address</button>
    </div>
    <div class="card-body" id="addresses-container">
        @php($existing = old('addresses', $customer->addresses?->toArray() ?? []))
        @foreach($existing as $i => $addr)
            @include('customers._address-row', ['index' => $i, 'address' => $addr])
        @endforeach
    </div>
</div>

<div class="col-12">
    <label class="form-label" for="notes">Notes</label>
    <textarea id="notes" name="notes" rows="2" class="form-control">{{ old('notes', $customer->notes) }}</textarea>
</div>

@push('scripts')
<script>
let addressIndex = {{ count($existing) }};
function addAddressRow() {
    const html = `@include('customers._address-row', ['index' => '__INDEX__', 'address' => []])`;
    document.getElementById('addresses-container')
        .insertAdjacentHTML('beforeend', html.replaceAll('__INDEX__', addressIndex++));
}
function removeAddressRow(btn) {
    btn.closest('.address-row').remove();
}
</script>
@endpush
