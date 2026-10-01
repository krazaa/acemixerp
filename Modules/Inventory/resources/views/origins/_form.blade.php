<div class="mb-3">
    <label class="form-label">
        Origin Name <span class="text-danger">*</span>
    </label>

    <input
        type="text"
        name="name"
        class="form-control @error('name') is-invalid @enderror"
        value="{{ old('name', $origin->name) }}"
        placeholder="e.g. Pakistan"
        required>

    @error('name')
        <div class="invalid-feedback">
            {{ $message }}
        </div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label">
        Code
    </label>

    <input
        type="text"
        name="code"
        class="form-control @error('code') is-invalid @enderror"
        value="{{ old('code', $origin->code) }}"
        placeholder="e.g. PK">

    @error('code')
        <div class="invalid-feedback">
            {{ $message }}
        </div>
    @enderror
</div>

 <div class="col-md-4">
        <label for="status" class="form-label">Status *</label>
        <select id="status" name="status" required class="form-select @error('status') is-invalid @enderror">
            @foreach(\Modules\Inventory\Enums\OriginStatus::cases() as $status)
                <option value="{{ $status->value }}" @selected(old('status', $origin->status?->value) === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
