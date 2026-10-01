@php($isEdit = $vendor->exists)

{{-- Identity --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3">

               <div class="col-md-4">
                <label class="form-label" for="name">Categories <span class="text-danger">*</span></label>
                <select name="category_id" class="form-select">
                    <option value="">Select Category</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>

                @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="name">Trading Name <span class="text-danger">*</span></label>
                <input id="name" name="name"
                       class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $vendor->name) }}" required maxlength="192">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="legal_name">Legal Name</label>
                <input id="legal_name" name="legal_name" class="form-control @error('legal_name') is-invalid @enderror"
                       value="{{ old('legal_name', $vendor->legal_name) }}" maxlength="192">
                @error('legal_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="tax_number">Tax Number</label>
                <input id="tax_number" name="tax_number" class="form-control @error('tax_number') is-invalid @enderror"
                       value="{{ old('tax_number', $vendor->tax_number) }}" maxlength="64">
                @error('tax_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="registration_number">Registration Number</label>
                <input id="registration_number" name="registration_number"
                       class="form-control @error('registration_number') is-invalid @enderror"
                       value="{{ old('registration_number', $vendor->registration_number) }}" maxlength="64">
                @error('registration_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>

{{-- Contact --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="contact_person">Contact Person</label>
                <input id="contact_person" name="contact_person" class="form-control @error('contact_person') is-invalid @enderror"
                       value="{{ old('contact_person', $vendor->contact_person) }}" maxlength="192">
                @error('contact_person')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="email">Email</label>
                <input id="email" name="email" type="email"
                       class="form-control @error('email') is-invalid @enderror"
                       value="{{ old('email', $vendor->email) }}" maxlength="255">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="phone">Company Phone</label>
                <input id="phone" name="phone"
                       class="form-control @error('phone') is-invalid @enderror"
                       value="{{ old('phone', $vendor->phone) }}" maxlength="32">
                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>

{{-- Bank Details --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label" for="bank_name">Bank Name</label>
                <input id="bank_name" name="bank_name" class="form-control @error('bank_name') is-invalid @enderror"
                    value="{{ old('bank_name', $vendor->bank_name) }}" maxlength="150">
                @error('bank_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label" for="bank_branch">Bank Branch</label>
                <input id="bank_branch" name="bank_branch" class="form-control @error('bank_branch') is-invalid @enderror"
                    value="{{ old('bank_branch', $vendor->bank_branch) }}" maxlength="150">
                @error('bank_branch')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label" for="bank_account_number">Bank Account Number</label>
                <input id="bank_account_number" name="bank_account_number" class="form-control @error('bank_account_number') is-invalid @enderror"
                    value="{{ old('bank_account_number', $vendor->bank_account_number) }}" maxlength="255" autocomplete="off">
                @error('bank_account_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label" for="bank_iban">IBAN</label>
                <input id="bank_iban" name="bank_iban" class="form-control @error('bank_iban') is-invalid @enderror"
                    value="{{ old('bank_iban', $vendor->bank_iban) }}" maxlength="34" autocomplete="off">
                @error('bank_iban')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>

{{-- Financial --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label" for="credit_days">Credit Days</label>
                <input id="credit_days" name="credit_days" type="number" min="0" max="365"
                       class="form-control @error('credit_days') is-invalid @enderror"
                       value="{{ old('credit_days', $vendor->credit_days ?? 0) }}">
                @error('credit_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label" for="status">Status <span class="text-danger">*</span></label>
                <select id="status" name="status"
                        class="form-select @error('status') is-invalid @enderror" required>
                    @foreach(\App\Enums\VendorStatus::cases() as $s)
                        <option value="{{ $s->value }}"
                            @selected(old('status', $vendor->status?->value) === $s->value)>
                            {{ $s->label() }}
                        </option>
                    @endforeach
                </select>
                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label d-block">Tax Exempt</label>
                <div class="form-check form-switch mt-2">
                    <input type="hidden" name="is_tax_exempt" value="0">
                    <input id="is_tax_exempt" name="is_tax_exempt" type="checkbox" value="1"
                           class="form-check-input"
                           @checked(old('is_tax_exempt', $vendor->is_tax_exempt))>
                    <label class="form-check-label" for="is_tax_exempt">Exempt from tax</label>
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
                onclick="vendorAddAddressRow()">
            <i class="bi bi-plus-lg"></i> Add Address
        </button>
    </div>
    <div class="card-body" id="vendor-addresses-container">
        @php($existing = old('addresses', $vendor->addresses?->toArray() ?? []))
        @foreach($existing as $i => $addr)
            @include('vendors._address-row', ['index' => $i, 'address' => $addr])
        @endforeach
        @if(empty($existing))
            <div class="text-muted small" id="vendor-no-addresses">
                No addresses yet. Click "Add Address" to add one.
            </div>
        @endif
    </div>
</div>

{{-- Notes --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">Notes</div>
    <div class="card-body">
        <textarea id="notes" name="notes" rows="3"
                  class="form-control @error('notes') is-invalid @enderror"
                  maxlength="2000">{{ old('notes', $vendor->notes) }}</textarea>
        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
