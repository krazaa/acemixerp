@php
    $isEdit = $role !== null;

    $selected = old(
        'permissions',
        $role?->permissions->pluck('name')->all() ?? []
    );
@endphp


<div class="row g-3">

    {{-- Name --}}
    <div class="col-md-6">
        <label class="form-label" for="name">
            Name <span class="text-danger">*</span>
        </label>

        <input
            id="name"
            name="name"
            type="text"
            class="form-control @error('name') is-invalid @enderror"
            value="{{ old('name', $role?->name) }}"
            pattern="[a-z][a-z0-9_-]*"
            maxlength="255"
            required
            @if($isEdit) readonly @endif
        >

        @error('name')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror

        <div class="form-text">
            Lowercase letters, digits, <code>_</code> and <code>-</code> only.
        </div>
    </div>

    {{-- Level --}}
    <div class="col-md-3">
        <label class="form-label" for="level">
            Level <span class="text-danger">*</span>
        </label>

        <input
            id="level"
            name="level"
            type="number"
            min="1"
            max="999"
            class="form-control @error('level') is-invalid @enderror"
            value="{{ old('level', $role?->level ?? 100) }}"
            required
        >

        @error('level')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>

    {{-- Description --}}
    <div class="col-md-12">
        <label class="form-label" for="description">
            Description
        </label>

        <input
            id="description"
            name="description"
            type="text"
            class="form-control @error('description') is-invalid @enderror"
            value="{{ old('description', $role?->description) }}"
            maxlength="1000"
        >

        @error('description')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Permissions</h3>
    </div>
<div class="card-body">
    {{-- Permissions --}}

    <div class="col-12">
        @foreach($permissionGroups as $group => $perms)
            <fieldset class="border rounded p-3 mb-2">
                <legend class="float-none w-auto px-2 fs-6 text-uppercase text-muted">
                    {{ $group ?: 'Ungrouped' }}
                </legend>
    <div class="row">
    @foreach($perms as $perm)
        <div class="col-2 mb-2">
            <div class="form-check">
                <input
                    class="form-check-input"
                    type="checkbox"
                    id="perm-{{ $perm->id }}"
                    name="permissions[]"
                    value="{{ $perm->name }}"
                    @checked(in_array($perm->name, $selected, true))
                >

                <label class="form-check-label" for="perm-{{ $perm->id }}">
                    {{ $perm->name }}

                    @if($perm->description)
                        <div class="text-muted small">
                            {{ $perm->description }}
                        </div>
                    @endif
                </label>
            </div>
        </div>
    @endforeach
</div>


            </fieldset>
        @endforeach
    </div>
</div>
</div>
</div>
