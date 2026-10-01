{{-- Identity --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="code">Code <span class="text-danger">*</span></label>
                <input id="code" name="code" class="form-control @error('code') is-invalid @enderror"
                       value="{{ old('code', $warehouse->code) }}" pattern="[A-Z0-9_-]+" required>
                @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-8">
                <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                <input id="name" name="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $warehouse->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="type">Type <span class="text-danger">*</span></label>
                <select id="type" name="type" class="form-select" required>
                    @foreach(\App\Enums\WarehouseType::cases() as $t)
                        <option value="{{ $t->value }}" @selected(old('type', $warehouse->type?->value) === $t->value)>
                            {{ $t->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="status">Status <span class="text-danger">*</span></label>
                <select id="status" name="status" class="form-select" required>
                    @foreach(\App\Enums\RecordStatus::cases() as $s)
                        <option value="{{ $s->value }}" @selected(old('status', $warehouse->status?->value) === $s->value)>
                            {{ $s->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <div class="form-check form-switch mt-4">
                    <input type="hidden" name="is_default" value="0">
                    <input id="is_default" name="is_default" type="checkbox" value="1"
                           class="form-check-input" @checked(old('is_default', $warehouse->is_default))>
                    <label class="form-check-label" for="is_default">Default warehouse</label>
                </div>
            </div>
            <div class="col-12">
                <label class="form-label" for="description">Description</label>
                <textarea id="description" name="description" rows="2" class="form-control">{{ old('description', $warehouse->description) }}</textarea>
            </div>
        </div>
    </div>
</div>

{{-- Management --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header">
        <h3 class="card-title">Management</h3>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="manager_id">Manager</label>
                <select id="manager_id" name="manager_id" class="form-select">
                    <option value="">— None —</option>
                    @foreach($managers as $m)
                        <option value="{{ $m->id }}" @selected((int) old('manager_id', $warehouse->manager_id) === $m->id)>
                            {{ $m->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="department_id">Department</label>
                <select id="department_id" name="department_id" class="form-select">
                    <option value="">— None —</option>
                    @foreach($departments as $d)
                        <option value="{{ $d->id }}" @selected((int) old('department_id', $warehouse->department_id) === $d->id)>
                            {{ $d->code }} — {{ $d->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="email">Email</label>
                <input id="email" name="email" type="email" class="form-control"
                       value="{{ old('email', $warehouse->email) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="phone">Phone</label>
                <input id="phone" name="phone" class="form-control"
                       value="{{ old('phone', $warehouse->phone) }}">
            </div>
        </div>
    </div>
</div>

{{-- Operations --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header">
        <h3 class="card-title">Operations</h3>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="form-check form-switch">
                    <input type="hidden" name="allow_negative_stock" value="0">
                    <input id="allow_negative_stock" name="allow_negative_stock" type="checkbox" value="1"
                           class="form-check-input" @checked(old('allow_negative_stock', $warehouse->allow_negative_stock))>
                    <label class="form-check-label" for="allow_negative_stock">
                        Allow negative stock in this warehouse
                    </label>
                </div>
                <div class="form-text">
                    Only enabled when the organization policy allows it.
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-check form-switch">
                    <input type="hidden" name="is_pickable" value="0">
                    <input id="is_pickable" name="is_pickable" type="checkbox" value="1"
                           class="form-check-input" @checked(old('is_pickable', $warehouse->is_pickable ?? true))>
                    <label class="form-check-label" for="is_pickable">
                        Available for picking &amp; dispatch
                    </label>
                </div>
                <div class="form-text">Transit and quarantine warehouses are normally not pickable.</div>
            </div>
        </div>
    </div>
</div>

{{-- Addresses --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span>Addresses</span>
        <button type="button" class="btn btn-sm btn-outline-secondary"
                onclick="warehouseAddAddressRow()">
            <i class="bi bi-plus-lg"></i> Add Address
        </button>
    </div>
    <div class="card-body" id="warehouse-addresses-container">
        @php($existing = old('addresses', $warehouse->addresses?->toArray() ?? []))
        @foreach($existing as $i => $addr)
            @include('warehouses._address-row', ['index' => $i, 'address' => $addr])
        @endforeach
        @if(empty($existing))
            <div class="text-muted small" id="warehouse-no-addresses">
                No addresses yet. Click "Add Address" to add one.
            </div>
        @endif
    </div>
</div>
