<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">Identity</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="code">Code <span class="text-danger">*</span></label>
                <input id="code" name="code" class="form-control @error('code') is-invalid @enderror"
                       value="{{ old('code', $taxRate->code) }}" pattern="[A-Z0-9_-]+" required>
                @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-8">
                <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                <input id="name" name="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $taxRate->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">Rate</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="type">Type <span class="text-danger">*</span></label>
                <select id="type" name="type" class="form-select" required>
                    @foreach(\App\Enums\TaxRateType::cases() as $t)
                        <option value="{{ $t->value }}" @selected(old('type', $taxRate->type?->value) === $t->value)>
                            {{ $t->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="component">Component <span class="text-danger">*</span></label>
                <select id="component" name="component" class="form-select" required>
                    @foreach(\App\Enums\TaxRateComponent::cases() as $c)
                        <option value="{{ $c->value }}" @selected(old('component', $taxRate->component?->value) === $c->value)>
                            {{ $c->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="rate">Rate (%) <span class="text-danger">*</span></label>
                <input id="rate" name="rate" type="number" step="0.000001" min="0" max="100"
                       class="form-control @error('rate') is-invalid @enderror"
                       value="{{ old('rate', $taxRate->rate ?? 0) }}" required>
                @error('rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">Effective Dating &amp; Flags</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="effective_from">Effective From <span class="text-danger">*</span></label>
                <input id="effective_from" name="effective_from" type="date"
                       class="form-control @error('effective_from') is-invalid @enderror"
                       value="{{ old('effective_from', $taxRate->effective_from?->toDateString() ?? now()->toDateString()) }}" required>
                @error('effective_from')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="effective_to">Effective To</label>
                <input id="effective_to" name="effective_to" type="date"
                       class="form-control @error('effective_to') is-invalid @enderror"
                       value="{{ old('effective_to', $taxRate->effective_to?->toDateString()) }}">
                @error('effective_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <div class="form-check form-switch">
                    <input type="hidden" name="is_default" value="0">
                    <input id="is_default" name="is_default" type="checkbox" value="1"
                           class="form-check-input" @checked(old('is_default', $taxRate->is_default))>
                    <label class="form-check-label" for="is_default">Default for its component</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-check form-switch">
                    <input type="hidden" name="is_compound" value="0">
                    <input id="is_compound" name="is_compound" type="checkbox" value="1"
                           class="form-check-input" @checked(old('is_compound', $taxRate->is_compound))>
                    <label class="form-check-label" for="is_compound">Compound Tax</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-check form-switch">
                    <input type="hidden" name="is_recoverable" value="0">
                    <input id="is_recoverable" name="is_recoverable" type="checkbox" value="1"
                           class="form-check-input" @checked(old('is_recoverable', $taxRate->is_recoverable ?? true))>
                    <label class="form-check-label" for="is_recoverable">Recoverable (input tax)</label>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">Other</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="status">Status <span class="text-danger">*</span></label>
                <select id="status" name="status" class="form-select" required>
                    @foreach(\App\Enums\RecordStatus::cases() as $s)
                        <option value="{{ $s->value }}" @selected(old('status', $taxRate->status?->value) === $s->value)>
                            {{ $s->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                <label class="form-label" for="description">Description</label>
                <textarea id="description" name="description" rows="2" class="form-control"
                          maxlength="2000">{{ old('description', $taxRate->description) }}</textarea>
            </div>
        </div>
    </div>
</div>
