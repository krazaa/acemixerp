@php($isEdit = $user !== null)

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
        <input id="name" name="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $user?->name) }}" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="email">Email <span class="text-danger">*</span></label>
        <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror"
               value="{{ old('email', $user?->email) }}" required>
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label" for="employee_number">Employee #</label>
        <input id="employee_number" name="employee_number" class="form-control"
               value="{{ old('employee_number', $user?->employee_number) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="phone">Phone</label>
        <input id="phone" name="phone" class="form-control"
               value="{{ old('phone', $user?->phone) }}">
    </div>

    <div class="col-md-6">
        <label class="form-label" for="password">
            Password @if(!$isEdit)<span class="text-danger">*</span>@endif
        </label>
        <input id="password" name="password" type="password" class="form-control @error('password') is-invalid @enderror"
               @if(!$isEdit) required @endif autocomplete="new-password">
        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        @if($isEdit)<div class="form-text">Leave blank to keep the current password.</div>@endif
    </div>
    <div class="col-md-6">
        <label class="form-label" for="password_confirmation">Confirm Password</label>
        <input id="password_confirmation" name="password_confirmation" type="password" class="form-control"
               autocomplete="new-password">
    </div>

    @if(!$isEdit)
    <div class="col-md-6">
        <label class="form-label" for="status">Status <span class="text-danger">*</span></label>
        <select id="status" name="status" class="form-select">
            @foreach(\App\Enums\UserStatus::cases() as $s)
                <option value="{{ $s->value }}" @selected(old('status') === $s->value)>{{ $s->label() }}</option>
            @endforeach
        </select>
    </div>
    @endif

    <div class="col-12">
        <label class="form-label">Roles</label>
        <div class="row">
            @php($selectedRoles = old('roles', $user?->roles->pluck('name')->all() ?? []))
            @foreach($roles as $role)
                <div class="col-md-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox"
                               id="role-{{ $role->id }}"
                               name="roles[]" value="{{ $role->name }}"
                               @checked(in_array($role->name, $selectedRoles, true))>
                        <label class="form-check-label" for="role-{{ $role->id }}">
                            {{ $role->name }}
                            <div class="text-muted small">{{ $role->description ?? '' }}</div>
                        </label>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
