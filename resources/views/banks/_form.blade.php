{{-- Identity --}}
<div class="card border-0 shadow-sm mb-3">

    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-2">
                <label class="form-label" for="code">Code <span class="text-danger">*</span></label>
                <input id="code" name="code" class="form-control @error('code') is-invalid @enderror"
                       value="{{ old('code', $bank->code) }}" pattern="[A-Z0-9_-]+" required>
                @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-8">
                <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                <input id="name" name="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $bank->name) }}" required maxlength="192">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-2">
                <label class="form-label" for="short_name">Short Name</label>
                <input id="short_name" name="short_name" class="form-control"
                       value="{{ old('short_name', $bank->short_name) }}" maxlength="64">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="account_number">Account Number</label>
                <input id="account_number" name="account_number" class="form-control"
                       value="{{ old('account_number', $bank->account_number) }}" maxlength="32">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="iban">Iban</label>
                <input id="iban" name="iban" class="form-control"
                       value="{{ old('iban', $bank->iban) }}" maxlength="16">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="status">Status <span class="text-danger">*</span></label>
                <select id="status" name="status" class="form-select" required>
                    @foreach(\App\Enums\RecordStatus::cases() as $s)
                        <option value="{{ $s->value }}" @selected(old('status', $bank->status?->value) === $s->value)>
                            {{ $s->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</div>

{{-- Contacts --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span>Contacts</span>
        <button type="button" class="btn btn-sm btn-outline-secondary"
                onclick="bankAddContactRow()">
            <i class="bi bi-plus-lg"></i> Add Contact
        </button>
    </div>
    <div class="card-body" id="bank-contacts-container">
        @php($existingContacts = old('bank_contacts', $bank->bank_contacts ?? []))
        @foreach($existingContacts as $i => $contact)
            @include('banks._contact-row', ['index' => $i, 'contact' => $contact])
        @endforeach
        @if(empty($existingContacts))
            <div class="text-muted small" id="bank-no-contacts">
                No contacts yet. Click "Add Contact" to add one.
            </div>
        @endif
    </div>
</div>

{{-- Addresses --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span>Addresses</span>
        <button type="button" class="btn btn-sm btn-outline-secondary"
                onclick="bankAddAddressRow()">
            <i class="bi bi-plus-lg"></i> Add Address
        </button>
    </div>
    <div class="card-body" id="bank-addresses-container">
        @php($existing = old('addresses', $bank->addresses?->toArray() ?? []))
        @foreach($existing as $i => $addr)
            @include('banks._address-row', ['index' => $i, 'address' => $addr])
        @endforeach
        @if(empty($existing))
            <div class="text-muted small" id="bank-no-addresses">
                No addresses yet. Click "Add Address" to add one.
            </div>
        @endif
    </div>
</div>

{{-- Notes --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header">
        <h3 class="card-title">Notes</h3>
    </div>
    <div class="card-body">
        <textarea id="notes" name="notes" rows="3" class="form-control"
                  maxlength="2000">{{ old('notes', $bank->notes) }}</textarea>
    </div>
</div>
