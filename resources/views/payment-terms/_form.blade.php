<div class="card border-0 shadow-sm mb-3">
    <div class="card-header">
        <h3 class="card-title">Identity</h3>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="code">Code <span class="text-danger">*</span></label>
                <input id="code" name="code" class="form-control @error('code') is-invalid @enderror"
                       value="{{ old('code', $paymentTerm->code) }}" pattern="[A-Z0-9_-]+" required>
                @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-8">
                <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                <input id="name" name="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $paymentTerm->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header">
        <h3 class="card-title">Term Rules</h3>
        </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="type">Type <span class="text-danger">*</span></label>
                <select id="type" name="type" class="form-select" required>
                    @foreach(\App\Enums\PaymentTermType::cases() as $t)
                        <option value="{{ $t->value }}" @selected(old('type', $paymentTerm->type?->value) === $t->value)>
                            {{ $t->label() }}
                        </option>
                    @endforeach
                </select>
                @error('type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="days">Days</label>
                <input id="days" name="days" type="number" min="0" max="365"
                       class="form-control @error('days') is-invalid @enderror"
                       value="{{ old('days', $paymentTerm->days ?? 0) }}">
                @error('days')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">Used for "Net N" and "EOM + N".</div>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="day_of_month">Day of Month</label>
                <input id="day_of_month" name="day_of_month" type="number" min="1" max="31"
                       class="form-control @error('day_of_month') is-invalid @enderror"
                       value="{{ old('day_of_month', $paymentTerm->day_of_month) }}">
                @error('day_of_month')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">Required for "Specific Day of Month".</div>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="discount_percent">Early-Payment Discount %</label>
                <input id="discount_percent" name="discount_percent" type="number" step="0.0001"
                       min="0" max="100"
                       class="form-control @error('discount_percent') is-invalid @enderror"
                       value="{{ old('discount_percent', $paymentTerm->discount_percent) }}">
                @error('discount_percent')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="discount_days">Discount Days</label>
                <input id="discount_days" name="discount_days" type="number" min="0" max="365"
                       class="form-control @error('discount_days') is-invalid @enderror"
                       value="{{ old('discount_days', $paymentTerm->discount_days) }}">
                @error('discount_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">E.g. "2% within 10 days" → 2 and 10.</div>
            </div>
            <div class="col-md-4">
                <div class="form-check form-switch mt-4">
                    <input type="hidden" name="is_default" value="0">
                    <input id="is_default" name="is_default" type="checkbox" value="1"
                           class="form-check-input" @checked(old('is_default', $paymentTerm->is_default))>
                    <label class="form-check-label" for="is_default">Default payment term</label>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header">
        <h3 class="card-title">Other</h3>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="status">Status <span class="text-danger">*</span></label>
                <select id="status" name="status" class="form-select" required>
                    @foreach(\App\Enums\RecordStatus::cases() as $s)
                        <option value="{{ $s->value }}" @selected(old('status', $paymentTerm->status?->value) === $s->value)>
                            {{ $s->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                <label class="form-label" for="description">Description</label>
                <textarea id="description" name="description" rows="2" class="form-control"
                          maxlength="2000">{{ old('description', $paymentTerm->description) }}</textarea>
            </div>
        </div>
    </div>
</div>
