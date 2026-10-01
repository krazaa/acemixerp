@php($isEdit = $bankAccount->exists)

{{-- Linkage --}}
<div class="card border-0 shadow-sm mb-3">

    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="bank_id">Bank <span class="text-danger">*</span></label>
                <select id="bank_id" name="bank_id"
                        class="form-select @error('bank_id') is-invalid @enderror" required>
                    <option value="">— Select bank —</option>
                    @foreach($banks as $b)
                        <option value="{{ $b->id }}"
                            @selected((int) old('bank_id', $bankAccount->bank_id) === $b->id)>
                            {{ $b->code }} — {{ $b->name }}
                        </option>
                    @endforeach
                </select>
                @error('bank_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="gl_account_id">
                    GL Account <span class="text-danger">*</span>
                </label>
                <select id="gl_account_id" name="gl_account_id"
                        class="form-select @error('gl_account_id') is-invalid @enderror" data-control="select2" required>
                    <option value="">— Select asset account —</option>
                    @foreach($glAccounts as $a)
                        <option value="{{ $a->id }}"
                            @selected((int) old('gl_account_id', $bankAccount->gl_account_id) === $a->id)>
                            {{ $a->code }} — {{ $a->name }}
                        </option>
                    @endforeach
                </select>
                @error('gl_account_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">
                    Must be a postable <strong>Asset</strong> account (e.g. under Bank Accounts).
                    Cannot be changed once the account has activity.
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Identity --}}
<div class="card border-0 shadow-sm mb-3">

    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="code">Code <span class="text-danger">*</span></label>
                <input id="code" name="code"
                       class="form-control @error('code') is-invalid @enderror"
                       value="{{ old('code', $bankAccount->code) }}"
                       pattern="[A-Z0-9_-]+" required maxlength="32">
                @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">Internal code, e.g. BA-MAIN-USD.</div>
            </div>
            <div class="col-md-8">
                <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                <input id="name" name="name"
                       class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $bankAccount->name) }}" required maxlength="128">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="account_type">
                    Account Type <span class="text-danger">*</span>
                </label>
                <select id="account_type" name="account_type" class="form-select" required>
                    @foreach(\App\Enums\BankAccountType::cases() as $t)
                        <option value="{{ $t->value }}"
                            @selected(old('account_type', $bankAccount->account_type?->value) === $t->value)>
                            {{ $t->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="currency_code">
                    Currency <span class="text-danger">*</span>
                </label>
                <input id="currency_code" name="currency_code" maxlength="3"
                       class="form-control @error('currency_code') is-invalid @enderror"
                       value="{{ old('currency_code', $bankAccount->currency_code) }}"
                       required placeholder="USD">
                @error('currency_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">3-letter ISO code.</div>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="status">Status <span class="text-danger">*</span></label>
                <select id="status" name="status" class="form-select" required>
                    @foreach(\App\Enums\RecordStatus::cases() as $s)
                        <option value="{{ $s->value }}"
                            @selected(old('status', $bankAccount->status?->value) === $s->value)>
                            {{ $s->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</div>

{{-- Account identifiers --}}
<div class="card border-0 shadow-sm mb-3">

    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="account_number">
                    Account Number <span class="text-danger">*</span>
                </label>
                <input id="account_number" name="account_number"
                       class="form-control @error('account_number') is-invalid @enderror"
                       value="{{ old('account_number', $bankAccount->account_number) }}"
                       required maxlength="64">
                @error('account_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">Unique per bank.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="iban">IBAN</label>
                <input id="iban" name="iban"
                       class="form-control @error('iban') is-invalid @enderror"
                       value="{{ old('iban', $bankAccount->iban) }}" maxlength="64">
                @error('iban')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="swift_code">SWIFT / BIC</label>
                <input id="swift_code" name="swift_code"
                       class="form-control @error('swift_code') is-invalid @enderror"
                       value="{{ old('swift_code', $bankAccount->swift_code) }}" maxlength="16">
                @error('swift_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>

{{-- Opening balance --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="opening_balance">Opening Balance</label>
                <input id="opening_balance" name="opening_balance" type="number" step="0.0001"
                       class="form-control @error('opening_balance') is-invalid @enderror"
                       value="{{ old('opening_balance', $bankAccount->opening_balance ?? 0) }}">
                @error('opening_balance')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">
                    Recorded as the account's opening position. Does not auto-post a journal;
                    use a manual journal to move this into the ledger if needed.
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="opening_balance_date">Opening Balance Date</label>
                <input id="opening_balance_date" name="opening_balance_date" type="date"
                       class="form-control @error('opening_balance_date') is-invalid @enderror"
                       value="{{ old('opening_balance_date', $bankAccount->opening_balance_date?->toDateString()) }}">
                @error('opening_balance_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>

{{-- Default flag --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="form-check form-switch">
            <input type="hidden" name="is_default" value="0">
            <input id="is_default" name="is_default" type="checkbox" value="1"
                   class="form-check-input"
                   @checked(old('is_default', $bankAccount->is_default))>
            <label class="form-check-label" for="is_default">
                Set as the default bank account for the organization
            </label>
        </div>
        <div class="form-text mt-1">
            Only one bank account can be default. Setting this will unset the current default.
        </div>
    </div>
</div>
