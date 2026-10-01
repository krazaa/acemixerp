<x-default-layout>
@section('title', 'Organization Settings')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 m-0">Organization Settings</h1>
</div>
@if ($errors->any())
    <div class="alert alert-danger">
        <strong>Please fix the following errors:</strong>

        <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
<form method="POST" action="{{ route('organization.update') }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="row g-4">
        {{-- Identity --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">
                    <div class="card-title">Identity
                        </div>
                    </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="name">Legal / Trading Name <span class="text-danger">*</span></label>
                        <input id="name" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $organization->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="legal_name">Legal Name (if different)</label>
                        <input id="legal_name" name="legal_name" class="form-control"
                               value="{{ old('legal_name', $organization->legal_name) }}">
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="tax_number">Tax Number</label>
                            <input id="tax_number" name="tax_number" class="form-control"
                                   value="{{ old('tax_number', $organization->tax_number) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="registration_number">Registration #</label>
                            <input id="registration_number" name="registration_number" class="form-control"
                                   value="{{ old('registration_number', $organization->registration_number) }}">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label" for="logo">Logo</label>
                        <input id="logo" name="logo" type="file" accept="image/png,image/jpeg,image/webp"
                               class="form-control @error('logo') is-invalid @enderror">
                        @error('logo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @if($organization->logo_path)
                            <div class="text-muted small mt-2">Current logo stored securely. Uploading replaces it.</div>
                        @endif
                        @if(auth()->user() && \App\Models\Organization::current()->logo_path)
                            <img src="{{ route('organization.logo') }}" alt="Logo" height="72" class="me-2">
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Contact --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">
                    <div class="card-title">Contact
                        </div>
                    </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input id="email" name="email" type="email" class="form-control"
                               value="{{ old('email', $organization->email) }}">
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="phone">Phone</label>
                            <input id="phone" name="phone" class="form-control"
                                   value="{{ old('phone', $organization->phone) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="website">Website</label>
                            <input id="website" name="website" class="form-control"
                                   value="{{ old('website', $organization->website) }}">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label" for="country">Country (ISO-2) <span class="text-danger">*</span></label>
                        <input id="country" name="country" maxlength="2" class="form-control"
                               value="{{ old('country', $organization->country) }}" required>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                    <span>Addresses</span>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="vendorAddAddressRow()">
                        <i class="bi bi-plus-lg"></i> Add Address
                    </button>
                </div>
                <input type="hidden" name="addresses_present" value="1">
                @php($addressRows = session()->hasOldInput('addresses_present') ? (old('addresses') ?? []) : old('addresses', $addresses))
                <div class="card-body" id="vendor-addresses-container">
                    @forelse($addressRows as $i => $address)
                        @include('vendors._address-row', ['index' => $i, 'address' => $address, 'showRegionFields' => true])
                    @empty
                        <div class="text-muted small" id="vendor-no-addresses">No addresses yet. Click "Add Address" to add one.</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Financial --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Financial</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="currency_code">Currency <span class="text-danger">*</span></label>
                            <select id="currency_code" name="currency_code" class="form-select" required>
                                @foreach($currencies as $code)
                                    <option value="{{ $code }}" @selected(old('currency_code', $organization->currency_code) === $code)>
                                        {{ $code }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="currency_symbol">Symbol <span class="text-danger">*</span></label>
                            <input id="currency_symbol" name="currency_symbol" class="form-control"
                                   value="{{ old('currency_symbol', $organization->currency_symbol) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="currency_decimals">Decimals <span class="text-danger">*</span></label>
                            <input id="currency_decimals" name="currency_decimals" type="number" min="0" max="4"
                                   class="form-control" value="{{ old('currency_decimals', $organization->currency_decimals) }}" required>
                        </div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="form-label" for="timezone">Timezone <span class="text-danger">*</span></label>
                            <select id="timezone" name="timezone" class="form-select" required>
                                @foreach($timezones as $tz)
                                    <option value="{{ $tz }}" @selected(old('timezone', $organization->timezone) === $tz)>{{ $tz }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="date_format">Date Format <span class="text-danger">*</span></label>
                            <select id="date_format" name="date_format" class="form-select" required>
                                @foreach($dateFormats as $fmt)
                                    <option value="{{ $fmt }}" @selected(old('date_format', $organization->date_format) === $fmt)>{{ $fmt }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="form-label" for="fiscal_year_start_month">Fiscal Year Start Month <span class="text-danger">*</span></label>
                            <input id="fiscal_year_start_month" name="fiscal_year_start_month" class="form-control"
                                   value="{{ old('fiscal_year_start_month', $organization->fiscal_year_start_month) }}"
                                   pattern="0[1-9]|1[0-2]" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="inventory_valuation_method">Valuation Method <span class="text-danger">*</span></label>
                            <select id="inventory_valuation_method" name="inventory_valuation_method" class="form-select" required>
                                <option value="WEIGHTED_AVERAGE" @selected(old('inventory_valuation_method', $organization->inventory_valuation_method) === 'WEIGHTED_AVERAGE')>Weighted Average</option>
                                <option value="FIFO" @selected(old('inventory_valuation_method', $organization->inventory_valuation_method) === 'FIFO')>FIFO</option>
                            </select>
                            <div class="form-text">
                                Cannot be changed after financial activity exists.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Policy --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Policy</div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input type="hidden" name="allow_negative_stock" value="0">
                        <input id="allow_negative_stock" name="allow_negative_stock" type="checkbox" value="1"
                               class="form-check-input" @checked(old('allow_negative_stock', $organization->allow_negative_stock))>
                        <label class="form-check-label" for="allow_negative_stock">
                            Allow negative stock
                        </label>
                    </div>
                    <div class="form-check form-switch">
                        <input type="hidden" name="require_approval_for_journal" value="0">
                        <input id="require_approval_for_journal" name="require_approval_for_journal" type="checkbox" value="1"
                               class="form-check-input" @checked(old('require_approval_for_journal', $organization->require_approval_for_journal))>
                        <label class="form-check-label" for="require_approval_for_journal">
                            Require approval before journal posting
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary" type="submit">Save Changes</button>
    </div>
</form>
@include('vendors._form-scripts', ['addressRows' => $addressRows, 'showRegionFields' => true])


</x-default-layout>
