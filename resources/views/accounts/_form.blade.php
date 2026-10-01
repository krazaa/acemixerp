@php($locked = $locked ?? ($account->exists && $account->isLocked()))

@if($locked)
    <div class="alert alert-warning">
        <i class="bi bi-lock-fill"></i>
        This account has journal postings. Its <strong>code</strong>, <strong>type</strong>,
        <strong>normal balance</strong>, and <strong>postable</strong> flag are locked (§57).
    </div>
@endif

{{-- Identity --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label" for="code">Code <span class="text-danger">*</span></label>
                <input id="code" name="code" class="form-control @error('code') is-invalid @enderror"
                       value="{{ old('code', $account->code) }}" required maxlength="32"
                       @if($locked) readonly @endif>
                @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">Hierarchical, e.g. 1000, 1100, 1110.</div>
            </div>
            <div class="col-md-9">
                <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                <input id="name" name="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $account->name) }}" required maxlength="192">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="type">Type <span class="text-danger">*</span></label>
                <select id="type" name="type" class="form-select" required
                        @if($locked) disabled @endif>
                    @foreach($types as $t)
                        <option value="{{ $t->value }}" @selected(old('type', $account->type?->value) === $t->value)>
                            {{ $t->label() }}
                        </option>
                    @endforeach
                </select>
                @if($locked)<input type="hidden" name="type" value="{{ $account->type->value }}">@endif
            </div>
            <div class="col-md-4">
                <label class="form-label" for="normal_balance">Normal Balance</label>
                <select id="normal_balance" name="normal_balance" class="form-select"
                        @if($locked) disabled @endif>
                    @foreach(\App\Enums\NormalBalance::cases() as $b)
                        <option value="{{ $b->value }}" @selected(old('normal_balance', $account->normal_balance?->value) === $b->value)>
                            {{ ucfirst($b->value) }}
                        </option>
                    @endforeach
                </select>
                @if($locked)<input type="hidden" name="normal_balance" value="{{ $account->normal_balance->value }}">@endif
                <div class="form-text">Leave blank to inherit from type.</div>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="parent_id">Parent Account</label>
                <select id="parent_id" name="parent_id" class="form-select" data-control="select2">
                    <option value="">— None (top-level) —</option>
                    @foreach($parents as $p)
                        <option value="{{ $p->id }}" @selected((int) old('parent_id', $account->parent_id) === $p->id)>
                            {{ $p->code }} — {{ $p->name }}
                        </option>
                    @endforeach
                </select>
                <div class="form-text">Must match this account's type.</div>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="currency_code">Currency</label>
                <input id="currency_code" name="currency_code" maxlength="3"
                       class="form-control" placeholder="Base currency"
                       value="{{ old('currency_code', $account->currency_code) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="status">Status <span class="text-danger">*</span></label>
                <select id="status" name="status" class="form-select" required>
                    @foreach(\App\Enums\RecordStatus::cases() as $s)
                        <option value="{{ $s->value }}" @selected(old('status', $account->status?->value) === $s->value)>
                            {{ $s->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4"></div>
            <div class="col-12">
                <label class="form-label" for="description">Description</label>
                <textarea id="description" name="description" rows="2" class="form-control"
                          maxlength="2000">{{ old('description', $account->description) }}</textarea>
            </div>
        </div>
    </div>
</div>

{{-- Behavior --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="form-check form-switch">
                    <input type="hidden" name="is_postable" value="0">
                    <input id="is_postable" name="is_postable" type="checkbox" value="1"
                           class="form-check-input"
                           @checked(old('is_postable', $account->is_postable ?? true))
                           @if($locked) disabled @endif>
                    @if($locked)<input type="hidden" name="is_postable" value="{{ (int) $account->is_postable }}">@endif
                    <label class="form-check-label" for="is_postable">
                        Postable — accepts journal lines
                    </label>
                </div>
                <div class="form-text">
                    Header accounts aggregating children must be non-postable.
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-check form-switch">
                    <input type="hidden" name="is_cash" value="0">
                    <input id="is_cash" name="is_cash" type="checkbox" value="1"
                           class="form-check-input" @checked(old('is_cash', $account->is_cash))>
                    <label class="form-check-label" for="is_cash">Cash account</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-check form-switch">
                    <input type="hidden" name="is_bank" value="0">
                    <input id="is_bank" name="is_bank" type="checkbox" value="1"
                           class="form-check-input" @checked(old('is_bank', $account->is_bank))>
                    <label class="form-check-label" for="is_bank">Bank account (parent)</label>
                </div>
            </div>
        </div>
        <hr>
        <div class="row g-3">
            <div class="col-md-4">
                <div class="form-check form-switch">
                    <input type="hidden" name="requires_cost_center" value="0">
                    <input id="requires_cost_center" name="requires_cost_center" type="checkbox" value="1"
                           class="form-check-input" @checked(old('requires_cost_center', $account->requires_cost_center))>
                    <label class="form-check-label" for="requires_cost_center">Requires cost center</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-check form-switch">
                    <input type="hidden" name="requires_department" value="0">
                    <input id="requires_department" name="requires_department" type="checkbox" value="1"
                           class="form-check-input" @checked(old('requires_department', $account->requires_department))>
                    <label class="form-check-label" for="requires_department">Requires department</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-check form-switch">
                    <input type="hidden" name="requires_party" value="0">
                    <input id="requires_party" name="requires_party" type="checkbox" value="1"
                           class="form-check-input" @checked(old('requires_party', $account->requires_party))>
                    <label class="form-check-label" for="requires_party">
                        Requires customer / vendor / employee
                    </label>
                </div>
                <div class="form-text">Enable for AR/AP control accounts.</div>
            </div>
        </div>
    </div>
</div>
